<?php

return [
    'docker_bin' => env('PRAKTIKUM_DOCKER_BIN', 'docker'),
    'default_python_image' => env('PRAKTIKUM_DEFAULT_PYTHON_IMAGE', 'python:3.14.6'),
    'default_mysql_image' => env('PRAKTIKUM_DEFAULT_MYSQL_IMAGE', 'mariadb:10.11.18'),
    'container_memory' => env('PRAKTIKUM_CONTAINER_MEMORY', '512m'),
    'container_cpus' => env('PRAKTIKUM_CONTAINER_CPUS', '1'),
    'exec_timeout_seconds' => (int) env('PRAKTIKUM_EXEC_TIMEOUT_SECONDS', 5),
];
