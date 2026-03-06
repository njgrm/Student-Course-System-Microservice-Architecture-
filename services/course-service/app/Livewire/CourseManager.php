<?php

namespace App\Livewire;

use App\Models\Course;
use Livewire\Component;

class CourseManager extends Component
{
    /** Form fields */
    public string $name = '';

    public string $description = '';

    public int|string $credits = '';

    /** Editing state */
    public ?int $editingId = null;

    public bool $showForm = false;

    /** Flash message */
    public string $successMessage = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'credits' => ['required', 'integer', 'min:1'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $course = Course::findOrFail($id);
        $this->editingId = $course->id;
        $this->name = $course->name;
        $this->description = $course->description;
        $this->credits = $course->credits;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $course = Course::findOrFail($this->editingId);
            $course->update($validated);
            $this->successMessage = 'Course updated successfully.';
        } else {
            Course::create($validated);
            $this->successMessage = 'Course created successfully.';
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        Course::findOrFail($id)->delete();
        $this->successMessage = 'Course deleted successfully.';
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->credits = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.course-manager', [
            'courses' => Course::latest()->get(),
        ]);
    }
}
