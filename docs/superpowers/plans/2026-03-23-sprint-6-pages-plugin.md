# Sprint 6 — Church Pages Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Church Pages plugin — Facebook-style organizational pages for church ministries, campuses, and departments — with CRUD, follow/unfollow, member role management, and a React frontend.

**Architecture:** New `Entity` plugin owns `church_entities` (type='page'|'community') and `entity_members` tables. Sprint 6 implements type='page' only; type='community' rows arrive in Sprint 8. Plugin follows the established ServiceProvider Router pattern — `$router->middleware('api')->prefix('api')->group(...)`.

**Tech Stack:** Laravel 11 · PestPHP · React 18 · TypeScript · Tailwind CSS · Axios

---

## File Map

### New files
| File | Responsibility |
|---|---|
| `plugins/Entity/EntityServiceProvider.php` | Boot routes + migrations |
| `plugins/Entity/plugin.json` | Plugin manifest |
| `plugins/Entity/routes/api.php` | All entity API routes |
| `plugins/Entity/Models/ChurchEntity.php` | Eloquent model, scopes, virtual attrs |
| `plugins/Entity/Models/EntityMember.php` | Member/follower model |
| `plugins/Entity/Policies/ChurchEntityPolicy.php` | create/update/delete/manageMembers gates |
| `plugins/Entity/Controllers/PageController.php` | CRUD for type='page' |
| `plugins/Entity/Controllers/PageFollowController.php` | Follow / unfollow a page |
| `plugins/Entity/Controllers/PageMemberController.php` | List members, update role, remove |
| `plugins/Entity/database/migrations/2026_04_28_000001_create_church_entities_table.php` | church_entities |
| `plugins/Entity/database/migrations/2026_04_28_000002_create_entity_members_table.php` | entity_members |
| `database/factories/ChurchEntityFactory.php` | Factory for pages |
| `tests/Feature/PageTest.php` | Feature tests (11 cases) |
| `resources/js/plugins/pages/PageCard.tsx` | Page card component |
| `resources/js/plugins/pages/PagesPage.tsx` | Browse/discover pages |
| `resources/js/plugins/pages/PageDetailPage.tsx` | Page profile + posts + follow |

### Modified files
| File | Change |
|---|---|
| `bootstrap/providers.php` | Register `EntityServiceProvider` |

---

## Task T1: Worktree + migrations

**Files:**
- Create: `.worktrees/sprint-6/` (worktree)
- Create: `plugins/Entity/database/migrations/2026_04_28_000001_create_church_entities_table.php`
- Create: `plugins/Entity/database/migrations/2026_04_28_000002_create_entity_members_table.php`

- [ ] **Create worktree**
```bash
git worktree add .worktrees/sprint-6 -b sprint/6-pages-plugin
cd .worktrees/sprint-6
ln -s ../../vendor vendor
cp ../../.env .env
```

- [ ] **Create church_entities migration**

`plugins/Entity/database/migrations/2026_04_28_000001_create_church_entities_table.php`:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['page', 'community'])->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('profile_image')->nullable();
            // Page-specific
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->json('social_links')->nullable();
            $table->json('action_button')->nullable();
            $table->boolean('is_verified')->default(false);
            // Community-specific (used in Sprint 8)
            $table->enum('privacy', ['public', 'closed', 'secret'])->default('public');
            $table->boolean('allow_posts')->default(true);
            $table->boolean('require_approval')->default(false);
            // Parent (ministry sub-pages — Sprint 9)
            $table->unsignedBigInteger('parent_entity_id')->nullable();
            // Counters
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index(['owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_entities');
    }
};
```

- [ ] **Create entity_members migration**

`plugins/Entity/database/migrations/2026_04_28_000002_create_entity_members_table.php`:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('church_entities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['pending', 'approved', 'declined', 'banned'])->default('approved');
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'user_id']);
            $table->index(['entity_id', 'status']);
            $table->index(['entity_id', 'role']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_members');
    }
};
```

- [ ] **Commit T1**
```bash
git add plugins/Entity/database/
git commit -m "feat(pages): add church_entities and entity_members migrations"
```

---

## Task T2: Models + Factory

**Files:**
- Create: `plugins/Entity/Models/ChurchEntity.php`
- Create: `plugins/Entity/Models/EntityMember.php`
- Create: `database/factories/ChurchEntityFactory.php`

- [ ] **Write failing test for model**

`tests/Feature/PageTest.php`:
```php
<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;

test('page slug is generated from name on creation', function () {
    $page = new ChurchEntity(['name' => 'Youth Ministry', 'type' => 'page']);
    expect($page->generateSlug())->toBe('youth-ministry');
});
```

- [ ] **Run test — expect FAIL** (class not found)
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php --filter="slug is generated"
```

- [ ] **Create ChurchEntity model**

`plugins/Entity/Models/ChurchEntity.php`:
```php
<?php
namespace Plugins\Entity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChurchEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'church_entities';

    protected $fillable = [
        'type', 'owner_id', 'name', 'slug', 'description', 'category_id',
        'cover_image', 'profile_image', 'website', 'address', 'phone',
        'social_links', 'action_button', 'is_verified', 'privacy',
        'allow_posts', 'require_approval', 'parent_entity_id',
        'members_count', 'posts_count', 'meta', 'is_active',
    ];

    protected $casts = [
        'social_links'   => 'array',
        'action_button'  => 'array',
        'meta'           => 'array',
        'is_verified'    => 'boolean',
        'allow_posts'    => 'boolean',
        'require_approval' => 'boolean',
        'is_active'      => 'boolean',
        'members_count'  => 'integer',
        'posts_count'    => 'integer',
    ];

    // Scopes
    public function scopePages($query)    { return $query->where('type', 'page'); }
    public function scopeCommunities($q)  { return $q->where('type', 'community'); }
    public function scopeActive($query)   { return $query->where('is_active', true); }

    // Relations
    public function owner()   { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function members() { return $this->hasMany(EntityMember::class, 'entity_id'); }

    public function approvedMembers()
    {
        return $this->hasMany(EntityMember::class, 'entity_id')->where('status', 'approved');
    }

    public function admins()
    {
        return $this->hasMany(EntityMember::class, 'entity_id')
                    ->where('role', 'admin')->where('status', 'approved');
    }

    // Helpers
    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->where('status', 'approved')->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)
                    ->whereIn('role', ['admin', 'moderator'])
                    ->where('status', 'approved')->exists();
    }

    protected static function newFactory()
    {
        return \Database\Factories\ChurchEntityFactory::new();
    }
}
```

- [ ] **Create EntityMember model**

`plugins/Entity/Models/EntityMember.php`:
```php
<?php
namespace Plugins\Entity\Models;

use Illuminate\Database\Eloquent\Model;

class EntityMember extends Model
{
    protected $table = 'entity_members';

    protected $fillable = ['entity_id', 'user_id', 'role', 'status', 'invited_by'];

    public function entity() { return $this->belongsTo(ChurchEntity::class, 'entity_id'); }
    public function user()   { return $this->belongsTo(\App\Models\User::class); }
}
```

- [ ] **Create factory**

`database/factories/ChurchEntityFactory.php`:
```php
<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;

class ChurchEntityFactory extends Factory
{
    protected $model = ChurchEntity::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        return [
            'type'     => 'page',
            'owner_id' => User::factory(),
            'name'     => ucwords($name),
            'slug'     => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'is_active'   => true,
        ];
    }

    public function community(): static
    {
        return $this->state(['type' => 'community', 'privacy' => 'public']);
    }
}
```

- [ ] **Run test — expect PASS**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php --filter="slug is generated"
```

- [ ] **Commit T2**
```bash
git add plugins/Entity/Models/ database/factories/ChurchEntityFactory.php tests/Feature/PageTest.php
git commit -m "feat(pages): add ChurchEntity + EntityMember models, ChurchEntityFactory"
```

---

## Task T3: ServiceProvider + plugin.json + routes skeleton

**Files:**
- Create: `plugins/Entity/EntityServiceProvider.php`
- Create: `plugins/Entity/plugin.json`
- Create: `plugins/Entity/routes/api.php`
- Modify: `bootstrap/providers.php`

- [ ] **Create ServiceProvider**

`plugins/Entity/EntityServiceProvider.php`:
```php
<?php
namespace Plugins\Entity;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class EntityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')
               ->group(base_path('plugins/Entity/routes/api.php'));

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

- [ ] **Create plugin.json**

`plugins/Entity/plugin.json`:
```json
{
  "name": "Entity",
  "slug": "entity",
  "version": "1.0.0",
  "description": "Church pages and communities — ministry pages, campuses, departments.",
  "author": "Church Platform",
  "icon": "flag",
  "category": "Feature",
  "requires": [],
  "settings_page": false,
  "can_disable": false,
  "can_remove": false,
  "enabled_by_default": true
}
```

- [ ] **Create routes skeleton**

`plugins/Entity/routes/api.php`:
```php
<?php
use Illuminate\Support\Facades\Route;
use Plugins\Entity\Controllers\PageController;
use Plugins\Entity\Controllers\PageFollowController;
use Plugins\Entity\Controllers\PageMemberController;

Route::prefix('v1')->name('api.v1.pages.')->group(function () {
    // Public
    Route::get('/pages',               [PageController::class, 'index'])->name('index');
    Route::get('/pages/{slug}',        [PageController::class, 'show'])->name('show');
    Route::get('/pages/{id}/members',  [PageMemberController::class, 'index'])->name('members.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/pages',                                  [PageController::class, 'store'])->name('store');
        Route::put('/pages/{id}',                              [PageController::class, 'update'])->name('update');
        Route::delete('/pages/{id}',                           [PageController::class, 'destroy'])->name('destroy');

        // Follow / unfollow
        Route::post('/pages/{id}/follow',                      [PageFollowController::class, 'store'])->name('follow.store');
        Route::delete('/pages/{id}/follow',                    [PageFollowController::class, 'destroy'])->name('follow.destroy');

        // Member management (admins only)
        Route::put('/pages/{id}/members/{userId}/role',        [PageMemberController::class, 'updateRole'])->name('members.role');
        Route::delete('/pages/{id}/members/{userId}',          [PageMemberController::class, 'destroy'])->name('members.destroy');
    });
});
```

- [ ] **Register ServiceProvider**

Add to `bootstrap/providers.php`:
```php
Plugins\Entity\EntityServiceProvider::class,
```

- [ ] **Run smoke test — routes resolve**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" artisan route:list | grep pages
```

- [ ] **Commit T3**
```bash
git add plugins/Entity/EntityServiceProvider.php plugins/Entity/plugin.json plugins/Entity/routes/ bootstrap/providers.php
git commit -m "feat(pages): add EntityServiceProvider, routes, plugin.json — register in providers"
```

---

## Task T4: PageController

**Files:**
- Create: `plugins/Entity/Controllers/PageController.php`

- [ ] **Write failing tests for CRUD**

Add to `tests/Feature/PageTest.php`:
```php
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('GET /pages returns list of pages', function () {
    ChurchEntity::factory()->count(3)->create(['type' => 'page']);
    $this->getJson('/api/v1/pages')->assertStatus(200)->assertJsonCount(3, 'data');
});

test('authenticated user can create a page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/pages', [
        'name'        => 'Youth Ministry',
        'description' => 'For all youth',
    ])->assertStatus(201)->assertJsonFragment(['name' => 'Youth Ministry']);
});

test('creating a page makes the creator an admin member', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson('/api/v1/pages', ['name' => 'Worship Team']);
    $id = $response->json('id');
    $this->assertDatabaseHas('entity_members', [
        'entity_id' => $id, 'user_id' => $user->id, 'role' => 'admin',
    ]);
});

test('non-owner cannot update page', function () {
    $page  = ChurchEntity::factory()->create(['type' => 'page']);
    $other = User::factory()->create();
    $this->actingAs($other)->putJson("/api/v1/pages/{$page->id}", ['name' => 'Hacked'])
         ->assertStatus(403);
});

test('owner can update own page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $user->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'admin', 'status' => 'approved']);
    $this->actingAs($user)->putJson("/api/v1/pages/{$page->id}", ['name' => 'Updated'])
         ->assertStatus(200)->assertJsonFragment(['name' => 'Updated']);
});

test('GET /pages/{slug} returns page by slug', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'slug' => 'worship-team']);
    $this->getJson('/api/v1/pages/worship-team')->assertStatus(200)->assertJsonFragment(['slug' => 'worship-team']);
});
```

- [ ] **Run tests — expect FAIL** (controller not found)
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php
```

- [ ] **Create PageController**

`plugins/Entity/Controllers/PageController.php`:
```php
<?php
namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class PageController extends Controller
{
    public function index(Request $request)
    {
        return ChurchEntity::pages()->active()
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->verified, fn($q) => $q->where('is_verified', true))
            ->latest()->paginate(20);
    }

    public function show(string $slug)
    {
        return ChurchEntity::pages()->active()->where('slug', $slug)
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->firstOrFail();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $slug = $this->uniqueSlug(Str::slug($data['name']));

        $page = ChurchEntity::create(array_merge($data, [
            'type'     => 'page',
            'owner_id' => $request->user()->id,
            'slug'     => $slug,
        ]));

        // Auto-add creator as admin
        EntityMember::create([
            'entity_id' => $page->id,
            'user_id'   => $request->user()->id,
            'role'      => 'admin',
            'status'    => 'approved',
        ]);

        $page->members_count = 1;
        $page->save();

        return response()->json($page, 201);
    }

    public function update(Request $request, int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);
        $this->authorize('update', $page);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $page->update($data);
        return $page;
    }

    public function destroy(int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);
        $this->authorize('delete', $page);
        $page->delete();
        return response()->json(null, 204);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (ChurchEntity::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
```

- [ ] **Run tests — expect PASS** (CRUD tests pass, policy test will fail until T5)
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php --filter="returns list|can create|makes the creator|returns page by slug"
```

- [ ] **Commit T4**
```bash
git add plugins/Entity/Controllers/PageController.php tests/Feature/PageTest.php
git commit -m "feat(pages): add PageController (index, show, store, update, destroy)"
```

---

## Task T5: Policy + PageFollowController + PageMemberController

**Files:**
- Create: `plugins/Entity/Policies/ChurchEntityPolicy.php`
- Create: `plugins/Entity/Controllers/PageFollowController.php`
- Create: `plugins/Entity/Controllers/PageMemberController.php`

- [ ] **Write follow + member tests**

Add to `tests/Feature/PageTest.php`:
```php
test('authenticated user can follow a page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    $this->actingAs($user)->postJson("/api/v1/pages/{$page->id}/follow")
         ->assertStatus(201);
    $this->assertDatabaseHas('entity_members', [
        'entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved',
    ]);
});

test('following a page increments members_count', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'members_count' => 0]);
    $this->actingAs($user)->postJson("/api/v1/pages/{$page->id}/follow")->assertStatus(201);
    expect($page->fresh()->members_count)->toBe(1);
});

test('user can unfollow a page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'members_count' => 1]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved']);
    $this->actingAs($user)->deleteJson("/api/v1/pages/{$page->id}/follow")->assertStatus(200);
    $this->assertDatabaseMissing('entity_members', ['entity_id' => $page->id, 'user_id' => $user->id]);
    expect($page->fresh()->members_count)->toBe(0);
});

test('admin can promote member to moderator', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'approved']);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);
    $this->actingAs($admin)->putJson("/api/v1/pages/{$page->id}/members/{$member->id}/role", ['role' => 'moderator'])
         ->assertStatus(200)->assertJsonFragment(['role' => 'moderator']);
});
```

- [ ] **Run tests — expect FAIL** (controllers not found)
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php --filter="can follow|increments members|can unfollow|can promote"
```

- [ ] **Create ChurchEntityPolicy**

`plugins/Entity/Policies/ChurchEntityPolicy.php`:
```php
<?php
namespace Plugins\Entity\Policies;

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;

class ChurchEntityPolicy
{
    public function update(User $user, ChurchEntity $entity): bool
    {
        return $entity->isAdmin($user->id) || $user->is_admin;
    }

    public function delete(User $user, ChurchEntity $entity): bool
    {
        return $entity->owner_id === $user->id || $user->is_admin;
    }

    public function manageMembers(User $user, ChurchEntity $entity): bool
    {
        return $entity->isAdmin($user->id) || $user->is_admin;
    }
}
```

Register policy in `plugins/Entity/EntityServiceProvider.php` (add to `register()`):
```php
use Illuminate\Support\Facades\Gate;

public function register(): void
{
    Gate::policy(
        \Plugins\Entity\Models\ChurchEntity::class,
        \Plugins\Entity\Policies\ChurchEntityPolicy::class
    );
}
```

- [ ] **Create PageFollowController**

`plugins/Entity/Controllers/PageFollowController.php`:
```php
<?php
namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class PageFollowController extends Controller
{
    public function store(Request $request, int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);

        $existing = EntityMember::where('entity_id', $id)
                                ->where('user_id', $request->user()->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Already following'], 409);
        }

        DB::transaction(function () use ($page, $request) {
            EntityMember::create([
                'entity_id' => $page->id,
                'user_id'   => $request->user()->id,
                'role'      => 'member',
                'status'    => 'approved',
            ]);
            ChurchEntity::where('id', $page->id)->increment('members_count');
        });

        return response()->json(['following' => true], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);

        $member = EntityMember::where('entity_id', $id)
                               ->where('user_id', $request->user()->id)
                               ->where('role', 'member')
                               ->first();

        if (!$member) {
            return response()->json(['message' => 'Not following'], 404);
        }

        DB::transaction(function () use ($page, $member) {
            $member->delete();
            ChurchEntity::where('id', $page->id)
                        ->where('members_count', '>', 0)
                        ->decrement('members_count');
        });

        return response()->json(['following' => false]);
    }
}
```

- [ ] **Create PageMemberController**

`plugins/Entity/Controllers/PageMemberController.php`:
```php
<?php
namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class PageMemberController extends Controller
{
    public function index(int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);
        return $page->approvedMembers()
                    ->with('user:id,name,avatar')
                    ->orderByRaw("FIELD(role,'admin','moderator','member')")
                    ->paginate(50);
    }

    public function updateRole(Request $request, int $id, int $userId)
    {
        $page = ChurchEntity::pages()->findOrFail($id);
        $this->authorize('manageMembers', $page);

        $data   = $request->validate(['role' => 'required|in:admin,moderator,member']);
        $member = EntityMember::where('entity_id', $id)->where('user_id', $userId)
                               ->where('status', 'approved')->firstOrFail();
        $member->update($data);
        return $member->load('user:id,name,avatar');
    }

    public function destroy(Request $request, int $id, int $userId)
    {
        $page = ChurchEntity::pages()->findOrFail($id);
        $this->authorize('manageMembers', $page);

        // Prevent removing the last admin
        if ($page->admins()->count() === 1) {
            $isLastAdmin = EntityMember::where('entity_id', $id)
                ->where('user_id', $userId)->where('role', 'admin')->exists();
            if ($isLastAdmin) {
                return response()->json(['message' => 'Cannot remove the last admin'], 422);
            }
        }

        EntityMember::where('entity_id', $id)->where('user_id', $userId)->delete();
        ChurchEntity::where('id', $id)->where('members_count', '>', 0)->decrement('members_count');

        return response()->json(null, 204);
    }
}
```

- [ ] **Run all tests — expect PASS**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php
```

Expected: all 11 tests green.

- [ ] **Format**
```bash
vendor/bin/pint plugins/Entity/
```

- [ ] **Commit T5**
```bash
git add plugins/Entity/
git commit -m "feat(pages): add ChurchEntityPolicy, PageFollowController, PageMemberController — all tests passing"
```

---

## Task T6: Frontend — PageCard + PagesPage + PageDetailPage

**Files:**
- Create: `resources/js/plugins/pages/PageCard.tsx`
- Create: `resources/js/plugins/pages/PagesPage.tsx`
- Create: `resources/js/plugins/pages/PageDetailPage.tsx`

- [ ] **Create PageCard**

`resources/js/plugins/pages/PageCard.tsx`:
```tsx
import React from 'react'

interface PageCardProps {
  page: {
    id: number
    name: string
    slug: string
    description?: string
    profile_image?: string
    cover_image?: string
    is_verified: boolean
    approved_members_count: number
  }
  onFollow?: (id: number) => void
  isFollowing?: boolean
}

export function PageCard({ page, onFollow, isFollowing }: PageCardProps) {
  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
      {page.cover_image && (
        <div className="h-24 bg-gray-200 overflow-hidden">
          <img src={page.cover_image} alt="" className="w-full h-full object-cover" />
        </div>
      )}
      <div className="p-4">
        <div className="flex items-start gap-3">
          <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
            {page.profile_image
              ? <img src={page.profile_image} alt={page.name} className="w-full h-full object-cover" />
              : <span className="text-blue-600 font-bold text-lg">{page.name[0]}</span>
            }
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-1">
              <h3 className="font-semibold text-gray-900 truncate">{page.name}</h3>
              {page.is_verified && (
                <svg className="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
              )}
            </div>
            <p className="text-sm text-gray-500">{page.approved_members_count.toLocaleString()} followers</p>
          </div>
        </div>
        {page.description && (
          <p className="mt-2 text-sm text-gray-600 line-clamp-2">{page.description}</p>
        )}
        {onFollow && (
          <button
            onClick={() => onFollow(page.id)}
            className={`mt-3 w-full py-1.5 rounded-lg text-sm font-medium transition-colors ${
              isFollowing
                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            }`}
          >
            {isFollowing ? 'Following' : 'Follow'}
          </button>
        )}
      </div>
    </div>
  )
}
```

- [ ] **Create PagesPage**

`resources/js/plugins/pages/PagesPage.tsx`:
```tsx
import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import axios from 'axios'
import { PageCard } from './PageCard'

export function PagesPage() {
  const [search, setSearch] = useState('')
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['pages', search],
    queryFn: () => axios.get('/api/v1/pages', { params: { search: search || undefined } }).then(r => r.data),
  })

  const followMutation = useMutation({
    mutationFn: (id: number) => axios.post(`/api/v1/pages/${id}/follow`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['pages'] }),
  })

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Ministry Pages</h1>
      </div>

      <input
        type="search"
        placeholder="Search pages..."
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="w-full mb-6 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />

      {isLoading && <p className="text-gray-500 text-center py-12">Loading…</p>}

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {data?.data?.map((page: any) => (
          <PageCard
            key={page.id}
            page={page}
            onFollow={(id) => followMutation.mutate(id)}
          />
        ))}
      </div>

      {data?.data?.length === 0 && (
        <p className="text-center text-gray-500 py-12">No pages found.</p>
      )}
    </div>
  )
}
```

- [ ] **Create PageDetailPage**

`resources/js/plugins/pages/PageDetailPage.tsx`:
```tsx
import React, { useState } from 'react'
import { useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import axios from 'axios'

export function PageDetailPage() {
  const { slug } = useParams<{ slug: string }>()
  const qc = useQueryClient()
  const [following, setFollowing] = useState(false)

  const { data: page, isLoading } = useQuery({
    queryKey: ['page', slug],
    queryFn: () => axios.get(`/api/v1/pages/${slug}`).then(r => r.data),
  })

  const followMutation = useMutation({
    mutationFn: () => following
      ? axios.delete(`/api/v1/pages/${page.id}/follow`)
      : axios.post(`/api/v1/pages/${page.id}/follow`),
    onSuccess: () => {
      setFollowing(f => !f)
      qc.invalidateQueries({ queryKey: ['page', slug] })
    },
  })

  if (isLoading) return <div className="p-8 text-gray-500">Loading…</div>
  if (!page) return <div className="p-8 text-red-500">Page not found.</div>

  return (
    <div className="max-w-3xl mx-auto">
      {/* Cover */}
      <div className="h-48 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-b-xl overflow-hidden">
        {page.cover_image && <img src={page.cover_image} alt="" className="w-full h-full object-cover" />}
      </div>

      {/* Header */}
      <div className="px-6 pb-4 -mt-10 flex items-end gap-4">
        <div className="w-20 h-20 rounded-xl border-4 border-white bg-white shadow overflow-hidden flex-shrink-0">
          {page.profile_image
            ? <img src={page.profile_image} alt={page.name} className="w-full h-full object-cover" />
            : <div className="w-full h-full bg-blue-100 flex items-center justify-center text-3xl font-bold text-blue-600">{page.name[0]}</div>
          }
        </div>
        <div className="flex-1 pt-12">
          <div className="flex items-center gap-2 flex-wrap">
            <h1 className="text-2xl font-bold text-gray-900">{page.name}</h1>
            {page.is_verified && (
              <span className="text-blue-500 text-sm font-medium bg-blue-50 px-2 py-0.5 rounded-full">Verified</span>
            )}
          </div>
          <p className="text-gray-500 text-sm">{page.approved_members_count?.toLocaleString()} followers</p>
        </div>
        <button
          onClick={() => followMutation.mutate()}
          disabled={followMutation.isPending}
          className={`px-5 py-2 rounded-lg font-medium transition-colors ${
            following ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-blue-600 text-white hover:bg-blue-700'
          }`}
        >
          {following ? 'Following' : 'Follow'}
        </button>
      </div>

      {/* Info */}
      {page.description && (
        <div className="px-6 py-4 border-t border-gray-100">
          <p className="text-gray-700">{page.description}</p>
        </div>
      )}
      <div className="px-6 py-4 grid grid-cols-2 gap-4 text-sm text-gray-600 border-t border-gray-100">
        {page.website && <a href={page.website} target="_blank" rel="noreferrer" className="flex items-center gap-2 text-blue-600 hover:underline">🌐 {page.website}</a>}
        {page.address && <span>📍 {page.address}</span>}
        {page.phone && <span>📞 {page.phone}</span>}
      </div>
    </div>
  )
}
```

- [ ] **Verify TypeScript compiles**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" node node_modules/.bin/tsc --noEmit
```

- [ ] **Commit T6**
```bash
git add resources/js/plugins/pages/
git commit -m "feat(pages): add PageCard, PagesPage, PageDetailPage frontend components"
```

---

## Task T7: Final verification + finish branch

- [ ] **Run full test suite**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageTest.php
```
Expected: 11 tests, all green.

- [ ] **Run Pint on all plugin files**
```bash
vendor/bin/pint plugins/Entity/
```

- [ ] **Vite build**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build
```

- [ ] **Finish branch** — use `superpowers:finishing-a-development-branch` skill
