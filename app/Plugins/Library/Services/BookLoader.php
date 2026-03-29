<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class BookLoader
{
    public function load(Book $book): Book
    {
        return $book->load([
            'category:id,name,slug',
            'uploader:id,name,avatar',
        ])->loadCount('reactions');
    }

    public function loadForDetail(Book $book): array
    {
        $this->load($book);

        $data = $book->toArray();
        $data['has_pdf'] = $book->hasPdf();

        $userId = auth()->id();
        if ($userId) {
            $data['can_download'] = auth()->user()->hasPermission('library.download');
        }

        return $data;
    }
}
