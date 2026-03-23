<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Routing\Controller;

class InstallerController extends Controller
{
    public function step1()
    {
        return response('step1');
    }

    public function postStep1()
    {
        return redirect('/install/step2');
    }

    public function step2()
    {
        return response('step2');
    }

    public function postStep2()
    {
        return redirect('/install/step3');
    }

    public function step3()
    {
        return response('step3');
    }

    public function postStep3()
    {
        return redirect('/install/complete');
    }

    public function complete()
    {
        return response('complete');
    }
}
