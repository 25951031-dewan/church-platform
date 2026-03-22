<?php

// Set APP_BASE_PATH early so Application::inferBasePath() uses the worktree,
// not the main repo (which is what the symlinked vendor autoloader would otherwise
// resolve to via ClassLoader::getRegisteredLoaders()).
$worktreePath = dirname(__DIR__);
putenv('APP_BASE_PATH=' . $worktreePath);
$_ENV['APP_BASE_PATH'] = $worktreePath;
$_SERVER['APP_BASE_PATH'] = $worktreePath;

// Remove stale bootstrap caches that may have been generated against the wrong
// base path (e.g., the main repo's service/package discovery).
foreach (['services.php', 'packages.php', 'config.php'] as $cacheFile) {
    $path = $worktreePath . '/bootstrap/cache/' . $cacheFile;
    if (file_exists($path)) {
        unlink($path);
    }
}

$loader = require $worktreePath . '/vendor/autoload.php';

// Register worktree-specific PSR-4 namespaces so that Plugins\*, App\*, and
// Tests\* resolve to this worktree's directories rather than to whichever
// worktree the shared vendor autoloader was generated against.
$loader->setPsr4('Plugins\\', [$worktreePath . '/plugins']);
$loader->setPsr4('App\\',     [$worktreePath . '/app']);
$loader->setPsr4('Tests\\',   [$worktreePath . '/tests']);

// The shared vendor autoload_static.php has classmap entries pointing to
// sprint-1's app/ and plugins/ directories (classmap takes precedence over
// PSR-4, so setPsr4 alone is not enough).  Override those entries by scanning
// this worktree's PHP files and adding them to the classmap.
$classMap = [];
$scanDirs = [$worktreePath . '/app', $worktreePath . '/plugins', $worktreePath . '/database/factories'];
foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (!preg_match('/^\s*(?:namespace\s+([\w\\\\]+)\s*;)/m', $content, $nsMatch)) {
            continue;
        }
        $ns = $nsMatch[1];
        if (!preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+(\w+)/m', $content, $clsMatch)) {
            continue;
        }
        $classMap[$ns . '\\' . $clsMatch[1]] = $file->getPathname();
    }
}
$loader->addClassMap($classMap);
