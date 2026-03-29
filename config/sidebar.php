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
    ],

];
