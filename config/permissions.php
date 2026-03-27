<?php
// Core foundation permissions. Each plugin will register its own.

return [
    'admin' => [
        'admin.access'           => 'Access Admin Panel',
        'admin.dashboard'        => 'View Dashboard Analytics',
    ],
    'users' => [
        'users.view'             => 'View User Profiles',
        'users.create'           => 'Create Users',
        'users.update'           => 'Edit Users',
        'users.delete'           => 'Delete Users',
        'users.impersonate'      => 'Login As User',
        'users.ban'              => 'Ban/Unban Users',
        'users.export'           => 'Export User Data',
    ],
    'roles' => [
        'roles.view'             => 'View Roles',
        'roles.create'           => 'Create Roles',
        'roles.update'           => 'Edit Roles',
        'roles.delete'           => 'Delete Roles',
        'roles.assign'           => 'Assign Roles to Users',
    ],
    'settings' => [
        'settings.view'          => 'View Settings',
        'settings.update'        => 'Update Settings',
    ],
    'files' => [
        'files.upload'           => 'Upload Files',
        'files.delete'           => 'Delete Any File',
        'files.manage'           => 'Manage File Storage',
    ],
    'appearance' => [
        'appearance.themes'      => 'Manage Themes',
        'appearance.menus'       => 'Manage Navigation Menus',
        'appearance.custom_code' => 'Edit Custom CSS/JS',
    ],
    'localizations' => [
        'localizations.view'     => 'View Translations',
        'localizations.update'   => 'Edit Translations',
    ],
    'seo' => [
        'seo.manage'             => 'Manage SEO Settings',
        'seo.sitemap'            => 'Generate Sitemap',
    ],
];
