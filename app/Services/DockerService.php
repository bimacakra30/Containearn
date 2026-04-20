<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class DockerService
{
    private string $bin;
    private int $execTimeout;

    public function __construct()
    {
        $this->bin         = config('praktikum.docker_bin', 'docker');
        $this->execTimeout = (int) config('praktikum.exec_timeout_seconds', 5);
    }

    public function startPythonContainer(string $containerName, ?string $image = null): array
    {
        $image = $image ?: config('praktikum.default_python_image', 'python:3.12-slim');

        $p = new Process([
            $this->bin,
            'run',
            '-d',
            '--rm',
            '--name',
            $containerName,
            '--network',
            'none',
            '--memory',
            (string) config('praktikum.container_memory', '256m'),
            '--cpus',
            (string) config('praktikum.container_cpus', '0.5'),
            '--pids-limit',
            '128',
            $image,
            'sleep',
            'infinity',
        ]);

        $p->setTimeout(30);
        $p->mustRun();

        return [
            'container_id'   => trim($p->getOutput()),
            'container_name' => $containerName,
        ];
    }

    public function writeFileToContainer(string $containerName, string $pathInContainer, string $content): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'practicum_');
        file_put_contents($tmp, $content);

        try {
            $p = new Process([$this->bin, 'cp', $tmp, "{$containerName}:{$pathInContainer}"]);
            $p->setTimeout(10);
            $p->mustRun();
        } finally {
            @unlink($tmp);
        }
    }

    public function runPythonFile(string $containerName, string $pathInContainer): array
    {
        $p = new Process([
            $this->bin,
            'exec',
            $containerName,
            'sh',
            '-lc',
            "timeout {$this->execTimeout}s python -u " . escapeshellarg($pathInContainer),
        ]);
        $p->setTimeout($this->execTimeout + 2);
        $p->run();

        $exitCode = $p->getExitCode();

        return [
            'exit_code' => $exitCode,
            'stdout'    => $p->getOutput(),
            'stderr'    => $exitCode === 124
                ? "Program exceeded time limit ({$this->execTimeout}s)."
                : $p->getErrorOutput(),
        ];
    }

    public function listActiveContainers(): array
    {
        $p = new Process([$this->bin, 'ps', '--format', '{{json .}}']);
        $p->setTimeout(10);
        $p->run();

        $lines = preg_split('/\r\n|\r|\n/', trim($p->getOutput())) ?: [];

        return collect($lines)
            ->filter(fn(string $line) => $line !== '')
            ->map(function (string $line): array {
                $container = json_decode($line, true);

                return [
                    'name' => (string) ($container['Names'] ?? '-'),
                    'status' => (string) ($container['Status'] ?? '-'),
                    'image' => (string) ($container['Image'] ?? '-'),
                ];
            })
            ->values()
            ->all();
    }

    public function destroyContainer(string $containerName): void
    {
        $p = new Process([$this->bin, 'rm', '-f', $containerName]);
        $p->setTimeout(10);
        $p->run();
    }
}
