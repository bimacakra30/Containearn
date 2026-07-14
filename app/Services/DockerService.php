<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class DockerService
{
    private string $bin;

    private int $execTimeout;

    public function __construct()
    {
        $this->bin = config('praktikum.docker_bin', 'docker');
        $this->execTimeout = (int) config('praktikum.exec_timeout_seconds', 5);
    }

    public function startPythonContainer(string $containerName, ?string $image = null): array
    {
        $image = $image ?: config('praktikum.default_python_image', 'python:3.14.6');

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
            'container_id' => trim($p->getOutput()),
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
            "timeout {$this->execTimeout}s python -u ".escapeshellarg($pathInContainer),
        ]);
        $p->setTimeout($this->execTimeout + 2);
        $p->run();

        $exitCode = $p->getExitCode();

        return [
            'exit_code' => $exitCode,
            'stdout' => $p->getOutput(),
            'stderr' => $exitCode === 124
                ? "Program exceeded time limit ({$this->execTimeout}s)."
                : $p->getErrorOutput(),
        ];
    }

    public function startMysqlContainer(string $containerName, ?string $image = null, ?string $password = null, string $database = 'practicum'): array
    {
        $password = $password ?: bin2hex(random_bytes(12));
        $image = $image ?: config('praktikum.default_mysql_image', 'mariadb:10.11.18');

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
            (string) config('praktikum.container_memory', '512m'),
            '--cpus',
            (string) config('praktikum.container_cpus', '1'),
            '--pids-limit',
            '128',
            '-e',
            'MYSQL_ROOT_PASSWORD='.$password,
            '-e',
            'MYSQL_DATABASE='.$database,
            $image,
        ]);

        $p->setTimeout(30);
        $p->mustRun();

        $this->waitForMysql($containerName, $password);

        return [
            'container_id' => trim($p->getOutput()),
            'container_name' => $containerName,
            'database' => $database,
            'password' => $password,
        ];
    }

    public function runMysqlScript(string $containerName, string $database, string $password, string $sql): array
    {
        return $this->executeMysqlScript($containerName, $database, $password, $sql, false);
    }

    public function queryMysqlRows(string $containerName, string $database, string $password, string $sql): array
    {
        $result = $this->executeMysqlScript($containerName, $database, $password, $sql, true);

        if ($result['exit_code'] !== 0) {
            throw new RuntimeException($result['stderr'] ?: 'SQL query failed.');
        }

        if (trim($result['stdout']) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($result['stdout'])) ?: [];
        $headers = str_getcsv(array_shift($lines), "\t", '"', '\\');

        return collect($lines)
            ->filter(fn (string $line) => $line !== '')
            ->map(function (string $line) use ($headers): array {
                $values = str_getcsv($line, "\t", '"', '\\');

                return collect($headers)
                    ->mapWithKeys(fn (string $header, int $index) => [$header => $values[$index] ?? null])
                    ->all();
            })
            ->values()
            ->all();
    }

    private function executeMysqlScript(string $containerName, string $database, string $password, string $sql, bool $captureRows): array
    {
        $path = '/tmp/practicum_'.bin2hex(random_bytes(8)).'.sql';
        $this->writeFileToContainer($containerName, $path, $sql);

        $flags = $captureRows ? ' --batch --raw' : '';
        $command = sprintf(
            'timeout %ds mysql -uroot -p%s%s %s < %s',
            $this->execTimeout,
            escapeshellarg($password),
            $flags,
            escapeshellarg($database),
            escapeshellarg($path),
        );

        $p = new Process([$this->bin, 'exec', $containerName, 'sh', '-lc', $command]);
        $p->setTimeout($this->execTimeout + 2);
        $p->run();

        $cleanup = new Process([$this->bin, 'exec', $containerName, 'rm', '-f', $path]);
        $cleanup->setTimeout(5);
        $cleanup->run();

        $exitCode = $p->getExitCode();

        return [
            'exit_code' => $exitCode,
            'stdout' => $p->getOutput(),
            'stderr' => $exitCode === 124
                ? "SQL execution exceeded time limit ({$this->execTimeout}s)."
                : $p->getErrorOutput(),
        ];
    }

    private function waitForMysql(string $containerName, string $password): void
    {
        $deadline = time() + 60;

        do {
            $p = new Process([
                $this->bin,
                'exec',
                $containerName,
                'mysqladmin',
                'ping',
                '-uroot',
                '-p'.$password,
                '--silent',
            ]);
            $p->setTimeout(5);
            $p->run();

            if ($p->isSuccessful()) {
                return;
            }

            usleep(500000);
        } while (time() < $deadline);

        throw new RuntimeException('MySQL container did not become ready in time.');
    }

    public function listActiveContainers(): array
    {
        $p = new Process([$this->bin, 'ps', '--format', '{{json .}}']);
        $p->setTimeout(10);
        $p->run();

        $lines = preg_split('/\r\n|\r|\n/', trim($p->getOutput())) ?: [];

        return collect($lines)
            ->filter(fn (string $line) => $line !== '')
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

    public function containerLogs(string $containerName): array
    {
        $p = new Process([$this->bin, 'logs', '--tail', '200', '--timestamps', $containerName]);
        $p->setTimeout(10);
        $p->run();

        return [
            'exit_code' => $p->getExitCode(),
            'stdout' => $p->getOutput(),
            'stderr' => $p->getErrorOutput(),
        ];
    }

    public function destroyContainer(string $containerName): void
    {
        $p = new Process([$this->bin, 'rm', '-f', $containerName]);
        $p->setTimeout(10);
        $p->run();
    }
}
