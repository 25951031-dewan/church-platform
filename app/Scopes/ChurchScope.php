<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ChurchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Single-church platform: scope is permanently disabled.
        // Church entities act as Pages (like Facebook Pages), not tenants.
    }
}
