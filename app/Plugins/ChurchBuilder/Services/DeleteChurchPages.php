<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\ChurchPage;

class DeleteChurchPages
{
    public function execute(array $ids): void
    {
        ChurchPage::whereIn('id', $ids)->delete();
    }
}
