<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    public function test_can_list_published_church_pages(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);
        ChurchPage::factory()->count(2)->create(['church_id' => $church->id, 'is_published' => true]);
        ChurchPage::factory()->unpublished()->create(['church_id' => $church->id]);

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}/pages")
            ->assertOk()
            ->assertJsonCount(2, 'pages');
    }

    public function test_admin_can_create_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);

        $this->actingAs($admin)->postJson("/api/v1/churches/{$church->id}/pages", [
            'title' => 'Our Beliefs',
            'body' => 'We believe in the power of prayer.',
        ])->assertCreated()
            ->assertJsonPath('page.title', 'Our Beliefs');
    }

    public function test_admin_can_update_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $page = ChurchPage::factory()->create(['church_id' => $church->id]);

        $this->actingAs($admin)->putJson("/api/v1/churches/{$church->id}/pages/{$page->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('page.title', 'Updated Title');
    }

    public function test_admin_can_delete_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $page = ChurchPage::factory()->create(['church_id' => $church->id]);

        $this->actingAs($admin)->deleteJson("/api/v1/churches/{$church->id}/pages/{$page->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_pages', ['id' => $page->id]);
    }

    public function test_member_cannot_create_page_on_other_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/pages", [
            'title' => 'Unauthorized Page',
            'body' => 'Should not work.',
        ])->assertForbidden();
    }
}
