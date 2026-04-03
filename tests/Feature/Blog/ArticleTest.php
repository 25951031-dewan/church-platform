<?php

namespace Tests\Feature\Blog;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Blog\Database\Seeders\BlogPermissionSeeder::class);
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

    private function moderatorUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'moderator')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_guest_can_list_published_articles(): void
    {
        Article::factory()->published()->count(3)->create();
        Article::factory()->create(); // draft — should not appear

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk();
        $response->assertJsonCount(3, 'pagination.data');
    }

    public function test_member_can_list_articles(): void
    {
        Article::factory()->published()->count(2)->create();

        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/articles');

        $response->assertOk();
        $response->assertJsonCount(2, 'pagination.data');
    }

    public function test_admin_can_list_draft_articles(): void
    {
        $admin = $this->adminUser();
        Article::factory()->create(['author_id' => $admin->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/articles?status=draft');

        $response->assertOk();
        $response->assertJsonCount(1, 'pagination.data');
    }

    public function test_guest_can_view_published_article(): void
    {
        $article = Article::factory()->published()->create(['view_count' => 0]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertOk();
        $response->assertJsonPath('article.slug', $article->slug);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'view_count' => 1]);
    }

    public function test_guest_cannot_view_draft_article(): void
    {
        $article = Article::factory()->create(['status' => 'draft']);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertForbidden();
    }

    public function test_author_can_create_draft_article(): void
    {
        $author = $this->moderatorUser();

        $response = $this->actingAs($author)->postJson('/api/v1/articles', [
            'title' => 'My Draft Article',
            'status' => 'draft',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('article.status', 'draft');
        $this->assertDatabaseHas('articles', ['title' => 'My Draft Article', 'author_id' => $author->id]);
    }

    public function test_author_cannot_publish_without_permission(): void
    {
        // Moderator has blog.create but NOT blog.publish
        $author = $this->moderatorUser();

        $response = $this->actingAs($author)->postJson('/api/v1/articles', [
            'title' => 'Trying to Publish',
            'status' => 'published',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_article(): void
    {
        $admin = $this->adminUser();
        $article = Article::factory()->published()->create();

        $response = $this->actingAs($admin)->putJson("/api/v1/articles/{$article->slug}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk();
        $response->assertJsonPath('article.title', 'Updated Title');
    }

    public function test_admin_can_delete_article(): void
    {
        $admin = $this->adminUser();
        $article = Article::factory()->published()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/v1/articles/{$article->slug}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
