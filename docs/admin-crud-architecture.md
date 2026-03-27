# Admin CRUD Architecture — Bemusic-Inspired Pattern

This document describes the backend and frontend architecture used for admin CRUD pages in church-platform, modelled on the pattern established by the Bemusic Script.

---

## Overview

Every admin resource follows the same layered shape:

```
URL                        →  React Page         →  Laravel Controller     →  Eloquent Model
/admin/users               →  AdminUsers.tsx      →  AdminUserController    →  User
/admin/posts               →  AdminPosts.tsx       →  AdminPostController    →  Post (social_posts)
/admin/events              →  AdminEvents.tsx      →  AdminEventController   →  Event
/admin/communities         →  AdminCommunities.tsx →  AdminCommunityController→ Community
/admin/faq                 →  FaqManager.jsx       →  FaqAdminController     →  FaqCategory / Faq
/admin/media               →  MediaManager.jsx     →  (Media plugin)         →  MediaFile
/admin/pages               →  PagesManager.jsx     →  PageBuilderController  →  Page
/admin/church              →  ChurchBuilder.jsx    →  (Church plugin)        →  Church
/admin/settings            →  SettingsManager.jsx  →  SettingsController     →  settings table
```

---

## Backend Pattern

### 1. Controller location

All admin controllers live in:
```
app/Http/Controllers/Api/Admin/
```

Namespace: `App\Http\Controllers\Api\Admin`

### 2. Standard CRUD controller shape

```php
<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Foo\Models\Foo;

class AdminFooController extends Controller
{
    // LIST — paginated, searchable, filterable
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Foo::with(['relation:id,name'])
                ->withCount('children')
                ->when($request->search,   fn($q) => $q->where('title', 'like', "%{$request->search}%"))
                ->when($request->category, fn($q) => $q->where('category', $request->category))
                ->latest()
                ->paginate(15)
        );
    }

    // DELETE
    public function destroy(Foo $foo): JsonResponse
    {
        $foo->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Optional: moderate / status change
    public function moderate(Request $request, Foo $foo): JsonResponse
    {
        $data = $request->validate(['status' => 'required|in:published,rejected']);
        $foo->update(['status' => $data['status']]);
        return response()->json($foo);
    }
}
```

**Key conventions:**
- Always use `paginate(15)` — never `get()` for lists
- Eager-load with `with(['relation:id,name'])` to avoid N+1 queries
- Use `when()` for optional filters — keeps the query clean and avoids null checks
- Return `JsonResponse` type hints for IDE support

### 3. Route registration

All admin routes live inside a single guarded group in `routes/api.php`:

```php
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Users
    Route::get('users',              [AdminUserController::class, 'index']);
    Route::patch('users/{user}/role',[AdminUserController::class, 'updateRole']);
    Route::delete('users/{user}',    [AdminUserController::class, 'destroy']);

    // Posts
    Route::get('posts',                    [AdminPostController::class, 'index']);
    Route::delete('posts/{post}',          [AdminPostController::class, 'destroy']);
    Route::patch('posts/{post}/moderate',  [AdminPostController::class, 'moderate']);

    // ... more resources
});
```

**Middleware stack:**
- `auth:sanctum` — must be authenticated
- `role:admin` — Spatie permission check (alias registered in `bootstrap/app.php`)

### 4. Adding a new admin resource

1. Create controller: `app/Http/Controllers/Api/Admin/AdminFooController.php`
2. Register routes in `routes/api.php` inside the admin group
3. Add use-import at the top of `routes/api.php`

---

## Frontend Pattern

### 1. File location

```
resources/js/components/admin/pages/
├── AdminDashboard.tsx     ← stats overview
├── AdminUsers.tsx         ← users CRUD
├── AdminPosts.tsx         ← posts moderation
├── AdminEvents.tsx        ← events management
├── AdminCommunities.tsx   ← communities management
```

### 2. Standard datatable page shape

Every datatable page follows this exact structure:

```tsx
export default function AdminFoo() {
    // State
    const [data, setData]       = useState<Paginated | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch]   = useState('');
    const [filter, setFilter]   = useState('');
    const [page, setPage]       = useState(1);
    const [busy, setBusy]       = useState<number | null>(null);

    // Fetch
    const load = useCallback(() => {
        setLoading(true);
        axios.get('/api/v1/admin/foos', { params: { search, filter, page } })
            .then(r => setData(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, filter, page]);

    useEffect(() => { load(); }, [load]);

    // Debounce search
    useEffect(() => {
        const t = setTimeout(() => { setPage(1); load(); }, 400);
        return () => clearTimeout(t);
    }, [search]); // eslint-disable-line

    // Actions
    const handleDelete = async (id: number) => {
        if (!confirm('Delete?')) return;
        setBusy(id);
        await axios.delete(`/api/v1/admin/foos/${id}`).catch(() => {});
        setBusy(null);
        load();
    };

    return (
        <div className="space-y-4">
            {/* 1. Page header */}
            <div>
                <h1 className="text-2xl font-bold text-gray-800">Foos</h1>
                {data && <p className="text-sm text-gray-500">{data.total} total</p>}
            </div>

            {/* 2. Filters bar */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex gap-3">
                <input type="text" placeholder="Search…" value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                <select value={filter} onChange={e => { setFilter(e.target.value); setPage(1); }}
                    className="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                </select>
            </div>

            {/* 3. Table */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100 bg-gray-50">
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                            <th className="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {/* Loading skeleton */}
                        {loading && [...Array(5)].map((_, i) => (
                            <tr key={i} className="border-b animate-pulse">
                                <td className="px-4 py-3"><div className="h-4 bg-gray-200 rounded w-32" /></td>
                                <td className="px-4 py-3" />
                            </tr>
                        ))}
                        {/* Empty state */}
                        {!loading && data?.data.length === 0 && (
                            <tr><td colSpan={2} className="px-4 py-10 text-center text-gray-400">Nothing found</td></tr>
                        )}
                        {/* Rows */}
                        {!loading && data?.data.map(item => (
                            <tr key={item.id} className="border-b hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-3 font-medium text-gray-800">{item.name}</td>
                                <td className="px-4 py-3 text-right">
                                    <button onClick={() => handleDelete(item.id)} disabled={busy === item.id}
                                        className="text-xs text-red-500 hover:underline disabled:opacity-40">
                                        {busy === item.id ? '…' : 'Delete'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* 4. Pagination footer */}
                {data && data.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
                        <span className="text-xs text-gray-500">Page {data.current_page} of {data.last_page}</span>
                        <div className="flex gap-2">
                            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100">← Prev</button>
                            <button onClick={() => setPage(p => Math.min(data.last_page, p + 1))} disabled={page === data.last_page}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100">Next →</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
```

### 3. Wiring a new page into AdminLayout

`AdminLayout.tsx` defines:

**a) Navigation section entry:**
```tsx
// In NAV_SECTIONS array, under the appropriate section title:
{ path: '/admin/foos', label: 'Foos', icon: ICONS.someIcon }
```

**b) Route entry:**
```tsx
// In the <Routes> block inside AdminLayout:
<Route path="foos" element={<AdminFoos />} />
```

**c) Import at top of AdminLayout.tsx:**
```tsx
import AdminFoos from './pages/AdminFoos';
```

### 4. Adding a new SVG icon

`AdminLayout.tsx` stores all icon SVG `d` paths in a `ICONS` object. Add a new path:
```tsx
const ICONS = {
    // ... existing icons
    foos: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2',
};
```

Use any Heroicons outline path (24×24 viewBox). See https://heroicons.com.

---

## Admin Layout Architecture

```
AdminLayout.tsx
├── <aside> Dark sidebar (w-56 / w-16 collapsed)
│   ├── Logo + collapse toggle
│   ├── <nav> — NAV_SECTIONS with NavLink (React Router)
│   │   NavLink active class = bg-indigo-600
│   └── User footer (name, email, Back to Site, Logout)
└── <div> Main area
    ├── <header> Top bar (user name + Admin badge)
    └── <main> Page content
        └── <Routes>  ← nested React Router routes
            ├── index → AdminDashboard
            ├── posts → AdminPosts
            ├── events → AdminEvents
            ├── communities → AdminCommunities
            ├── users → AdminUsers
            ├── faq → FaqManager (existing)
            ├── media → MediaManager (existing)
            ├── pages → PagesManager (existing)
            ├── church → ChurchBuilder (existing)
            └── settings → SettingsManager (existing)
```

`AdminLayout` is mounted by `ChurchApp.tsx` when `location.pathname.startsWith('/admin')` and the user has the `admin` role.

---

## Bemusic Design Principles Applied

| Bemusic                        | Church Platform equivalent               |
|--------------------------------|------------------------------------------|
| Dark sidebar + icon sections   | `AdminLayout.tsx` — same layout          |
| Feature-based module folders   | `components/admin/pages/` per resource   |
| Datatable + filters + paginate | Each `Admin*.tsx` follows identical shape |
| `useDatatableQuery()` hook     | `useCallback` + `useEffect` + axios      |
| `DatatablePageHeaderBar`       | Inline header div with title + count     |
| Row actions (edit, delete)     | Inline buttons per row with `busy` state |
| `ConfirmationDialog`           | `window.confirm()` (sufficient for MVP)  |
| `react-hook-form` per resource | Each form in its own component           |
| `useMutation()` + invalidate   | Direct axios + `load()` re-fetch         |
| Settings via `useAdminSettings`| `GET/PATCH /api/v1/admin/settings/*`     |

---

## Available Admin API Endpoints

| Method | URL                                  | Description               |
|--------|--------------------------------------|---------------------------|
| GET    | `/api/v1/admin/users`                | Paginated user list       |
| PATCH  | `/api/v1/admin/users/{id}/role`      | Change user role          |
| DELETE | `/api/v1/admin/users/{id}`           | Delete user               |
| GET    | `/api/v1/admin/posts`                | Paginated post list       |
| DELETE | `/api/v1/admin/posts/{id}`           | Delete post               |
| PATCH  | `/api/v1/admin/posts/{id}/moderate`  | Approve / reject post     |
| GET    | `/api/v1/admin/events`               | Paginated event list      |
| DELETE | `/api/v1/admin/events/{id}`          | Delete event              |
| GET    | `/api/v1/admin/communities`          | Paginated community list  |
| DELETE | `/api/v1/admin/communities/{id}`     | Delete community          |
| GET    | `/api/v1/admin/pages`                | List custom pages         |
| POST   | `/api/v1/admin/pages`                | Create page               |
| PATCH  | `/api/v1/admin/pages/{id}`           | Update page metadata      |
| DELETE | `/api/v1/admin/pages/{id}`           | Delete page               |
| GET    | `/api/v1/admin/pages/{id}/builder`   | Get GrapesJS state        |
| PUT    | `/api/v1/admin/pages/{id}/builder`   | Save GrapesJS state       |
| GET    | `/api/v1/admin/settings`             | Platform settings         |
| PATCH  | `/api/v1/admin/settings`             | Update platform settings  |
| GET    | `/api/v1/admin/analytics`            | Dashboard analytics data  |

All endpoints require: `Authorization: Bearer {token}` + admin role.
