<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\ChurchContext;

class ChurchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Only filter in multi-church mode
        if (config('app.church_mode', 'single') !== 'multi') {
            return;
        }

        $context = app(ChurchContext::class);

        if ($context->has()) {
            $builder->where($model->getTable() . '.church_id', $context->getId());
        }
    }
}
