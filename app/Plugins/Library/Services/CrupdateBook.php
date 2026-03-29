<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class CrupdateBook
{
    public function execute(array $data, ?Book $book = null): Book
    {
        $fields = [
            'title', 'slug', 'author', 'description', 'content',
            'cover', 'pdf_path', 'isbn', 'publisher', 'pages_count',
            'published_year', 'category_id', 'is_featured', 'is_active',
            'meta_title', 'meta_description',
        ];

        if ($book) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $book->update($updateData);
        } else {
            $createData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            if (isset($data['uploaded_by'])) {
                $createData['uploaded_by'] = $data['uploaded_by'];
            }
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            $book = Book::create($createData);
        }

        return $book;
    }
}
