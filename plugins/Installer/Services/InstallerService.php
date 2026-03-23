<?php

namespace Plugins\Installer\Services;

use App\Models\Church;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InstallerService
{
    private string $envPath;

    private string $basePath;

    private string $publicPath;

    private string $storagePath;

    public function __construct(
        ?string $envPath = null,
        ?string $basePath = null,
    ) {
        $this->basePath = $basePath ?? base_path();
        $this->envPath = $envPath ?? $this->basePath.'/.env';
        $this->publicPath = $this->basePath.'/public';
        $this->storagePath = $this->basePath.'/storage';
    }

    // ── .env management ───────────────────────────────────────────────────────

    /**
     * Safely update key=value pairs in .env.
     * Uses atomic write (temp file + rename) to prevent half-written reads by
     * concurrent child artisan processes. Multi-word values are always quoted.
     */
    public function updateEnv(array $values): void
    {
        $lines = file_exists($this->envPath) ? file($this->envPath, FILE_IGNORE_NEW_LINES) : [];
        $updated = [];

        foreach ($lines as $line) {
            $matched = false;
            foreach ($values as $key => $value) {
                if (preg_match('/^'.preg_quote($key, '/').'=/', $line)) {
                    $updated[] = $this->formatEnvLine($key, $value);
                    unset($values[$key]);
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                $updated[] = $line;
            }
        }

        foreach ($values as $key => $value) {
            $updated[] = $this->formatEnvLine($key, $value);
        }

        $tmp = $this->envPath.'.tmp.'.uniqid();
        file_put_contents($tmp, implode("\n", $updated)."\n");
        rename($tmp, $this->envPath);
    }

    private function formatEnvLine(string $key, mixed $value): string
    {
        $value = (string) $value;
        if (str_contains($value, ' ') || str_contains($value, '#') || $value === '') {
            return $key.'="'.addslashes($value).'"';
        }

        return $key.'='.$value;
    }

    // ── Directories + permissions ─────────────────────────────────────────────

    public function prepareDirectories(): void
    {
        $bootstrapCache = $this->basePath.'/bootstrap/cache';
        if (! is_dir($bootstrapCache)) {
            mkdir($bootstrapCache, 0775, true);
        }
        chmod($bootstrapCache, 0775);

        foreach ($this->storageDirs() as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            chmod($dir, 0775);
        }
    }

    private function storageDirs(): array
    {
        return [
            $this->storagePath,
            $this->storagePath.'/app',
            $this->storagePath.'/app/public',
            $this->storagePath.'/framework',
            $this->storagePath.'/framework/cache',
            $this->storagePath.'/framework/sessions',
            $this->storagePath.'/framework/views',
            $this->storagePath.'/logs',
        ];
    }

    // ── .htaccess ─────────────────────────────────────────────────────────────

    public function writeRootHtaccess(): void
    {
        $path = $this->basePath.'/.htaccess';
        if (file_exists($path)) {
            return;
        }

        file_put_contents($path, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>
HTACCESS);
    }

    public function writePublicHtaccess(): void
    {
        $path = $this->publicPath.'/.htaccess';
        if (file_exists($path)) {
            return;
        }

        file_put_contents($path, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS);
    }

    // ── Step 1 env write ──────────────────────────────────────────────────────

    public function prepareEnvironment(): void
    {
        $this->prepareDirectories();
        $this->writeRootHtaccess();
        $this->writePublicHtaccess();

        if (! file_exists($this->envPath) && file_exists($this->basePath.'/.env.example')) {
            copy($this->basePath.'/.env.example', $this->envPath);
        }
    }

    public function checkRequirements(): array
    {
        return [
            'php' => PHP_VERSION_ID >= 80200,
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'bcmath' => extension_loaded('bcmath'),
            'storage' => is_writable($this->storagePath),
            'bootstrap_cache' => is_writable($this->basePath.'/bootstrap/cache'),
            'root_htaccess' => file_exists($this->basePath.'/.htaccess'),
            'public_htaccess' => file_exists($this->publicPath.'/.htaccess'),
            'vendor' => is_dir($this->basePath.'/vendor'),
        ];
    }

    public function writeStep1Env(string $appUrl): void
    {
        $this->updateEnv([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
        ]);
        Artisan::call('key:generate', ['--force' => true]);
        // key:generate writes APP_KEY to disk. The caller MUST redirect after this
        // so the next request re-bootstraps and loads the key from disk.
    }

    // ── Database (Step 2) ─────────────────────────────────────────────────────

    public function testConnection(array $config): bool
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            new \PDO($dsn, $config['username'], $config['password']);

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function runMigrations(): void
    {
        // PHP_BINARY ensures the same PHP version runs migrations as serves the web request.
        Process::run([PHP_BINARY, $this->basePath.'/artisan', 'migrate', '--force'])->throw();
    }

    // ── Finalise (Step 3) ─────────────────────────────────────────────────────

    public function seedRoles(): void
    {
        foreach (['admin', 'church_leader', 'member'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    public function createAdmin(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->assignRole('admin');

        return $user;
    }

    public function createDefaultChurch(string $name, int $createdBy): Church
    {
        return Church::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }

    public function createStorageLink(): void
    {
        Artisan::call('storage:link', ['--force' => true]);
    }

    public function lockInstaller(): void
    {
        // MUST be called before warmCaches() so installer routes are absent from route cache.
        file_put_contents(storage_path('installed.lock'), now()->toIso8601String());
    }

    public function warmCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
    }
}
