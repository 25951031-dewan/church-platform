<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchProfileTest extends TestCase
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

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_member_can_list_churches(): void
    {
        $user = $this->memberUser();
        Church::factory()->count(3)->create(['status' => 'approved']);
        Church::factory()->create(['status' => 'pending']);

        $this->actingAs($user)->getJson('/api/v1/churches')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_member_can_view_approved_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}")
            ->assertOk()
            ->assertJsonPath('church.name', $church->name);
    }

    public function test_directory_filters_by_city(): void
    {
        $user = $this->memberUser();
        Church::factory()->create(['status' => 'approved', 'city' => 'Dallas']);
        Church::factory()->create(['status' => 'approved', 'city' => 'Houston']);

        $this->actingAs($user)->getJson('/api/v1/churches?city=Dallas')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_verify_church(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/verify")
            ->assertOk()
            ->assertJsonPath('is_verified', true);

        $this->assertTrue($church->fresh()->is_verified);
    }

    public function test_admin_can_toggle_featured(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }
}
