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
    ],

    'mahasiswa' => [
        [
            'label' => 'Dashboard',
            'route' => 'mahasiswa.dashboard',
            'active' => ['mahasiswa.dashboard']
        ],
        [
            'label' => 'Practicum Content',
            'route' => 'mahasiswa.content.index',
            'active' => ['mahasiswa.content.*']
        ],
        [
            'label' => 'Profile',
            'route' => 'mahasiswa.profile',
            'active' => ['mahasiswa.profile']
        ],
    ],

];
