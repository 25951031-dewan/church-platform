<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BookDownloadTest extends TestCase
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

    private function guestUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'guest')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_can_track_download(): void
    {
        $user = $this->memberUser();
        $book = Book::factory()->withPdf()->create(['download_count' => 10]);

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertOk();
        $response->assertJsonPath('download_count', 11);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'download_count' => 11]);
    }

    public function test_cannot_download_without_permission(): void
    {
        $user = $this->guestUser();
        $book = Book::factory()->withPdf()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertForbidden();
    }

    public function test_cannot_download_inactive_book(): void
    {
        $user = $this->memberUser();
        $book = Book::factory()->inactive()->withPdf()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertForbidden();
    }
}
