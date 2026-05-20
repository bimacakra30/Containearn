<?php

namespace App\Http\Controllers;

use App\Services\DockerService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(DockerService $docker): View
    {
        $containers = $docker->listActiveContainers();

        return view('admin.monitoring', [
            'containers' => $containers,
        ]);
    }

    public function logs(string $containerName, DockerService $docker): JsonResponse
    {
        abort_unless((bool) preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\z/', $containerName), 404);

        $result = $docker->containerLogs($containerName);
        $logs = trim($result['stdout'].$result['stderr']);

        return response()->json([
            'container' => $containerName,
            'logs' => $logs !== '' ? $logs : 'No logs are available for this container.',
            'exit_code' => $result['exit_code'],
        ], $result['exit_code'] === 0 ? 200 : 422);
    }
}
