# Code Patterns — Church Platform

> Reference with `#file:.copilot/patterns.md` when asking Copilot to generate
> new plugins, models, controllers, or React pages.

## Gold-Standard Plugin (copy from Library plugin)

```
app/Plugins/{Name}/
  Models/{Name}.php                          # extends Illuminate\Database\Eloquent\Model
  Services/
    {Name}Loader.php                         # load single resource
    Paginate{Name}s.php                      # paginated list with filters
    Crupdate{Name}.php                       # create or update
    Delete{Name}s.php                        # bulk delete
  Controllers/{Name}Controller.php           # extends App\Http\Controllers\Controller
  Policies/{Name}Policy.php                  # extends Common\Core\BasePolicy  ← CRITICAL
  Requests/Modify{Name}.php                  # extends Illuminate\Foundation\Http\FormRequest
  Routes/api.php                             # auth:sanctum mutations
  Database/
    Migrations/YYYY_MM_DD_create_{name}s_table.php
    Seeders/{Name}PermissionSeeder.php       # permissions NOT in plugins.json
  Plugin.php
```

## Policy Pattern (ALWAYS this, never HandlesAuthorization)
```php
class BookPolicy extends Common\Core\BasePolicy
{
    public function view(User $user, Book $book): bool    { return true; }
    public function create(User $user): bool              { return $user->hasPermission('books.create'); }
    public function update(User $user, Book $book): bool  { return $user->hasPermission('books.update'); }
    public function delete(User $user, Book $book): bool  { return $user->hasPermission('books.delete'); }
}
```

## Route Pattern
```php
// app/Plugins/{Name}/Routes/api.php
Route::get('{name}s', [NameController::class, 'index']);           // public list
Route::get('{name}s/{item}', [NameController::class, 'show']);     // public show
Route::middleware('auth:sanctum')->group(function () {
    Route::post('{name}s', [NameController::class, 'store']);
    Route::put('{name}s/{item}', [NameController::class, 'update']);
    Route::delete('{name}s/{item}', [NameController::class, 'destroy']);
});
```

## Seeder Idempotency (ALWAYS guard against re-running)
```php
public function run(): void
{
    if (DB::table('{name}s')->count() > 0) return;  // early exit guard
    $relatedId = DB::table('related')->where('slug', 'x')->value('id')
        ?? DB::table('related')->insertGetId([...]);  // upsert pattern
}
```

## React Page Pattern
```tsx
// Always import apiClient — it auto-attaches Bearer token
import { apiClient } from '@app/common/http/api-client';
import { useQuery } from '@tanstack/react-query';

export function ExamplePage() {
  const { data, isLoading } = useQuery({
    queryKey: ['resource-name'],
    queryFn: () => apiClient.get('/resource').then(r => r.data),
  });

  return (
    // bg-[#0C0E12] for page, bg-[#161920] for cards
    // text-white for headings, text-gray-400 for secondary
    // border-white/5 for borders
    <div className="min-h-screen bg-[#0C0E12]">
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <h1 className="text-xl font-bold text-white">Title</h1>
      </div>
    </div>
  );
}
```

## TSX Import Aliases
```
@app/common/http/api-client       ← apiClient (Bearer token auto-attached)
@app/common/auth/use-auth         ← useAuth()
@app/common/stores                ← useNotificationStore (NOT @common/stores)
@app/common/core/bootstrap-data   ← useBootstrapStore
@common/*                         ← common/foundation/resources/client/* (vendored)
```

## config/plugins.json — Registration
```json
"{name}": { "enabled": true, "version": "1.0.0" }
```
- Only register if `app/Plugins/{Name}/` actually exists
- Set `enabled: false` for future/planned plugins
- NEVER add `permissions` arrays here

## Settings (never add migrations for settings)
```php
// Read
Setting::where('key', 'church_name')->value('value');
// Write
Setting::updateOrCreate(['key' => 'church_name'], ['value' => $value]);
```
