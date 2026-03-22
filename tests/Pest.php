<?php

uses(Tests\TestCase::class)->in('Feature');

// Worktree support: apply TestCase to worktree Feature tests
$worktreesRoot = dirname(__DIR__) . '/.worktrees';
if (is_dir($worktreesRoot)) {
    foreach (glob($worktreesRoot . '/*/tests/Feature') as $worktreeFeature) {
        if (is_dir($worktreeFeature)) {
            uses(Tests\TestCase::class)->in($worktreeFeature);
        }
    }
}
