<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Routing\Controller;

class UpdaterController extends Controller
{
    public function dashboard()
    {
        return response('update dashboard');
    }

    public function run()
    {
        return response('update run');
    }
}
