---
applyTo: "tests/**/*.php"
---

# Test Rules — Church Platform

## User Model Import
```php
// ALWAYS
use App\Models\User;

// NEVER
use Common\Auth\Models\User;
```

## Test setup pattern
```php
public function test_example(): void
{
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');  // Bearer token guard

    $response = $this->getJson('/api/v1/resource');
    $response->assertOk()->assertJsonStructure(['data' => [...]]);
}
```

## Factory location: `database/factories/`
## Feature tests location: `tests/Feature/{PluginName}/`
