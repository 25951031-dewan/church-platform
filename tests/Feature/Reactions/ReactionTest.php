<?php
// tests/Feature/Reactions/ReactionTest.php

namespace Tests\Feature\Reactions;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Common\Reactions\Models\Reaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberUser(): User
    {
        $perm = Permission::create([
            'name' => 'reactions.create',
            'display_name' => 'React to Content',
            'group' => 'reactions',
        ]);
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_user_can_toggle_reaction(): void
    {
        $user = $this->createMemberUser();

        // We need a reactable model — we'll use Post once it exists.
        // For now, test the model directly via DB insert.
        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'like',
        ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        // Toggle off — delete
        $reaction->delete();
        $this->assertDatabaseMissing('reactions', ['id' => $reaction->id]);
    }

    public function test_user_cannot_double_react_same_content(): void
    {
        $user = $this->createMemberUser();

        Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'like',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'pray', // Different type, same content — still blocked by unique constraint
        ]);
    }

    public function test_reaction_types_are_valid(): void
    {
        $this->assertEquals(
            ['like', 'pray', 'amen', 'love', 'celebrate'],
            Reaction::TYPES
        );
    }
}
