<?php

namespace Tests\Feature\Comments;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Common\Comments\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function seedTimelinePermissions(): void
    {
        foreach ([
            'comments.create' => 'Post Comments',
            'comments.update' => 'Edit Own Comments',
            'comments.delete_any' => 'Delete Any Comment',
            'comments.moderate' => 'Moderate Comments',
            'reactions.create' => 'React to Content',
        ] as $name => $display) {
            Permission::create([
                'name' => $name,
                'display_name' => $display,
                'group' => explode('.', $name)[0],
            ]);
        }
    }

    private function createMember(): User
    {
        $this->seedTimelinePermissions();
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach(
            Permission::whereIn('name', ['comments.create', 'comments.update', 'reactions.create'])->pluck('id')
        );

        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_comment_has_nested_replies(): void
    {
        $user = User::factory()->create();

        $parent = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Great post!',
        ]);

        $reply = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Thanks!',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals(1, $parent->replies()->count());
        $this->assertEquals($parent->id, $reply->parent->id);
    }

    public function test_comment_has_reactions(): void
    {
        $user = User::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Amen!',
        ]);

        $comment->reactions()->create([
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        $this->assertEquals(1, $comment->reactions()->count());
        $this->assertEquals(['like' => 1], $comment->reactionCounts());
    }

    public function test_deleting_parent_deletes_replies(): void
    {
        $user = User::factory()->create();

        $parent = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Parent',
        ]);

        Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Reply',
            'parent_id' => $parent->id,
        ]);

        $parent->delete();
        $this->assertEquals(0, Comment::count());
    }
}
