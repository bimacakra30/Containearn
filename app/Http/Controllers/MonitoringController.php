<?php

namespace App\Http\Controllers;

use App\Services\DockerService;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(DockerService $docker): View
    {
        $containers = $docker->listActiveContainers();

        return view('admin.monitoring', [
            'containers' => $containers
        ]);
    }
}
