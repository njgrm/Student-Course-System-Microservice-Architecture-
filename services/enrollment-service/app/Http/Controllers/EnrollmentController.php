<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Enrollment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class EnrollmentController extends Controller
{
    private const STUDENT_SERVICE = 'http://localhost:8001/api/students';

    private const COURSE_SERVICE = 'http://localhost:8002/api/courses';

    /** HTTP timeout in seconds for inter-service calls. */
    private const HTTP_TIMEOUT = 5;

    /**
     * Display a listing of all enrollments, enriched with student/course names.
     */
    public function index(): JsonResponse
    {
        $enrollments = Enrollment::all()->map(fn ($enrollment) => $this->enrichEnrollment($enrollment));

        return response()->json($enrollments);
    }

    /**
     * Store a newly created enrollment after verifying student and course exist.
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verify student exists via Student Service
        try {
            $studentResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::STUDENT_SERVICE.'/'.$validated['student_id']);

            if ($studentResponse->failed()) {
                return response()->json([
                    'error'   => 'NOT_FOUND',
                    'message' => 'Student not found in Student Service.',
                ], 404);
            }
        } catch (ConnectionException $e) {
            $code = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Timeout') ? 504 : 503;

            return response()->json([
                'error'   => $code === 504 ? 'GATEWAY_TIMEOUT' : 'SERVICE_UNAVAILABLE',
                'message' => $code === 504
                    ? 'Student Service timed out.'
                    : 'Student Service is unavailable.',
            ], $code);
        }

        // Verify course exists via Course Service
        try {
            $courseResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::COURSE_SERVICE.'/'.$validated['course_id']);

            if ($courseResponse->failed()) {
                return response()->json([
                    'error'   => 'NOT_FOUND',
                    'message' => 'Course not found in Course Service.',
                ], 404);
            }
        } catch (ConnectionException $e) {
            $code = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Timeout') ? 504 : 503;

            return response()->json([
                'error'   => $code === 504 ? 'GATEWAY_TIMEOUT' : 'SERVICE_UNAVAILABLE',
                'message' => $code === 504
                    ? 'Course Service timed out.'
                    : 'Course Service is unavailable.',
            ], $code);
        }

        // Check for duplicate enrollment
        $exists = Enrollment::where('student_id', $validated['student_id'])
            ->where('course_id', $validated['course_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error'   => 'DUPLICATE_ENROLLMENT',
                'message' => 'Student is already enrolled in this course.',
            ], 409);
        }

        $enrollment = Enrollment::create([
            'student_id' => $validated['student_id'],
            'course_id' => $validated['course_id'],
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'id'      => $enrollment->id,
            'message' => 'Enrollment created successfully.',
        ], 201);
    }

    /**
     * Display the specified enrollment, enriched with student/course names.
     */
    public function show(Enrollment $enrollment): JsonResponse
    {
        return response()->json($this->enrichEnrollment($enrollment));
    }

    /**
     * Get all enrollments for a specific student.
     */
    public function getByStudent(int $studentId): JsonResponse
    {
        // Verify student exists
        try {
            $studentResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::STUDENT_SERVICE.'/'.$studentId);

            if ($studentResponse->failed()) {
                return response()->json([
                    'error'   => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ], 404);
            }
        } catch (ConnectionException $e) {
            $code = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Timeout') ? 504 : 503;

            return response()->json([
                'error'   => $code === 504 ? 'GATEWAY_TIMEOUT' : 'SERVICE_UNAVAILABLE',
                'message' => $code === 504
                    ? 'Student Service timed out.'
                    : 'Student Service is unavailable.',
            ], $code);
        }

        $enrollments = Enrollment::where('student_id', $studentId)
            ->get()
            ->map(fn ($enrollment) => $this->enrichEnrollment($enrollment));

        return response()->json($enrollments);
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $enrollment->delete();

        return response()->json(['message' => 'Enrollment deleted successfully.']);
    }

    /**
     * Delete all enrollments for a given student (called when student is deleted).
     */
    public function destroyByStudent(int $studentId): JsonResponse
    {
        Enrollment::where('student_id', $studentId)->delete();

        return response()->json(['message' => 'Enrollments for student deleted.']);
    }

    /**
     * Delete all enrollments for a given course (called when course is deleted).
     */
    public function destroyByCourse(int $courseId): JsonResponse
    {
        Enrollment::where('course_id', $courseId)->delete();

        return response()->json(['message' => 'Enrollments for course deleted.']);
    }

    /**
     * Enrich an enrollment with student name and course name from other services.
     */
    private function enrichEnrollment(Enrollment $enrollment): array
    {
        $data = $enrollment->toArray();

        // Fetch student name
        try {
            $studentResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::STUDENT_SERVICE.'/'.$enrollment->student_id);
            $data['student_name'] = $studentResponse->successful()
                ? $studentResponse->json('full_name')
                : 'Unknown Student';
        } catch (\Exception $e) {
            $data['student_name'] = 'Student Service Unavailable';
        }

        // Fetch course name
        try {
            $courseResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::COURSE_SERVICE.'/'.$enrollment->course_id);
            $data['course_name'] = $courseResponse->successful()
                ? $courseResponse->json('name')
                : 'Unknown Course';
        } catch (\Exception $e) {
            $data['course_name'] = 'Course Service Unavailable';
        }

        return $data;
    }
}
