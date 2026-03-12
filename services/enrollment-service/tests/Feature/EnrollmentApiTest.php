<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_enrollments(): void
    {
        // Fake HTTP calls to other services (enrichment)
        Http::fake([
            'localhost:8001/api/students/*' => Http::response(['full_name' => 'Test Student'], 200),
            'localhost:8002/api/courses/*' => Http::response(['name' => 'Test Course'], 200),
        ]);

        Enrollment::factory()->count(2)->create();

        $response = $this->getJson('/api/enrollments');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_create_an_enrollment(): void
    {
        Http::fake([
            'localhost:8001/api/students/1' => Http::response(['id' => 1, 'full_name' => 'Alice'], 200),
            'localhost:8002/api/courses/1' => Http::response(['id' => 1, 'name' => 'Intro CS'], 200),
        ]);

        $response = $this->postJson('/api/enrollments', [
            'student_id' => 1,
            'course_id' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['student_id' => 1, 'course_id' => 1]);

        $this->assertDatabaseHas('enrollments', ['student_id' => 1, 'course_id' => 1]);
    }

    public function test_cannot_create_duplicate_enrollment(): void
    {
        Http::fake([
            'localhost:8001/api/students/1' => Http::response(['id' => 1, 'full_name' => 'Alice'], 200),
            'localhost:8002/api/courses/1' => Http::response(['id' => 1, 'name' => 'Intro CS'], 200),
        ]);

        Enrollment::factory()->create(['student_id' => 1, 'course_id' => 1]);

        $response = $this->postJson('/api/enrollments', [
            'student_id' => 1,
            'course_id' => 1,
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['error' => 'DUPLICATE_ENROLLMENT']);
    }

    public function test_cannot_enroll_nonexistent_student(): void
    {
        Http::fake([
            'localhost:8001/api/students/999' => Http::response([], 404),
        ]);

        $response = $this->postJson('/api/enrollments', [
            'student_id' => 999,
            'course_id' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'NOT_FOUND']);
    }

    public function test_cannot_enroll_nonexistent_course(): void
    {
        Http::fake([
            'localhost:8001/api/students/1' => Http::response(['id' => 1], 200),
            'localhost:8002/api/courses/999' => Http::response([], 404),
        ]);

        $response = $this->postJson('/api/enrollments', [
            'student_id' => 1,
            'course_id' => 999,
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'NOT_FOUND']);
    }

    public function test_can_show_an_enrollment(): void
    {
        Http::fake([
            'localhost:8001/api/students/*' => Http::response(['full_name' => 'Alice'], 200),
            'localhost:8002/api/courses/*' => Http::response(['name' => 'Intro CS'], 200),
        ]);

        $enrollment = Enrollment::factory()->create();

        $response = $this->getJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $enrollment->id]);
    }

    public function test_can_delete_an_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();

        $response = $this->deleteJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Enrollment deleted successfully.']);

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_cascade_delete_by_student(): void
    {
        Enrollment::factory()->create(['student_id' => 5, 'course_id' => 1]);
        Enrollment::factory()->create(['student_id' => 5, 'course_id' => 2]);
        Enrollment::factory()->create(['student_id' => 6, 'course_id' => 1]);

        $response = $this->deleteJson('/api/enrollments/student/5');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('enrollments', ['student_id' => 5]);
        $this->assertDatabaseHas('enrollments', ['student_id' => 6]);
    }

    public function test_cascade_delete_by_course(): void
    {
        Enrollment::factory()->create(['student_id' => 1, 'course_id' => 10]);
        Enrollment::factory()->create(['student_id' => 2, 'course_id' => 10]);
        Enrollment::factory()->create(['student_id' => 1, 'course_id' => 11]);

        $response = $this->deleteJson('/api/enrollments/course/10');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('enrollments', ['course_id' => 10]);
        $this->assertDatabaseHas('enrollments', ['course_id' => 11]);
    }

    public function test_cannot_create_enrollment_without_required_fields(): void
    {
        $response = $this->postJson('/api/enrollments', []);

        $response->assertStatus(400)
            ->assertJsonFragment(['error' => 'VALIDATION_ERROR'])
            ->assertJsonStructure(['error', 'message', 'details']);
    }

    public function test_homepage_redirects_to_gateway(): void
    {
        Http::fake([
            'localhost:8001/api/students' => Http::response([], 200),
            'localhost:8002/api/courses' => Http::response([], 200),
        ]);

        $response = $this->get('/');

        $response->assertStatus(302);
    }
}
