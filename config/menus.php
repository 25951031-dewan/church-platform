<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Menu Positions
    |--------------------------------------------------------------------------
    |
    | Define all available menu positions in the application.
    | Each position can be used to display a menu in specific areas.
    |
    */
    'positions' => [
        ['name' => 'header', 'label' => 'Header Navigation', 'route' => '/'],
        ['name' => 'footer', 'label' => 'Footer', 'route' => '/'],
        ['name' => 'footer-secondary', 'label' => 'Footer Secondary', 'route' => '/'],
        ['name' => 'auth-dropdown', 'label' => 'User Dropdown Menu', 'route' => '/'],
        ['name' => 'mobile-nav', 'label' => 'Mobile Navigation', 'route' => '/'],
        ['name' => 'admin-sidebar', 'label' => 'Admin Sidebar', 'route' => '/admin'],
        ['name' => 'custom-page-navbar', 'label' => 'Custom Page Navbar', 'route' => '/'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Routes
    |--------------------------------------------------------------------------
    |
    | Routes that can be used in menu items.
    | These appear in the menu editor dropdown for quick selection.
    |
    */
    'available_routes' => [
        // Public Pages
        ['label' => 'Home', 'route' => '/', 'type' => 'route'],
        ['label' => 'Feed / Newsfeed', 'route' => '/feed', 'type' => 'route'],
        ['label' => 'Events', 'route' => '/events', 'type' => 'route'],
        ['label' => 'Sermons', 'route' => '/sermons', 'type' => 'route'],
        ['label' => 'Groups', 'route' => '/groups', 'type' => 'route'],
        ['label' => 'Prayer Wall', 'route' => '/prayer', 'type' => 'route'],
        ['label' => 'Blog', 'route' => '/blog', 'type' => 'route'],
        ['label' => 'Library / Media', 'route' => '/library', 'type' => 'route'],
        ['label' => 'Church Directory', 'route' => '/churches', 'type' => 'route'],
        ['label' => 'Live Meetings', 'route' => '/meetings', 'type' => 'route'],
        ['label' => 'Contact', 'route' => '/contact', 'type' => 'route'],

        // Auth Pages
        ['label' => 'Login', 'route' => '/login', 'type' => 'route'],
        ['label' => 'Register', 'route' => '/register', 'type' => 'route'],

        // User Pages
        ['label' => 'My Profile', 'route' => '/profile', 'type' => 'route'],
        ['label' => 'Notifications', 'route' => '/notifications', 'type' => 'route'],
        ['label' => 'Settings', 'route' => '/settings', 'type' => 'route'],

        // Admin
        ['label' => 'Admin Dashboard', 'route' => '/admin', 'type' => 'route'],
        ['label' => 'Admin Settings', 'route' => '/admin/settings', 'type' => 'route'],
    ],
];
