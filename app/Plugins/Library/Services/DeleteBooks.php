<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class DeleteBooks
{
    public function execute(array $ids): void
    {
        $books = Book::whereIn('id', $ids)->get();

        foreach ($books as $book) {
            $book->reactions()->delete();
            $book->delete();
        }
    }
}
