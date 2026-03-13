<#
Lab 2 One-Click Curl Runner
- Runs all curl tests in one go
- Saves formatted outputs to docs/evidence/*.txt
- Includes automated 503 + 504 simulations

Usage:
  powershell -ExecutionPolicy Bypass -File .\tests\run-all-curl-tests.ps1

Optional:
  powershell -ExecutionPolicy Bypass -File .\tests\run-all-curl-tests.ps1 -FreshSeed
#>

param(
    [switch]$FreshSeed
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent (Split-Path -Parent $PSCommandPath)
$EvidenceDir = Join-Path $Root 'docs\evidence'

$StudentPath = Join-Path $Root 'services\student-service'
$CoursePath = Join-Path $Root 'services\course-service'
$EnrollPath = Join-Path $Root 'services\enrollment-service'

$studentPid = $null
$coursePid = $null
$enrollPid = $null
$slowMockJob = $null

function Write-Section([string]$msg) {
    Write-Host "`n=== $msg ===" -ForegroundColor Cyan
}

function Ensure-Dir([string]$path) {
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
    }
}

function Get-PidsByPort([int]$port) {
    $conns = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    if (-not $conns) { return @() }
    return ($conns | Select-Object -ExpandProperty OwningProcess -Unique)
}

function Stop-ByPort([int]$port) {
    $pids = Get-PidsByPort $port
    foreach ($pid in $pids) {
        try { Stop-Process -Id $pid -Force -ErrorAction Stop } catch {}
    }
}

function Wait-Port([int]$port, [int]$timeoutSec = 15) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    while ($sw.Elapsed.TotalSeconds -lt $timeoutSec) {
        if ((Get-PidsByPort $port).Count -gt 0) { return $true }
        Start-Sleep -Milliseconds 250
    }
    return $false
}

function Start-ServicePhp([string]$cwd, [int]$port) {
    $proc = Start-Process -FilePath 'php' -ArgumentList @('artisan', 'serve', "--port=$port") -WorkingDirectory $cwd -PassThru -WindowStyle Hidden
    if (-not (Wait-Port -port $port -timeoutSec 20)) {
        throw "Service on port $port failed to start."
    }
    return $proc.Id
}

function New-TempJson([string]$json) {
    $tmp = [System.IO.Path]::GetTempFileName()
    $json | Out-File -FilePath $tmp -Encoding ascii -NoNewline
    return $tmp
}

function Invoke-Curl([string]$method, [string]$url, [string]$jsonBody = $null, [int]$maxTime = 0) {
    $args = @('-s', '-w', "`nHTTP_STATUS:%{http_code}", '-X', $method, $url, '-H', 'Accept: application/json')
    if ($maxTime -gt 0) {
        $args = @('-s', '--max-time', "$maxTime", '-w', "`nHTTP_STATUS:%{http_code}", '-X', $method, $url, '-H', 'Accept: application/json')
    }

    $tmp = $null
    try {
        if ($null -ne $jsonBody) {
            $tmp = New-TempJson $jsonBody
            $args += @('-H', 'Content-Type: application/json', '-d', "@$tmp")
        }

        $raw = (& curl.exe @args 2>&1 | Out-String).Trim()
        if ($raw -match '(?s)^(.*)\r?\nHTTP_STATUS:(\d{3})$') {
            return [pscustomobject]@{ Body = $matches[1].Trim(); Status = [int]$matches[2] }
        }

        return [pscustomobject]@{ Body = $raw; Status = -1 }
    }
    finally {
        if ($tmp -and (Test-Path $tmp)) { Remove-Item $tmp -Force -ErrorAction SilentlyContinue }
    }
}

function Save-Evidence(
    [string]$file,
    [string]$test,
    [string]$method,
    [string]$endpoint,
    [string]$expected,
    [string]$body,
    [string]$precondition,
    [pscustomobject]$resp
) {
    $lines = @(
        "TEST: $test",
        "METHOD: $method $endpoint"
    )

    if ($body) { $lines += "BODY: $body" }
    if ($precondition) { $lines += "PRECONDITION: $precondition" }

    $lines += @(
        "EXPECTED: $expected",
        '',
        'RESPONSE:',
        $resp.Body,
        "HTTP_STATUS: $($resp.Status)"
    )

    $outFile = Join-Path $EvidenceDir $file
    $lines | Out-File -FilePath $outFile -Encoding utf8
    Write-Host "[$($resp.Status)] $file"
}

function Start-SlowStudentMock {
    $script = {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Parse('127.0.0.1'), 8001)
        $listener.Start()
        try {
            $client = $listener.AcceptTcpClient()
            try {
                $stream = $client.GetStream()
                $buffer = New-Object byte[] 4096
                [void]$stream.Read($buffer, 0, $buffer.Length)

                Start-Sleep -Seconds 10

                $body = '{"id":1,"full_name":"Slow Mock"}'
                $resp = "HTTP/1.1 200 OK`r`nContent-Type: application/json`r`nContent-Length: $($body.Length)`r`nConnection: close`r`n`r`n$body"
                $bytes = [System.Text.Encoding]::ASCII.GetBytes($resp)
                $stream.Write($bytes, 0, $bytes.Length)
                $stream.Flush()
            }
            finally {
                $client.Close()
            }
        }
        finally {
            $listener.Stop()
        }
    }

    return Start-Job -ScriptBlock $script
}

try {
    Ensure-Dir $EvidenceDir

    if ($FreshSeed) {
        Write-Section 'Fresh seeding all services'
        Push-Location $StudentPath; php artisan migrate:fresh --seed --force | Out-Null; Pop-Location
        Push-Location $CoursePath; php artisan migrate:fresh --seed --force | Out-Null; Pop-Location
        Push-Location $EnrollPath; php artisan migrate:fresh --seed --force | Out-Null; Pop-Location
        Write-Host 'Fresh seed complete.' -ForegroundColor Green
    }

    Write-Section 'Ensure services are running on 8001/8002/8003'
    if ((Get-PidsByPort 8001).Count -eq 0) { $studentPid = Start-ServicePhp -cwd $StudentPath -port 8001 }
    if ((Get-PidsByPort 8002).Count -eq 0) { $coursePid = Start-ServicePhp -cwd $CoursePath -port 8002 }
    if ((Get-PidsByPort 8003).Count -eq 0) { $enrollPid = Start-ServicePhp -cwd $EnrollPath -port 8003 }
    Write-Host 'Services ready.' -ForegroundColor Green

    $stamp = Get-Date -Format 'yyyyMMddHHmmss'
    $studentEmail = "lab2.student.$stamp@example.com"
    $courseName = "Lab2 Course $stamp"

    Write-Section 'Happy path + CRUD + validation + not-found'

    $r01 = Invoke-Curl 'POST' 'http://localhost:8001/api/students' "{\"full_name\":\"Lab2 Student\",\"email\":\"$studentEmail\",\"age\":22}"
    Save-Evidence '01-create-student.txt' 'Create a new student' 'POST' '/api/students' '201 Created' "{\"full_name\":\"Lab2 Student\",\"email\":\"$studentEmail\",\"age\":22}" '' $r01
    $studentId = ($r01.Body | ConvertFrom-Json -ErrorAction SilentlyContinue).id

    $r02 = Invoke-Curl 'POST' 'http://localhost:8002/api/courses' "{\"name\":\"$courseName\",\"description\":\"Lab2 generated course\",\"credits\":3}"
    Save-Evidence '02-create-course.txt' 'Create a new course' 'POST' '/api/courses' '201 Created' "{\"name\":\"$courseName\",\"description\":\"Lab2 generated course\",\"credits\":3}" '' $r02
    $courseId = ($r02.Body | ConvertFrom-Json -ErrorAction SilentlyContinue).id

    if (-not $studentId) { $studentId = 1 }
    if (-not $courseId) { $courseId = 1 }

    $r03 = Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' "{\"student_id\":$studentId,\"course_id\":$courseId}"
    Save-Evidence '03-create-enrollment.txt' "Enroll student $studentId in course $courseId" 'POST' '/api/enrollments' '201 Created' "{\"student_id\":$studentId,\"course_id\":$courseId}" '' $r03
    $enrollmentId = ($r03.Body | ConvertFrom-Json -ErrorAction SilentlyContinue).id

    Save-Evidence '04-list-students.txt' 'List all students' 'GET' '/api/students' '200 OK' '' '' (Invoke-Curl 'GET' 'http://localhost:8001/api/students')
    Save-Evidence '05-list-courses.txt' 'List all courses' 'GET' '/api/courses' '200 OK' '' '' (Invoke-Curl 'GET' 'http://localhost:8002/api/courses')
    Save-Evidence '06-list-enrollments.txt' 'List all enrollments' 'GET' '/api/enrollments' '200 OK' '' '' (Invoke-Curl 'GET' 'http://localhost:8003/api/enrollments')

    Save-Evidence '07-get-student.txt' "Get student id=$studentId" 'GET' "/api/students/$studentId" '200 OK' '' '' (Invoke-Curl 'GET' "http://localhost:8001/api/students/$studentId")
    Save-Evidence '08-get-course.txt' "Get course id=$courseId" 'GET' "/api/courses/$courseId" '200 OK' '' '' (Invoke-Curl 'GET' "http://localhost:8002/api/courses/$courseId")

    Save-Evidence '09-update-student.txt' "Update student id=$studentId" 'PUT' "/api/students/$studentId" '200 OK' "{\"full_name\":\"Lab2 Student Updated\",\"email\":\"$studentEmail\",\"age\":23}" '' (Invoke-Curl 'PUT' "http://localhost:8001/api/students/$studentId" "{\"full_name\":\"Lab2 Student Updated\",\"email\":\"$studentEmail\",\"age\":23}")
    Save-Evidence '10-update-course.txt' "Update course id=$courseId" 'PUT' "/api/courses/$courseId" '200 OK' "{\"name\":\"$courseName Updated\",\"description\":\"Updated\",\"credits\":4}" '' (Invoke-Curl 'PUT' "http://localhost:8002/api/courses/$courseId" "{\"name\":\"$courseName Updated\",\"description\":\"Updated\",\"credits\":4}")

    Save-Evidence '11-missing-field.txt' 'Create student without full_name' 'POST' '/api/students' '400 Validation Error' '{"email":"no-name@test.com","age":20}' '' (Invoke-Curl 'POST' 'http://localhost:8001/api/students' '{"email":"no-name@test.com","age":20}')
    Save-Evidence '12-invalid-email.txt' 'Create student with invalid email' 'POST' '/api/students' '400 Validation Error' '{"full_name":"Bad Email","email":"not-an-email","age":20}' '' (Invoke-Curl 'POST' 'http://localhost:8001/api/students' '{"full_name":"Bad Email","email":"not-an-email","age":20}')
    Save-Evidence '13-duplicate-email.txt' 'Create student with duplicate email' 'POST' '/api/students' '400 Validation Error' "{\"full_name\":\"Duplicate\",\"email\":\"$studentEmail\",\"age\":20}" '' (Invoke-Curl 'POST' 'http://localhost:8001/api/students' "{\"full_name\":\"Duplicate\",\"email\":\"$studentEmail\",\"age\":20}")
    Save-Evidence '14-negative-age.txt' 'Create student with negative age' 'POST' '/api/students' '400 Validation Error' '{"full_name":"Negative Age","email":"neg@test.com","age":-5}' '' (Invoke-Curl 'POST' 'http://localhost:8001/api/students' '{"full_name":"Negative Age","email":"neg@test.com","age":-5}')
    Save-Evidence '15-empty-body.txt' 'Create student with empty body' 'POST' '/api/students' '400 Validation Error' '{}' '' (Invoke-Curl 'POST' 'http://localhost:8001/api/students' '{}')
    Save-Evidence '16-invalid-credits.txt' 'Create course with credits=0' 'POST' '/api/courses' '400 Validation Error' '{"name":"Bad Credits","description":"Testing","credits":0}' '' (Invoke-Curl 'POST' 'http://localhost:8002/api/courses' '{"name":"Bad Credits","description":"Testing","credits":0}')

    Save-Evidence '17-student-not-found.txt' 'Get missing student' 'GET' '/api/students/99999' '404 Not Found' '' '' (Invoke-Curl 'GET' 'http://localhost:8001/api/students/99999')
    Save-Evidence '18-course-not-found.txt' 'Get missing course' 'GET' '/api/courses/99999' '404 Not Found' '' '' (Invoke-Curl 'GET' 'http://localhost:8002/api/courses/99999')
    Save-Evidence '19-enroll-bad-student.txt' 'Enroll non-existent student' 'POST' '/api/enrollments' '404 Not Found' "{\"student_id\":99999,\"course_id\":$courseId}" '' (Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' "{\"student_id\":99999,\"course_id\":$courseId}")
    Save-Evidence '20-enroll-bad-course.txt' 'Enroll non-existent course' 'POST' '/api/enrollments' '404 Not Found' "{\"student_id\":$studentId,\"course_id\":99999}" '' (Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' "{\"student_id\":$studentId,\"course_id\":99999}")
    Save-Evidence '21-delete-missing-student.txt' 'Delete missing student' 'DELETE' '/api/students/99999' '404 Not Found' '' '' (Invoke-Curl 'DELETE' 'http://localhost:8001/api/students/99999')

    Save-Evidence '22-duplicate-enrollment.txt' 'Duplicate enrollment attempt' 'POST' '/api/enrollments' '409 Conflict' "{\"student_id\":$studentId,\"course_id\":$courseId}" '' (Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' "{\"student_id\":$studentId,\"course_id\":$courseId}")

    Write-Section 'Cascade + deletes'
    $cascadeEmail = "cascade.$stamp@example.com"
    $r23a = Invoke-Curl 'POST' 'http://localhost:8001/api/students' "{\"full_name\":\"Cascade User\",\"email\":\"$cascadeEmail\",\"age\":20}"
    $cascadeStudentId = ($r23a.Body | ConvertFrom-Json -ErrorAction SilentlyContinue).id
    if (-not $cascadeStudentId) { $cascadeStudentId = $studentId }

    $r23b = Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' "{\"student_id\":$cascadeStudentId,\"course_id\":$courseId}"
    $r23c = Invoke-Curl 'DELETE' "http://localhost:8001/api/students/$cascadeStudentId"
    $r23d = Invoke-Curl 'GET' 'http://localhost:8003/api/enrollments'

    $cascadeLines = @(
        "TEST: Delete student and verify cascade enrollments",
        "STEPS:",
        "  1. Create student",
        "  2. Create enrollment",
        "  3. Delete student",
        "  4. List enrollments",
        "EXPECTED: Deleted student's enrollment no longer appears",
        '',
        'STEP 1 RESPONSE:', $r23a.Body, "HTTP_STATUS: $($r23a.Status)",
        '',
        'STEP 2 RESPONSE:', $r23b.Body, "HTTP_STATUS: $($r23b.Status)",
        '',
        'STEP 3 RESPONSE:', $r23c.Body, "HTTP_STATUS: $($r23c.Status)",
        '',
        'STEP 4 RESPONSE:', $r23d.Body, "HTTP_STATUS: $($r23d.Status)"
    )
    $cascadeLines | Out-File -FilePath (Join-Path $EvidenceDir '23-cascade-delete.txt') -Encoding utf8
    Write-Host '[multi] 23-cascade-delete.txt'

    if (-not $enrollmentId) { $enrollmentId = 1 }
    Save-Evidence '24-delete-enrollment.txt' "Delete enrollment id=$enrollmentId" 'DELETE' "/api/enrollments/$enrollmentId" '200 OK' '' '' (Invoke-Curl 'DELETE' "http://localhost:8003/api/enrollments/$enrollmentId")
    Save-Evidence '25-delete-course.txt' "Delete course id=$courseId" 'DELETE' "/api/courses/$courseId" '200 OK' '' '' (Invoke-Curl 'DELETE' "http://localhost:8002/api/courses/$courseId")
    Save-Evidence '26-delete-student.txt' "Delete student id=$studentId" 'DELETE' "/api/students/$studentId" '200 OK' '' '' (Invoke-Curl 'DELETE' "http://localhost:8001/api/students/$studentId")

    Write-Section '503 simulation: Student service down'
    Stop-ByPort 8001
    Start-Sleep -Milliseconds 600
    $r27 = Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' '{"student_id":1,"course_id":1}'
    Save-Evidence '27-service-unavailable-503.txt' 'Enroll while Student Service is down' 'POST' '/api/enrollments' '503 Service Unavailable' '{"student_id":1,"course_id":1}' 'Student service stopped on port 8001' $r27
    $studentPid = Start-ServicePhp -cwd $StudentPath -port 8001

    Write-Section '503 simulation: Course service down'
    Stop-ByPort 8002
    Start-Sleep -Milliseconds 600
    $r28 = Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' '{"student_id":1,"course_id":1}'
    Save-Evidence '28-dependency-course-down.txt' 'Enroll while Course Service is down' 'POST' '/api/enrollments' '503 Service Unavailable' '{"student_id":1,"course_id":1}' 'Course service stopped on port 8002' $r28
    $coursePid = Start-ServicePhp -cwd $CoursePath -port 8002

    Write-Section '504 simulation: Slow dependency on port 8001'
    Stop-ByPort 8001
    Start-Sleep -Milliseconds 500
    $slowMockJob = Start-SlowStudentMock
    Start-Sleep -Milliseconds 500

    $r29 = Invoke-Curl 'POST' 'http://localhost:8003/api/enrollments' '{"student_id":1,"course_id":1}'
    Save-Evidence '29-timeout-504.txt' 'Enroll while Student Service is too slow' 'POST' '/api/enrollments' '504 Gateway Timeout' '{"student_id":1,"course_id":1}' 'Student service replaced with slow mock (>5s)' $r29

    if ($slowMockJob) {
        try { Stop-Job $slowMockJob -ErrorAction SilentlyContinue | Out-Null } catch {}
        try { Remove-Job $slowMockJob -Force -ErrorAction SilentlyContinue | Out-Null } catch {}
        $slowMockJob = $null
    }

    $studentPid = Start-ServicePhp -cwd $StudentPath -port 8001

    Write-Section 'Done'
    Write-Host "Evidence generated at: $EvidenceDir" -ForegroundColor Green
    Write-Host 'Files:' -ForegroundColor Green
    Get-ChildItem $EvidenceDir -Filter '*.txt' | Sort-Object Name | ForEach-Object { Write-Host " - $($_.Name)" }
}
catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    throw
}
finally {
    if ($slowMockJob) {
        try { Stop-Job $slowMockJob -ErrorAction SilentlyContinue | Out-Null } catch {}
        try { Remove-Job $slowMockJob -Force -ErrorAction SilentlyContinue | Out-Null } catch {}
    }

    if ((Get-PidsByPort 8001).Count -eq 0) {
        try { $null = Start-ServicePhp -cwd $StudentPath -port 8001 } catch {}
    }
    if ((Get-PidsByPort 8002).Count -eq 0) {
        try { $null = Start-ServicePhp -cwd $CoursePath -port 8002 } catch {}
    }
    if ((Get-PidsByPort 8003).Count -eq 0) {
        try { $null = Start-ServicePhp -cwd $EnrollPath -port 8003 } catch {}
    }
}
