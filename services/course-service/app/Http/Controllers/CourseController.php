<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class CourseController extends Controller
{
    /**
     * Display a listing of all courses.
     */
    public function index(): JsonResponse
    {
        return response()->json(Course::all());
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());

        return response()->json([
            'id'      => $course->id,
            'message' => 'Course created successfully.',
        ], 201);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course): JsonResponse
    {
        return response()->json($course);
    }

    /**
     * Update the specified course.
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course->update($request->validated());

        return response()->json([
            'id'      => $course->id,
            'message' => 'Course updated successfully.',
        ]);
    }

    /**
     * Remove the specified course and notify Enrollment Service to cascade.
     */
    public function destroy(Course $course): JsonResponse
    {
        $courseId = $course->id;
        $course->delete();

        // Notify Enrollment Service to remove this course's enrollments
        try {
            Http::delete("http://localhost:8003/api/enrollments/course/{$courseId}");
        } catch (\Exception $e) {
            // Log but don't fail — enrollment cleanup is best-effort
        }

        return response()->json(['message' => 'Course deleted successfully.']);
    }
}
