<?php

$worktreePath = dirname(__DIR__);
putenv('APP_BASE_PATH=' . $worktreePath);
$_ENV['APP_BASE_PATH'] = $worktreePath;
$_SERVER['APP_BASE_PATH'] = $worktreePath;

foreach (['services.php', 'packages.php', 'config.php'] as $cacheFile) {
    $path = $worktreePath . '/bootstrap/cache/' . $cacheFile;
    if (file_exists($path)) {
        unlink($path);
    }
}

$loader = require $worktreePath . '/vendor/autoload.php';

$loader->setPsr4('Plugins\\', [$worktreePath . '/plugins']);
$loader->setPsr4('App\\',     [$worktreePath . '/app']);
$loader->setPsr4('Tests\\',   [$worktreePath . '/tests']);

$classMap = [];
$scanDirs = [$worktreePath . '/app', $worktreePath . '/plugins', $worktreePath . '/database/factories'];
foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        if (!preg_match('/^\s*(?:namespace\s+([\w\\\\]+)\s*;)/m', $content, $nsMatch)) continue;
        $ns = $nsMatch[1];
        if (!preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+(\w+)/m', $content, $clsMatch)) continue;
        $classMap[$ns . '\\' . $clsMatch[1]] = $file->getPathname();
    }
}
$loader->addClassMap($classMap);
