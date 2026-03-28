<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class DeleteSermons
{
    public function execute(array $sermonIds): void
    {
        $sermons = Sermon::whereIn('id', $sermonIds)->get();

        foreach ($sermons as $sermon) {
            $sermon->reactions()->delete();
            $sermon->comments()->delete();
            $sermon->delete();
        }
    }
}
