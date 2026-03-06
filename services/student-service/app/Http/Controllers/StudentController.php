<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class StudentController extends Controller
{
    /**
     * Display a listing of all students.
     */
    public function index(): JsonResponse
    {
        return response()->json(Student::all());
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = Student::create($request->validated());

        return response()->json($student, 201);
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        return response()->json($student);
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());

        return response()->json($student);
    }

    /**
     * Remove the specified student and notify Enrollment Service to cascade.
     */
    public function destroy(Student $student): JsonResponse
    {
        $studentId = $student->id;
        $student->delete();

        // Notify Enrollment Service to remove this student's enrollments
        try {
            Http::delete("http://localhost:8003/api/enrollments/student/{$studentId}");
        } catch (\Exception $e) {
            // Log but don't fail — enrollment cleanup is best-effort
        }

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
