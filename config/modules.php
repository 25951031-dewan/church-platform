<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Modules
    |--------------------------------------------------------------------------
    |
    | Each module has a name, scope, and default enabled state.
    |
    | scope:
    |   global       - content shared across all churches (e.g., daily verse)
    |   church       - strictly scoped to each church
    |   configurable - admin can choose global or church scope at runtime
    |
    */

    'sermons' => [
        'name'    => 'Sermons',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'mic',
    ],
    'events' => [
        'name'    => 'Events',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'calendar',
    ],
    'prayer' => [
        'name'    => 'Prayer Requests',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'hand',
    ],
    'bible_studies' => [
        'name'    => 'Bible Studies',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'book-open',
    ],
    'gallery' => [
        'name'    => 'Gallery',
        'scope'   => 'church',
        'default' => true,
        'icon'    => 'image',
    ],
    'newsletter' => [
        'name'    => 'Newsletter',
        'scope'   => 'church',
        'default' => true,
        'icon'    => 'mail',
    ],
    'donations' => [
        'name'    => 'Donations',
        'scope'   => 'church',
        'default' => true,
        'icon'    => 'heart',
    ],
    'testimonies' => [
        'name'    => 'Testimonies',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'star',
    ],
    'directory' => [
        'name'    => 'Church Directory',
        'scope'   => 'global',
        'default' => true,
        'icon'    => 'map-pin',
    ],
    'community' => [
        'name'    => 'Community',
        'scope'   => 'configurable',
        'default' => true,
        'icon'    => 'users',
    ],
    'counseling' => [
        'name'    => 'Counseling',
        'scope'   => 'church',
        'default' => false,
        'icon'    => 'message-circle',
    ],
    'announcements' => [
        'name'    => 'Announcements',
        'scope'   => 'church',
        'default' => true,
        'icon'    => 'bell',
    ],
    'ministries' => [
        'name'    => 'Ministries',
        'scope'   => 'church',
        'default' => true,
        'icon'    => 'grid',
    ],
];
