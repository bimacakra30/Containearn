<?php

return [

    'superadmin' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard']
        ],
        [
            'label' => 'Profile',
            'route' => 'admin.profile',
            'active' => ['admin.profile']
        ],
        [
            'label' => 'Users Management',
            'route' => 'admin.users.index',
            'active' => ['admin.users.*']
        ],
    ],

    'dosen' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard']
        ],
        [
            'label' => 'Profile',
            'route' => 'admin.profile',
            'active' => ['admin.profile']
        ],
        [
            'label' => 'Users Management',
            'route' => 'admin.users.index',
            'active' => ['admin.users.*']
        ],
        [
            'label' => 'Practicum Contents',
            'route' => 'admin.contents.index',
            'active' => ['admin.contents.*']
        ],

        [
            'label' => 'Monitoring',
            'route' => 'admin.monitoring.index',
            'active' => ['admin.monitoring.*']
        ],
    ],

    'mahasiswa' => [
        [
            'label' => 'Dashboard',
            'route' => 'mahasiswa.dashboard',
            'active' => ['mahasiswa.dashboard']
        ],
        [
            'label' => 'Profile',
            'route' => 'mahasiswa.profile',
            'active' => ['mahasiswa.profile']
        ],
        [
            'label' => 'Practicum Content',
            'route' => 'mahasiswa.content.index',
            'active' => ['mahasiswa.content.*']
        ],
    ],

];
