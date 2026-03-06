<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_courses(): void
    {
        Course::factory()->count(3)->create();

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_a_course(): void
    {
        $payload = [
            'name' => 'Advanced PHP',
            'description' => 'Learn advanced PHP concepts.',
            'credits' => 4,
        ];

        $response = $this->postJson('/api/courses', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Advanced PHP']);

        $this->assertDatabaseHas('courses', ['name' => 'Advanced PHP']);
    }

    public function test_cannot_create_course_without_required_fields(): void
    {
        $response = $this->postJson('/api/courses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'credits']);
    }

    public function test_can_show_a_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $course->id]);
    }

    public function test_show_returns_404_for_nonexistent_course(): void
    {
        $response = $this->getJson('/api/courses/999');

        $response->assertStatus(404);
    }

    public function test_can_update_a_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->putJson("/api/courses/{$course->id}", [
            'name' => 'Updated Course',
            'description' => 'Updated description.',
            'credits' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Course']);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Updated Course']);
    }

    public function test_can_delete_a_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Course deleted successfully.']);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_homepage_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
