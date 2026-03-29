<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Library\Database\Seeders\LibraryPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        $user->clearPermissionCache();
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_can_list_active_books(): void
    {
        $user = $this->memberUser();
        Book::factory()->count(3)->create();
        Book::factory()->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_show_book_detail(): void
    {
        $user = $this->memberUser();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonPath('book.id', $book->id);
    }

    public function test_show_increments_view_count(): void
    {
        $user = $this->memberUser();
        $book = Book::factory()->create(['view_count' => 5]);

        $this->actingAs($user)->getJson("/api/v1/books/{$book->id}");

        $this->assertDatabaseHas('books', ['id' => $book->id, 'view_count' => 6]);
    }

    public function test_can_create_book(): void
    {
        $user = $this->adminUser();
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/books', [
            'title' => 'Test Book',
            'author' => 'John Doe',
            'description' => 'A test book',
            'category_id' => $category->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('book.title', 'Test Book');
        $this->assertDatabaseHas('books', ['title' => 'Test Book', 'uploaded_by' => $user->id]);
    }

    public function test_can_update_book(): void
    {
        $user = $this->adminUser();
        $book = Book::factory()->create(['uploaded_by' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Updated Title',
            'author' => $book->author,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
    }

    public function test_can_delete_book(): void
    {
        $user = $this->adminUser();
        $book = Book::factory()->create(['uploaded_by' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_can_filter_books_by_category(): void
    {
        $user = $this->memberUser();
        $category = BookCategory::factory()->create();
        Book::factory()->count(2)->create(['category_id' => $category->id]);
        Book::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/books?category_id={$category->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }
}
