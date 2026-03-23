<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

// Worktree support: apply TestCase + RefreshDatabase to worktree Feature tests
$worktreesRoot = dirname(__DIR__) . '/.worktrees';
if (is_dir($worktreesRoot)) {
    foreach (glob($worktreesRoot . '/*/tests/Feature') as $worktreeFeature) {
        if (is_dir($worktreeFeature)) {
            uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)->in($worktreeFeature);
        }
    }
}
