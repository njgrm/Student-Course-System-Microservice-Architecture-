@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM Lab 2 one-click runner (CMD + curl.exe)
REM Usage:
REM   tests\run-all-curl-tests.cmd
REM   tests\run-all-curl-tests.cmd /freshseed

if /I "%~1"=="/help" goto :usage
if /I "%~1"=="-h" goto :usage

set "ROOT=%~dp0.."
for %%I in ("%ROOT%") do set "ROOT=%%~fI"
set "EVIDENCE=%ROOT%\docs\evidence"
set "TMPJSON=%TEMP%\lab2_body_%RANDOM%.json"
set "TMPBODY=%TEMP%\lab2_resp_body_%RANDOM%.txt"
set "TMPSTAT=%TEMP%\lab2_resp_status_%RANDOM%.txt"

set "STUDENT_DIR=%ROOT%\services\student-service"
set "COURSE_DIR=%ROOT%\services\course-service"
set "ENROLL_DIR=%ROOT%\services\enrollment-service"

if not exist "%EVIDENCE%" mkdir "%EVIDENCE%"

echo.
echo ========================================
echo  Lab 2 Curl Runner ^(CMD + curl.exe^)
echo ========================================
echo.

if /I "%~1"=="/freshseed" (
  echo [setup] Running migrate:fresh --seed on all services...
  pushd "%STUDENT_DIR%" && php artisan migrate:fresh --seed --force >nul && popd
  pushd "%COURSE_DIR%" && php artisan migrate:fresh --seed --force >nul && popd
  pushd "%ENROLL_DIR%" && php artisan migrate:fresh --seed --force >nul && popd
  echo [setup] Fresh seed complete.
)

call :ensure_service 8001 "%STUDENT_DIR%" "student-service"
call :ensure_service 8002 "%COURSE_DIR%" "course-service"
call :ensure_service 8003 "%ENROLL_DIR%" "enrollment-service"

for /f %%T in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMddHHmmss"') do set "STAMP=%%T"
set "UNIQ_EMAIL=lab2.student.%STAMP%@example.com"
set "UNIQ_COURSE=Lab2 Course %STAMP%"

echo.
echo [1] Happy path and CRUD

> "%TMPJSON%" echo {"full_name":"Lab2 Student","email":"%UNIQ_EMAIL%","age":22}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "01-create-student.txt" "Create a new student" "POST" "/api/students" "{""full_name"":""Lab2 Student"",""email"":""%UNIQ_EMAIL%"",""age"":22}" "201 Created" ""

> "%TMPJSON%" echo {"name":"%UNIQ_COURSE%","description":"Lab2 generated course","credits":3}
call :curl_json POST "http://localhost:8002/api/courses"
call :write_evidence "02-create-course.txt" "Create a new course" "POST" "/api/courses" "{""name"":""%UNIQ_COURSE%"",""description"":""Lab2 generated course"",""credits"":3}" "201 Created" ""

> "%TMPJSON%" echo {"student_id":5,"course_id":5}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "03-create-enrollment.txt" "Enroll student 5 in course 5" "POST" "/api/enrollments" "{""student_id"":5,""course_id"":5}" "201 Created" ""

call :curl_nojson GET "http://localhost:8001/api/students"
call :write_evidence "04-list-students.txt" "List all students" "GET" "/api/students" "" "200 OK" ""

call :curl_nojson GET "http://localhost:8002/api/courses"
call :write_evidence "05-list-courses.txt" "List all courses" "GET" "/api/courses" "" "200 OK" ""

call :curl_nojson GET "http://localhost:8003/api/enrollments"
call :write_evidence "06-list-enrollments.txt" "List all enrollments" "GET" "/api/enrollments" "" "200 OK" ""

call :curl_nojson GET "http://localhost:8001/api/students/1"
call :write_evidence "07-get-student.txt" "Get student id=1" "GET" "/api/students/1" "" "200 OK" ""

call :curl_nojson GET "http://localhost:8002/api/courses/1"
call :write_evidence "08-get-course.txt" "Get course id=1" "GET" "/api/courses/1" "" "200 OK" ""

> "%TMPJSON%" echo {"full_name":"Lab2 Student Updated","email":"%UNIQ_EMAIL%","age":23}
call :curl_json PUT "http://localhost:8001/api/students/1"
call :write_evidence "09-update-student.txt" "Update student id=1" "PUT" "/api/students/1" "{""full_name"":""Lab2 Student Updated"",""email"":""%UNIQ_EMAIL%"",""age"":23}" "200 OK" ""

> "%TMPJSON%" echo {"name":"%UNIQ_COURSE% Updated","description":"Updated","credits":4}
call :curl_json PUT "http://localhost:8002/api/courses/1"
call :write_evidence "10-update-course.txt" "Update course id=1" "PUT" "/api/courses/1" "{""name"":""%UNIQ_COURSE% Updated"",""description"":""Updated"",""credits"":4}" "200 OK" ""

echo.
echo [2] Validation errors

> "%TMPJSON%" echo {"email":"no-name@test.com","age":20}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "11-missing-field.txt" "Create student without full_name" "POST" "/api/students" "{""email"":""no-name@test.com"",""age"":20}" "400 Validation Error" ""

> "%TMPJSON%" echo {"full_name":"Bad Email","email":"not-an-email","age":20}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "12-invalid-email.txt" "Create student with invalid email" "POST" "/api/students" "{""full_name"":""Bad Email"",""email"":""not-an-email"",""age"":20}" "400 Validation Error" ""

> "%TMPJSON%" echo {"full_name":"Duplicate","email":"%UNIQ_EMAIL%","age":20}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "13-duplicate-email.txt" "Create student with duplicate email" "POST" "/api/students" "{""full_name"":""Duplicate"",""email"":""%UNIQ_EMAIL%"",""age"":20}" "400 Validation Error" ""

> "%TMPJSON%" echo {"full_name":"Negative Age","email":"neg@test.com","age":-5}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "14-negative-age.txt" "Create student with negative age" "POST" "/api/students" "{""full_name"":""Negative Age"",""email"":""neg@test.com"",""age"":-5}" "400 Validation Error" ""

> "%TMPJSON%" echo {}
call :curl_json POST "http://localhost:8001/api/students"
call :write_evidence "15-empty-body.txt" "Create student with empty body" "POST" "/api/students" "{}" "400 Validation Error" ""

> "%TMPJSON%" echo {"name":"Bad Credits","description":"Testing","credits":0}
call :curl_json POST "http://localhost:8002/api/courses"
call :write_evidence "16-invalid-credits.txt" "Create course with credits=0" "POST" "/api/courses" "{""name"":""Bad Credits"",""description"":""Testing"",""credits"":0}" "400 Validation Error" ""

echo.
echo [3] Not found and conflict

call :curl_nojson GET "http://localhost:8001/api/students/99999"
call :write_evidence "17-student-not-found.txt" "Get missing student" "GET" "/api/students/99999" "" "404 Not Found" ""

call :curl_nojson GET "http://localhost:8002/api/courses/99999"
call :write_evidence "18-course-not-found.txt" "Get missing course" "GET" "/api/courses/99999" "" "404 Not Found" ""

> "%TMPJSON%" echo {"student_id":99999,"course_id":1}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "19-enroll-bad-student.txt" "Enroll non-existent student" "POST" "/api/enrollments" "{""student_id"":99999,""course_id"":1}" "404 Not Found" ""

> "%TMPJSON%" echo {"student_id":1,"course_id":99999}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "20-enroll-bad-course.txt" "Enroll non-existent course" "POST" "/api/enrollments" "{""student_id"":1,""course_id"":99999}" "404 Not Found" ""

call :curl_nojson DELETE "http://localhost:8001/api/students/99999"
call :write_evidence "21-delete-missing-student.txt" "Delete missing student" "DELETE" "/api/students/99999" "" "404 Not Found" ""

> "%TMPJSON%" echo {"student_id":1,"course_id":1}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "22-duplicate-enrollment.txt" "Duplicate enrollment attempt" "POST" "/api/enrollments" "{""student_id"":1,""course_id"":1}" "409 Conflict" ""

echo.
echo [4] Basic delete checks

call :curl_nojson DELETE "http://localhost:8003/api/enrollments/1"
call :write_evidence "24-delete-enrollment.txt" "Delete enrollment id=1" "DELETE" "/api/enrollments/1" "" "200 OK" ""

call :curl_nojson DELETE "http://localhost:8002/api/courses/3"
call :write_evidence "25-delete-course.txt" "Delete course id=3" "DELETE" "/api/courses/3" "" "200 OK" ""

call :curl_nojson DELETE "http://localhost:8001/api/students/3"
call :write_evidence "26-delete-student.txt" "Delete student id=3" "DELETE" "/api/students/3" "" "200 OK" ""

echo.
echo [5] 503 and 504 simulations

call :stop_port 8001
timeout /t 1 /nobreak >nul
> "%TMPJSON%" echo {"student_id":1,"course_id":1}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "27-service-unavailable-503.txt" "Enroll while Student Service is down" "POST" "/api/enrollments" "{""student_id"":1,""course_id"":1}" "503 Service Unavailable" "Student service stopped on port 8001"
call :ensure_service 8001 "%STUDENT_DIR%" "student-service"

call :stop_port 8002
timeout /t 1 /nobreak >nul
> "%TMPJSON%" echo {"student_id":1,"course_id":1}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "28-dependency-course-down.txt" "Enroll while Course Service is down" "POST" "/api/enrollments" "{""student_id"":1,""course_id"":1}" "503 Service Unavailable" "Course service stopped on port 8002"
call :ensure_service 8002 "%COURSE_DIR%" "course-service"

call :stop_port 8001
powershell -NoProfile -Command "$listener=[System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Parse('127.0.0.1'),8001);$listener.Start();$client=$listener.AcceptTcpClient();$stream=$client.GetStream();$buf=New-Object byte[] 4096;[void]$stream.Read($buf,0,$buf.Length);Start-Sleep -Seconds 10;$body='{""id"":1,""full_name"":""Slow Mock""}';$resp='HTTP/1.1 200 OK`r`nContent-Type: application/json`r`nContent-Length: '+$body.Length+'`r`nConnection: close`r`n`r`n'+$body;$bytes=[System.Text.Encoding]::ASCII.GetBytes($resp);$stream.Write($bytes,0,$bytes.Length);$stream.Flush();$client.Close();$listener.Stop()" >nul 2>&1
timeout /t 1 /nobreak >nul
> "%TMPJSON%" echo {"student_id":1,"course_id":1}
call :curl_json POST "http://localhost:8003/api/enrollments"
call :write_evidence "29-timeout-504.txt" "Enroll while Student Service is too slow" "POST" "/api/enrollments" "{""student_id"":1,""course_id"":1}" "504 Gateway Timeout" "Student service replaced with slow mock on port 8001"
call :ensure_service 8001 "%STUDENT_DIR%" "student-service"

del /q "%TMPJSON%" >nul 2>&1
del /q "%TMPBODY%" >nul 2>&1
del /q "%TMPSTAT%" >nul 2>&1

echo.
echo ========================================
echo Done. Evidence generated in docs\evidence
echo ========================================
dir /b "%EVIDENCE%\*.txt"
goto :eof

:curl_json
set "METHOD=%~1"
set "URL=%~2"
curl.exe -s -o "%TMPBODY%" -w "%%{http_code}" -X %METHOD% "%URL%" -H "Accept: application/json" -H "Content-Type: application/json" -d "@%TMPJSON%" > "%TMPSTAT%"
set /p RESP_STATUS=<"%TMPSTAT%"
set "RESP_BODY="
for /f "usebackq delims=" %%L in ("%TMPBODY%") do set "RESP_BODY=%%L"
if not defined RESP_BODY set "RESP_BODY=(empty)"
echo [%RESP_STATUS%] %METHOD% %URL%
goto :eof

:curl_nojson
set "METHOD=%~1"
set "URL=%~2"
curl.exe -s -o "%TMPBODY%" -w "%%{http_code}" -X %METHOD% "%URL%" -H "Accept: application/json" > "%TMPSTAT%"
set /p RESP_STATUS=<"%TMPSTAT%"
set "RESP_BODY="
for /f "usebackq delims=" %%L in ("%TMPBODY%") do set "RESP_BODY=%%L"
if not defined RESP_BODY set "RESP_BODY=(empty)"
echo [%RESP_STATUS%] %METHOD% %URL%
goto :eof

:write_evidence
set "FILE=%~1"
set "TEST=%~2"
set "METHOD=%~3"
set "ENDPOINT=%~4"
set "BODY=%~5"
set "EXPECTED=%~6"
set "PRECOND=%~7"
(
  echo TEST: %TEST%
  echo METHOD: %METHOD% %ENDPOINT%
  if not "%BODY%"=="" echo BODY: %BODY%
  if not "%PRECOND%"=="" echo PRECONDITION: %PRECOND%
  echo EXPECTED: %EXPECTED%
  echo.
  echo RESPONSE:
  echo %RESP_BODY%
  echo HTTP_STATUS: %RESP_STATUS%
) > "%EVIDENCE%\%FILE%"
goto :eof

:ensure_service
set "PORT=%~1"
set "SVC_PATH=%~2"
set "SVC_NAME=%~3"
call :is_port_listening %PORT%
if "%PORT_LISTENING%"=="1" goto :eof
echo [start] %SVC_NAME% on %PORT%
start "%SVC_NAME%" /min cmd /c "cd /d %SVC_PATH% && php artisan serve --port=%PORT%"
call :wait_port %PORT% 25
if not "%PORT_LISTENING%"=="1" (
  echo ERROR: %SVC_NAME% failed to start on port %PORT%
  exit /b 1
)
goto :eof

:is_port_listening
set "PORT=%~1"
set "PORT_LISTENING=0"
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R ":%PORT% .*LISTENING"') do (
  set "PORT_LISTENING=1"
  goto :eof
)
goto :eof

:wait_port
set "PORT=%~1"
set "MAXWAIT=%~2"
set /a C=0
:wait_port_loop
call :is_port_listening %PORT%
if "%PORT_LISTENING%"=="1" goto :eof
set /a C+=1
if %C% GEQ %MAXWAIT% goto :eof
timeout /t 1 /nobreak >nul
goto :wait_port_loop

:stop_port
set "PORT=%~1"
echo [stop] port %PORT%
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R ":%PORT% .*LISTENING"') do taskkill /f /pid %%P >nul 2>&1
goto :eof

:usage
echo Usage:
echo   tests\run-all-curl-tests.cmd
echo   tests\run-all-curl-tests.cmd /freshseed
goto :eof
