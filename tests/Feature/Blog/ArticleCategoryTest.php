<?php

namespace Tests\Feature\Blog;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Models\ArticleCategory;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ArticleCategoryTest extends TestCase
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

    public function test_member_can_list_categories(): void
    {
        ArticleCategory::factory()->count(3)->create();

        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/article-categories');

        $response->assertOk();
        $response->assertJsonCount(3, 'categories');
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson('/api/v1/article-categories', [
            'name' => 'Devotionals',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('category.name', 'Devotionals');
        $this->assertDatabaseHas('article_categories', ['name' => 'Devotionals']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->adminUser())->putJson("/api/v1/article-categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertJsonPath('category.name', 'New Name');
    }

    public function test_admin_can_delete_category_and_unlink_articles(): void
    {
        $category = ArticleCategory::factory()->create();
        $article = Article::factory()->published()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->adminUser())->deleteJson("/api/v1/article-categories/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('article_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'category_id' => null]);
    }
}
