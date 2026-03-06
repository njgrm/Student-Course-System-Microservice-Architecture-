<?php

namespace App\Livewire;

use App\Models\Student;
use Livewire\Component;

class StudentManager extends Component
{
    /** Form fields */
    public string $full_name = '';

    public string $email = '';

    public int|string $age = '';

    /** Editing state */
    public ?int $editingId = null;

    public bool $showForm = false;

    /** Flash message */
    public string $successMessage = '';

    protected function rules(): array
    {
        $uniqueRule = $this->editingId
            ? 'unique:students,email,'.$this->editingId
            : 'unique:students,email';

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $uniqueRule],
            'age' => ['required', 'integer', 'min:1'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $student = Student::findOrFail($id);
        $this->editingId = $student->id;
        $this->full_name = $student->full_name;
        $this->email = $student->email;
        $this->age = $student->age;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $student = Student::findOrFail($this->editingId);
            $student->update($validated);
            $this->successMessage = 'Student updated successfully.';
        } else {
            Student::create($validated);
            $this->successMessage = 'Student created successfully.';
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        Student::findOrFail($id)->delete();
        $this->successMessage = 'Student deleted successfully.';
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->full_name = '';
        $this->email = '';
        $this->age = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.student-manager', [
            'students' => Student::latest()->get(),
        ]);
    }
}
