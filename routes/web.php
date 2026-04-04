<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Installer\InstallerController;
use App\Http\Controllers\PublicContentController;
use Common\Core\BootstrapDataService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Installer Routes
Route::prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('installer.welcome');
    Route::get('/database', [InstallerController::class, 'database'])->name('installer.database');
    Route::post('/database', [InstallerController::class, 'saveDatabase']);
    Route::get('/admin', [InstallerController::class, 'admin'])->name('installer.admin');
    Route::post('/admin', [InstallerController::class, 'saveAdmin']);
});

// Auth Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Social Auth Routes
Route::get('/auth/{provider}/redirect', [AuthController::class, 'socialRedirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('social.callback');

// Admin routes are handled by the React SPA catch-all below.

// Sitemap (SEO)
Route::get('/sitemap.xml', [\App\Http\Controllers\Api\SitemapController::class, 'index'])->name('sitemap');

// Dynamic PWA Manifest
Route::get('/manifest.json', function () {
    $s = \Common\Settings\Models\Setting::pluck('value', 'key');
    $manifest = [
        'name' => $s['pwa_name'] ?? $s['church_name'] ?? config('app.name', 'Grace Community Church'),
        'short_name' => $s['pwa_short_name'] ?? 'Church',
        'description' => $s['pwa_description'] ?? 'Your church community app - worship, events, prayer, and more.',
        'start_url' => '/',
        'display' => $s['pwa_display'] ?? 'standalone',
        'background_color' => $s['pwa_background_color'] ?? '#0C0E12',
        'theme_color' => $s['pwa_theme_color'] ?? '#0C0E12',
        'orientation' => $s['pwa_orientation'] ?? 'portrait-primary',
        'icons' => [
            [
                'src' => !empty($s['pwa_icon_192']) ? '/storage/' . $s['pwa_icon_192'] : '/images/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => !empty($s['pwa_icon_512']) ? '/storage/' . $s['pwa_icon_512'] : '/images/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
        'categories' => ['lifestyle', 'social'],
        'lang' => 'en',
    ];

    return response()->json($manifest)
        ->header('Content-Type', 'application/manifest+json')
        ->header('Cache-Control', 'public, max-age=3600');
});

// Public Content Pages (SSR with clean URLs)
if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
    Route::get('/blog/{slug}', function ($slug) {
        $article = \App\Plugins\Blog\Models\Article::where('slug', $slug)
            ->published()
            ->with('author')
            ->firstOrFail();

        return view('public.article', compact('article'));
    })->name('public.article');
} else {
    Route::get('/blog/{slug}', [PublicContentController::class, 'post'])->name('public.post');
}
Route::get('/page/{slug}', [PublicContentController::class, 'page'])->name('public.page');
Route::get('/ministries', [PublicContentController::class, 'ministries'])->name('public.ministries');
Route::get('/ministries/{slug}', [PublicContentController::class, 'ministry'])->name('public.ministry');
Route::get('/bible-studies', [PublicContentController::class, 'bibleStudies'])->name('public.bible-studies');
Route::get('/bible-studies/{slug}', [PublicContentController::class, 'bibleStudy'])->name('public.bible-study');
Route::get('/library', [PublicContentController::class, 'library'])->name('public.library');
Route::get('/library/{slug}', [PublicContentController::class, 'book'])->name('public.book');
Route::get('/about', [PublicContentController::class, 'about'])->name('public.about');

// SPA catch-all — serves React app with bootstrap data
Route::get('/{any?}', function (\Illuminate\Http\Request $request, BootstrapDataService $bootstrap) {
    return view('app', ['bootstrapData' => $bootstrap->get()]);
})->where('any', '.*')->name('home');
