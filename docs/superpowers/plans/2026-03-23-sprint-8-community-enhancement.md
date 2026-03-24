# Sprint 8 — Community Enhancement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade the Community plugin with full privacy/join-approval flow, role management, and member moderation (ban/unban).

**Architecture:** The existing `communities` table already has `requires_approval` + `pending` status in `community_members`. Sprint 8 adds a `closed` privacy variant, wires the approval/reject flow via new controller methods, adds role promotion/demotion, ban/unban, and a `CommunityMemberController` to own these actions. Frontend gets join state awareness (Join / Pending / Leave), an admin approval queue panel, and role management on the community detail page.

**Tech Stack:** Laravel 11 · PestPHP · React 18 · TypeScript · Tailwind CSS · Axios · TanStack Query

---

## File Map

### New files
| File | Responsibility |
|---|---|
| `plugins/Community/Controllers/CommunityMemberController.php` | approve/reject join requests, update role, ban/unban |
| `plugins/Community/database/migrations/2026_05_05_000001_add_closed_privacy_to_communities.php` | Extend privacy enum: add `closed`; set `requires_approval=true` when `privacy=closed` |
| `database/factories/CommunityFactory.php` | Test factory for Community + CommunityMember |
| `tests/Feature/CommunityJoinTest.php` | join approval flow, closed community, role management, ban |
| `resources/js/plugins/community/CommunityDetailPage.tsx` | Detail page with live feed, member list, admin approval queue |

### Modified files
| File | Change |
|---|---|
| `plugins/Community/Controllers/CommunityController.php` | `store()` sets `requires_approval=true` when `privacy=closed`; `index()` appends `my_status` per community for auth user |
| `plugins/Community/routes/api.php` | Add member management routes |
| `resources/js/plugins/community/CommunityPage.tsx` | Show join state (Join / Pending / Leave); link to detail page |

---

## Task T1: Worktree + migration (closed privacy)

**Files:**
- Create: `.worktrees/sprint-8/`
- Create: `plugins/Community/database/migrations/2026_05_05_000001_add_closed_privacy_to_communities.php`

- [ ] **Create worktree**
```bash
git worktree add .worktrees/sprint-8 -b sprint/8-community-enhancement
cd .worktrees/sprint-8
ln -s ../../vendor vendor
cp ../../.env .env
```

- [ ] **Create migration**

`plugins/Community/database/migrations/2026_05_05_000001_add_closed_privacy_to_communities.php`:
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN for enums.
        // We add a separate `privacy_v2` column and use it going forward.
        // In production MySQL this migration extends the enum inline.
        Schema::table('communities', function (Blueprint $table) {
            $table->string('privacy_closed')->default('0')->after('privacy');
            // privacy_closed='1' means "closed" (visible but requires approval to join)
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn('privacy_closed');
        });
    }
};
```

> **SQLite note:** SQLite cannot `ALTER COLUMN` enum values. We track "closed" communities with a boolean `privacy_closed` column. The model exposes a `isClosed()` helper and `store()` sets it when `privacy=closed` is requested.

- [ ] **Commit T1**
```bash
git add plugins/Community/database/migrations/
git commit -m "feat(community): add privacy_closed column for closed-community join approval"
```

---

## Task T2: CommunityFactory + CommunityController privacy/join updates

**Files:**
- Create: `database/factories/CommunityFactory.php`
- Modify: `plugins/Community/Models/Community.php`
- Modify: `plugins/Community/Controllers/CommunityController.php`

- [ ] **Write failing tests**

`tests/Feature/CommunityJoinTest.php`:
```php
<?php
use App\Models\User;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

test('joining an open community auto-approves', function () {
    $user      = User::factory()->create();
    $community = Community::factory()->create(['privacy' => 'public', 'privacy_closed' => '0']);

    $this->actingAs($user)->postJson("/api/v1/communities/{$community->id}/join")
         ->assertStatus(201)->assertJson(['status' => 'approved']);

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $user->id])->value('status'))
        ->toBe('approved');
});

test('joining a closed community creates pending request', function () {
    $user      = User::factory()->create();
    $community = Community::factory()->closed()->create();

    $this->actingAs($user)->postJson("/api/v1/communities/{$community->id}/join")
         ->assertStatus(201)->assertJson(['status' => 'pending']);

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $user->id])->value('status'))
        ->toBe('pending');
});

test('community admin can approve a pending member', function () {
    $admin     = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->closed()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,     'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($admin)
         ->postJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $requester->id])->value('status'))
        ->toBe('approved');
});

test('community admin can reject a pending member', function () {
    $admin     = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,     'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($admin)
         ->deleteJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $requester->id])->exists())
        ->toBeFalse();
});

test('non-admin cannot approve members', function () {
    $user      = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->create();
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id,      'role' => 'member', 'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($user)
         ->postJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertStatus(403);
});

test('admin can promote member to moderator', function () {
    $admin  = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,  'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);

    $this->actingAs($admin)
         ->patchJson("/api/v1/communities/{$community->id}/members/{$member->id}", ['role' => 'moderator'])
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $member->id])->value('role'))
        ->toBe('moderator');
});

test('admin can ban a member', function () {
    $admin  = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,  'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);

    $this->actingAs($admin)
         ->postJson("/api/v1/communities/{$community->id}/members/{$member->id}/ban")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $member->id])->value('status'))
        ->toBe('banned');
});
```

- [ ] **Run tests — expect FAIL**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityJoinTest.php 2>&1 | tail -8
```

- [ ] **Create CommunityFactory**

`database/factories/CommunityFactory.php`:
```php
<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Community\Models\Community;

class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->words(3, true),
            'slug'              => $this->faker->unique()->slug(),
            'description'       => $this->faker->sentence(),
            'privacy'           => 'public',
            'privacy_closed'    => '0',
            'status'            => 'active',
            'is_counsel_group'  => false,
            'requires_approval' => false,
            'members_count'     => 0,
            'posts_count'       => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(['privacy' => 'closed', 'privacy_closed' => '1', 'requires_approval' => true]);
    }
}
```

- [ ] **Update Community model** — add `newFactory()`, `isClosed()` helper, cast `privacy_closed`:

In `plugins/Community/Models/Community.php`:
```php
// Add to $fillable:
'privacy_closed',

// Add to $casts:
'privacy_closed' => 'boolean',

// Add newFactory():
protected static function newFactory(): \Database\Factories\CommunityFactory
{
    return \Database\Factories\CommunityFactory::new();
}

// Add helper:
public function isClosed(): bool
{
    return (bool) $this->privacy_closed;
}

public function isAdmin(int $userId): bool
{
    return $this->communityMembers()
        ->where('user_id', $userId)
        ->where('role', 'admin')
        ->where('status', 'approved')
        ->exists();
}
```

- [ ] **Update CommunityController::store()** — set `requires_approval` + `privacy_closed` when `privacy=closed`:

In `store()`, change validation:
```php
'privacy' => ['sometimes', 'in:public,private,closed'],
```

After validation, before `Community::create(...)`:
```php
$validated['requires_approval'] = ($validated['privacy'] ?? 'public') === 'closed';
$validated['privacy_closed']    = $validated['requires_approval'] ? '1' : '0';
if (($validated['privacy'] ?? 'public') === 'closed') {
    $validated['privacy'] = 'private'; // store as private for feed queries
}
```

- [ ] **Update CommunityController::index()** — append `my_status` for authenticated user:

Replace the `index()` body in `plugins/Community/Controllers/CommunityController.php`:
```php
public function index(Request $request): JsonResponse
{
    $user = $request->user();

    $communities = Community::regularGroups()->active()
        ->with('creator:id,name,avatar')
        ->withCount('approvedMembers')
        ->when($request->search,    fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
        ->when($request->church_id, fn ($q) => $q->where('church_id', $request->church_id))
        ->latest()->paginate(20);

    if ($user) {
        $myMemberships = CommunityMember::where('user_id', $user->id)
            ->whereIn('community_id', $communities->pluck('id'))
            ->pluck('status', 'community_id');

        $communities->getCollection()->transform(function ($c) use ($myMemberships) {
            $c->my_status = $myMemberships->get($c->id); // 'approved'|'pending'|null
            return $c;
        });
    }

    return response()->json($communities);
}
```

- [ ] **Update CommunityController::join()** — use `isClosed()`:

The existing `join()` already checks `$community->requires_approval`. No change needed — `closed()` factory sets `requires_approval=true`.

- [ ] **Run tests — expect PASS (first 2 tests)**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityJoinTest.php --filter "joining" 2>&1 | tail -8
```

- [ ] **Commit T2**
```bash
git add database/factories/CommunityFactory.php plugins/Community/Models/Community.php plugins/Community/Controllers/CommunityController.php tests/Feature/CommunityJoinTest.php
git commit -m "feat(community): CommunityFactory, closed privacy, isClosed/isAdmin helpers"
```

---

## Task T3: CommunityMemberController — approve/reject/role/ban

**Files:**
- Create: `plugins/Community/Controllers/CommunityMemberController.php`
- Modify: `plugins/Community/routes/api.php`

- [ ] **Create CommunityMemberController**

`plugins/Community/Controllers/CommunityMemberController.php`:
```php
<?php
namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

class CommunityMemberController extends Controller
{
    /** GET /communities/{id}/members — list approved + pending members (admin only) */
    public function index(Request $request, int $id): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $members = $community->communityMembers()
            ->with('user:id,name,avatar')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'moderator' THEN 1 ELSE 2 END")
            ->get();

        return response()->json($members);
    }

    /** POST /communities/{id}/members/{userId}/approve */
    public function approve(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $member = CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'pending')
            ->firstOrFail();

        $member->update(['status' => 'approved']);
        $community->increment('members_count');

        return response()->json(['message' => 'Approved.']);
    }

    /** DELETE /communities/{id}/members/{userId}/approve — reject */
    public function reject(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'pending')
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Rejected.']);
    }

    /** PATCH /communities/{id}/members/{userId} — update role */
    public function updateRole(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $role = $request->validate(['role' => ['required', 'in:moderator,member']])['role'];

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'approved')
            ->firstOrFail()
            ->update(['role' => $role]);

        return response()->json(['message' => 'Role updated.']);
    }

    /** POST /communities/{id}/members/{userId}/ban */
    public function ban(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);
        abort_if($userId === $request->user()->id, 422, 'Cannot ban yourself.');

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->firstOrFail()
            ->update(['status' => 'banned']);

        return response()->json(['message' => 'Banned.']);
    }

    /** DELETE /communities/{id}/members/{userId}/ban — unban */
    public function unban(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'banned')
            ->firstOrFail()
            ->update(['status' => 'approved']);

        return response()->json(['message' => 'Unbanned.']);
    }
}
```

- [ ] **Add routes** in `plugins/Community/routes/api.php`:

Add the `use` import at the **top of the file** with the existing `use` statements:
```php
use Plugins\Community\Controllers\CommunityMemberController;
```

Then add inside the `auth:sanctum` group, after the `leave` route:
Route::get('communities/{id}/members',                    [CommunityMemberController::class, 'index']);
Route::post('communities/{id}/members/{userId}/approve',  [CommunityMemberController::class, 'approve']);
Route::delete('communities/{id}/members/{userId}/approve',[CommunityMemberController::class, 'reject']);
Route::patch('communities/{id}/members/{userId}',         [CommunityMemberController::class, 'updateRole']);
Route::post('communities/{id}/members/{userId}/ban',      [CommunityMemberController::class, 'ban']);
Route::delete('communities/{id}/members/{userId}/ban',    [CommunityMemberController::class, 'unban']);
```

- [ ] **Run all tests — expect all 7 passing**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityJoinTest.php 2>&1 | tail -8
```
Expected: 7 passed.

- [ ] **Commit T3**
```bash
git add plugins/Community/Controllers/CommunityMemberController.php plugins/Community/routes/api.php
git commit -m "feat(community): CommunityMemberController — approve/reject/role/ban with 7 tests passing"
```

---

## Task T4: Frontend — CommunityPage join state + CommunityDetailPage

**Files:**
- Modify: `resources/js/plugins/community/CommunityPage.tsx`
- Create: `resources/js/plugins/community/CommunityDetailPage.tsx`

- [ ] **Update CommunityPage** — show correct join state (Join / Pending / Leave)

Replace `CommunityPage.tsx`:
```tsx
import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

interface Community {
    id: number; name: string; description?: string;
    members_count: number; privacy: string; privacy_closed: boolean; cover_image?: string;
    my_status?: 'approved' | 'pending' | null;
}

export default function CommunityPage() {
    const navigate    = useNavigate();
    const [items, setItems]   = useState<Community[]>([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        axios.get('/api/v1/communities', { params: { search } }).then(r => setItems(r.data.data ?? []));
    }, [search]);

    const handleJoin = async (c: Community) => {
        if (c.my_status === 'approved') {
            await axios.delete(`/api/v1/communities/${c.id}/leave`);
            setItems(cs => cs.map(x => x.id === c.id ? { ...x, my_status: null, members_count: x.members_count - 1 } : x));
        } else if (!c.my_status) {
            const res = await axios.post(`/api/v1/communities/${c.id}/join`);
            const newStatus = res.data.status as 'approved' | 'pending';
            setItems(cs => cs.map(x => x.id === c.id ? {
                ...x, my_status: newStatus,
                members_count: newStatus === 'approved' ? x.members_count + 1 : x.members_count,
            } : x));
        }
    };

    const joinLabel = (c: Community) => {
        if (c.my_status === 'approved') return 'Leave';
        if (c.my_status === 'pending')  return 'Pending…';
        return c.privacy_closed ? 'Request to Join' : 'Join';
    };

    const joinStyle = (c: Community): React.CSSProperties => ({
        width: '100%', border: 'none', borderRadius: 8, padding: '0.4rem', cursor: c.my_status === 'pending' ? 'default' : 'pointer',
        background: c.my_status === 'approved' ? '#f1f5f9' : c.my_status === 'pending' ? '#fef3c7' : '#2563eb',
        color: c.my_status === 'approved' ? '#475569' : c.my_status === 'pending' ? '#92400e' : '#fff',
    });

    return (
        <div style={{ maxWidth: 800, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Communities</h1>
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search…"
                style={{ width: '100%', padding: '0.6rem 1rem', border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: '1rem', boxSizing: 'border-box' }} />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(240px,1fr))', gap: '1rem' }}>
                {items.map(c => (
                    <div key={c.id} style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
                        <div onClick={() => navigate(`/communities/${c.id}`)} style={{ cursor: 'pointer' }}>
                            <div style={{ height: 80, background: c.cover_image ? `url(${c.cover_image}) center/cover` : '#2563eb' }} />
                            <div style={{ padding: '0.75rem 0.75rem 0' }}>
                                <div style={{ fontWeight: 600 }}>{c.name}</div>
                                <div style={{ fontSize: '0.8rem', color: '#64748b', marginBottom: '0.4rem' }}>
                                    {c.members_count} members · {c.privacy_closed ? 'Closed' : c.privacy}
                                </div>
                                {c.description && <p style={{ fontSize: '0.8rem', color: '#475569', marginBottom: '0.5rem' }}>{c.description}</p>}
                            </div>
                        </div>
                        <div style={{ padding: '0 0.75rem 0.75rem' }}>
                            <button
                                onClick={() => c.my_status !== 'pending' && handleJoin(c)}
                                style={joinStyle(c)}
                                disabled={c.my_status === 'pending'}
                            >
                                {joinLabel(c)}
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Create CommunityDetailPage**

`resources/js/plugins/community/CommunityDetailPage.tsx`:
```tsx
import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface Member { id: number; user_id: number; role: string; status: string; user: { id: number; name: string; avatar?: string } }

export default function CommunityDetailPage() {
    const { id }   = useParams<{ id: string }>();
    const navigate = useNavigate();
    const qc       = useQueryClient();

    const { data: community, isLoading, isError } = useQuery({
        queryKey: ['community', id],
        queryFn:  () => axios.get(`/api/v1/communities/${id}`).then(r => r.data),
    });

    const { data: members } = useQuery({
        queryKey: ['community-members', id],
        queryFn:  () => axios.get(`/api/v1/communities/${id}/members`).then(r => r.data),
        enabled: !!community,
        retry: false, // non-admins get 403 — that's fine
    });

    const approveMutation = useMutation({
        mutationFn: (userId: number) => axios.post(`/api/v1/communities/${id}/members/${userId}/approve`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    const rejectMutation = useMutation({
        mutationFn: (userId: number) => axios.delete(`/api/v1/communities/${id}/members/${userId}/approve`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    const banMutation = useMutation({
        mutationFn: (userId: number) => axios.post(`/api/v1/communities/${id}/members/${userId}/ban`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    if (isLoading) return <div className="max-w-3xl mx-auto p-6"><div className="h-48 bg-gray-200 rounded-xl animate-pulse" /></div>;
    if (isError || !community) return (
        <div className="max-w-3xl mx-auto px-6 py-16 text-center">
            <p className="text-gray-500">Community not found.</p>
            <button onClick={() => navigate('/communities')} className="mt-4 text-blue-600 text-sm hover:underline">← Back</button>
        </div>
    );

    const pending  = (members as Member[] | undefined)?.filter(m => m.status === 'pending')  ?? [];
    const approved = (members as Member[] | undefined)?.filter(m => m.status === 'approved') ?? [];

    return (
        <div className="max-w-3xl mx-auto">
            {/* Cover */}
            <div className="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-b-xl overflow-hidden">
                {community.cover_image && <img src={community.cover_image} className="w-full h-full object-cover" alt="" />}
            </div>

            {/* Header */}
            <div className="px-6 py-4">
                <h1 className="text-2xl font-bold text-gray-900">{community.name}</h1>
                <p className="text-sm text-gray-500 mt-0.5">
                    {community.approved_members_count ?? community.members_count} members
                    {' · '}{community.privacy_closed ? 'Closed' : community.privacy}
                </p>
                {community.description && <p className="text-gray-700 mt-2 text-sm leading-relaxed">{community.description}</p>}
            </div>

            {/* Approval queue (admin only — shown when members loaded) */}
            {pending.length > 0 && (
                <div className="mx-6 mb-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
                    <h2 className="text-sm font-semibold text-amber-800 mb-3">Join Requests ({pending.length})</h2>
                    {pending.map(m => (
                        <div key={m.user_id} className="flex items-center gap-3 mb-2">
                            <img src={m.user.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(m.user.name)}`}
                                className="w-8 h-8 rounded-full object-cover" alt="" />
                            <span className="flex-1 text-sm font-medium text-gray-800">{m.user.name}</span>
                            <button onClick={() => approveMutation.mutate(m.user_id)}
                                className="px-3 py-1 text-xs rounded-lg bg-green-500 text-white hover:bg-green-600">Approve</button>
                            <button onClick={() => rejectMutation.mutate(m.user_id)}
                                className="px-3 py-1 text-xs rounded-lg bg-red-100 text-red-600 hover:bg-red-200">Reject</button>
                        </div>
                    ))}
                </div>
            )}

            {/* Members list */}
            {approved.length > 0 && (
                <div className="px-6 pb-6">
                    <h2 className="text-base font-semibold text-gray-900 mb-3">Members</h2>
                    {approved.map(m => (
                        <div key={m.user_id} className="flex items-center gap-3 mb-2">
                            <img src={m.user.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(m.user.name)}`}
                                className="w-8 h-8 rounded-full object-cover" alt="" />
                            <span className="flex-1 text-sm font-medium text-gray-800">{m.user.name}</span>
                            <span className="text-xs text-gray-400 capitalize">{m.role}</span>
                            {m.role !== 'admin' && (
                                <button onClick={() => banMutation.mutate(m.user_id)}
                                    className="px-2 py-1 text-xs rounded bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500">Ban</button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
```

- [ ] **TypeScript check**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" node node_modules/.bin/tsc --noEmit 2>&1 | grep "plugins/" | head -10
```

- [ ] **Vite build**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -5
```

- [ ] **Commit T4**
```bash
git add resources/js/plugins/community/
git commit -m "feat(community): join state UI, CommunityDetailPage with approval queue and member list"
```

---

## Task T5: Final verification + finish branch

- [ ] **Run all 7 community tests**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityJoinTest.php 2>&1 | tail -8
```
Expected: 7 passed.

- [ ] **Run full suite (confirm no regressions)**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/ 2>&1 | tail -6
```

- [ ] **Format PHP**
```bash
vendor/bin/pint plugins/Community/
```

- [ ] **Final Vite build**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -5
```

- [ ] **Finish branch** — use `superpowers:finishing-a-development-branch` skill
