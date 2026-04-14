<?php

return [
    'docker_bin' => env('PRAKTIKUM_DOCKER_BIN', 'docker'),
    'default_python_image' => env('PRAKTIKUM_DEFAULT_PYTHON_IMAGE', 'python:3.12-slim'),
    'container_memory' => env('PRAKTIKUM_CONTAINER_MEMORY', '256m'),
    'container_cpus' => env('PRAKTIKUM_CONTAINER_CPUS', '0.5'),
    'exec_timeout_seconds' => (int) env('PRAKTIKUM_EXEC_TIMEOUT_SECONDS', 5),
];
