<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Plugins\Installer\Services\UpdaterService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdaterController extends Controller
{
    public function __construct(private UpdaterService $service) {}

    public function dashboard(): View
    {
        $versionInfo = $this->service->checkForUpdate();

        return view('installer::installer.update', compact('versionInfo'));
    }

    public function run(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () {
            if (ob_get_level()) {
                ob_end_clean();
            }

            $this->service->runUpdate(function (string $step, string $status, string $message) {
                echo 'data: '.json_encode(compact('step', 'status', 'message'))."\n\n";
                ob_flush();
                flush();
            });

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
