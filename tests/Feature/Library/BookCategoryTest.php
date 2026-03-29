<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BookCategoryTest extends TestCase
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

    public function test_can_list_categories(): void
    {
        $user = $this->memberUser();
        BookCategory::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/book-categories');

        $response->assertOk();
        $response->assertJsonCount(3, 'categories');
    }

    public function test_can_create_category(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->postJson('/api/v1/book-categories', [
            'name' => 'Theology',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('book_categories', ['name' => 'Theology']);
    }

    public function test_can_create_child_category(): void
    {
        $user = $this->adminUser();
        $parent = BookCategory::factory()->create(['name' => 'Theology']);

        $response = $this->actingAs($user)->postJson('/api/v1/book-categories', [
            'name' => 'Systematic Theology',
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Systematic Theology',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_delete_category_unlinks_books(): void
    {
        $user = $this->adminUser();
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/book-categories/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'category_id' => null]);
    }
}
