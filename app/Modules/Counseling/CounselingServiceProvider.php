<?php

namespace App\Modules\Counseling;

use App\Modules\Counseling\Models\CounselingThread;
use App\Modules\Counseling\Policies\CounselingPolicy;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class CounselingServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = 'counseling';

    protected function bootModule(): void
    {
        parent::bootModule();

        Gate::policy(CounselingThread::class, CounselingPolicy::class);
    }
}
