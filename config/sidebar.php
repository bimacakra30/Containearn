<?php

return [

    'superadmin' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard'],
        ],
        [
            'label' => 'Profile',
            'route' => 'admin.profile',
            'active' => ['admin.profile'],
        ],
        [
            'label' => 'Users Management',
            'route' => 'admin.user.index',
            'active' => ['admin.user.*'],
        ],
    ],

    'dosen' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard'],
        ],
        [
            'label' => 'Profile',
            'route' => 'admin.profile',
            'active' => ['admin.profile'],
        ],
        [
            'label' => 'Practicum Contents',
            'route' => 'admin.contents.index',
            'active' => ['admin.contents.*'],
        ],
        [
            'label' => 'Reports',
            'route' => 'admin.reports.index',
            'active' => ['admin.reports.*'],
        ],

        [
            'label' => 'Monitoring',
            'route' => 'admin.monitoring.index',
            'active' => ['admin.monitoring.*'],
        ],
    ],

    'mahasiswa' => [
        [
            'label' => 'Dashboard',
            'route' => 'mahasiswa.dashboard',
            'active' => ['mahasiswa.dashboard'],
        ],
        [
            'label' => 'Profile',
            'route' => 'mahasiswa.profile',
            'active' => ['mahasiswa.profile'],
        ],
        [
            'label' => 'Practicum Content',
            'route' => 'mahasiswa.content.index',
            'active' => ['mahasiswa.content.*'],
        ],
    ],

];
