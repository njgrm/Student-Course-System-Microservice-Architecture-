<?php

namespace App\Livewire;

use App\Models\Enrollment;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class EnrollmentManager extends Component
{
    /** Form fields */
    public int|string $student_id = '';

    public int|string $course_id = '';

    /** External data for dropdowns */
    public array $students = [];

    public array $courses = [];

    /** UI state */
    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'min:1'],
            'course_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function mount(): void
    {
        $this->loadExternalData();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->loadExternalData();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Verify student exists via Student Service
        try {
            $studentResponse = Http::get("http://localhost:8001/api/students/{$validated['student_id']}");
            if ($studentResponse->failed()) {
                $this->dispatch('show-toast', type: 'error', message: 'Student not found in Student Service.');

                return;
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Student Service is unavailable.');

            return;
        }

        // Verify course exists via Course Service
        try {
            $courseResponse = Http::get("http://localhost:8002/api/courses/{$validated['course_id']}");
            if ($courseResponse->failed()) {
                $this->dispatch('show-toast', type: 'error', message: 'Course not found in Course Service.');

                return;
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Course Service is unavailable.');

            return;
        }

        // Check for duplicate enrollment
        $exists = Enrollment::where('student_id', $validated['student_id'])
            ->where('course_id', $validated['course_id'])
            ->exists();

        if ($exists) {
            $this->dispatch('show-toast', type: 'error', message: 'Student is already enrolled in this course.');

            return;
        }

        Enrollment::create([
            'student_id' => $validated['student_id'],
            'course_id' => $validated['course_id'],
            'enrolled_at' => now(),
        ]);

        $this->dispatch('show-toast', type: 'success', message: 'Enrollment created successfully.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        Enrollment::findOrFail($id)->delete();
        $this->dispatch('show-toast', type: 'success', message: 'Enrollment deleted successfully.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->student_id = '';
        $this->course_id = '';
        $this->resetValidation();
    }

    private function loadExternalData(): void
    {
        // Fetch students from Student Service
        try {
            $response = Http::get('http://localhost:8001/api/students');
            $this->students = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $this->students = [];
        }

        // Fetch courses from Course Service
        try {
            $response = Http::get('http://localhost:8002/api/courses');
            $this->courses = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $this->courses = [];
        }
    }

    /**
     * Enrich enrollments with student/course names from other services.
     */
    private function enrichEnrollments($enrollments): array
    {
        // Build lookup maps from loaded data
        $studentMap = collect($this->students)->keyBy('id');
        $courseMap = collect($this->courses)->keyBy('id');

        return $enrollments->map(function ($enrollment) use ($studentMap, $courseMap) {
            $data = $enrollment->toArray();
            $data['student_name'] = $studentMap[$enrollment->student_id]['full_name'] ?? 'Unknown Student';
            $data['course_name'] = $courseMap[$enrollment->course_id]['name'] ?? 'Unknown Course';

            return $data;
        })->toArray();
    }

    public function render()
    {
        $this->loadExternalData();

        return view('livewire.enrollment-manager', [
            'enrollments' => $this->enrichEnrollments(Enrollment::latest()->get()),
        ]);
    }
}
