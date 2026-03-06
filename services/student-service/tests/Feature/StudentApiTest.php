<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_students(): void
    {
        Student::factory()->count(3)->create();

        $response = $this->getJson('/api/students');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_a_student(): void
    {
        $payload = [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'age' => 22,
        ];

        $response = $this->postJson('/api/students', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['full_name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->assertDatabaseHas('students', ['email' => 'jane@example.com']);
    }

    public function test_cannot_create_student_without_required_fields(): void
    {
        $response = $this->postJson('/api/students', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'age']);
    }

    public function test_cannot_create_student_with_duplicate_email(): void
    {
        Student::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/students', [
            'full_name' => 'Another Person',
            'email' => 'duplicate@example.com',
            'age' => 25,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_show_a_student(): void
    {
        $student = Student::factory()->create();

        $response = $this->getJson("/api/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $student->id]);
    }

    public function test_show_returns_404_for_nonexistent_student(): void
    {
        $response = $this->getJson('/api/students/999');

        $response->assertStatus(404);
    }

    public function test_can_update_a_student(): void
    {
        $student = Student::factory()->create();

        $response = $this->putJson("/api/students/{$student->id}", [
            'full_name' => 'Updated Name',
            'email' => 'updated@example.com',
            'age' => 30,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['full_name' => 'Updated Name']);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'full_name' => 'Updated Name']);
    }

    public function test_can_delete_a_student(): void
    {
        $student = Student::factory()->create();

        $response = $this->deleteJson("/api/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Student deleted successfully.']);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_homepage_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
