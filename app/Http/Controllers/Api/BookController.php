<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    /**
     * Display a paginated listing of books.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $books = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $books,
        ]);
    }

    /**
     * Display featured books.
     */
    public function featured(Request $request): JsonResponse
    {
        $books = Book::where('is_featured', true)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $books,
        ]);
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $book,
        ]);
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category'    => 'nullable|string|max:255',
            'isbn'        => 'nullable|string|max:20',
            'publisher'   => 'nullable|string|max:255',
            'publish_year' => 'nullable|integer|min:1000|max:2100',
            'pages'       => 'nullable|integer|min:1',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:51200',
            'is_featured' => 'nullable|boolean',
            'is_free'     => 'nullable|boolean',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('books/covers', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            $validated['pdf_file'] = $request->file('pdf_file')
                ->store('books/pdfs', 'public');
        }

        $validated['download_count'] = 0;

        $book = Book::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Book created successfully.',
            'data'    => $book,
        ], 201);
    }

    /**
     * Update the specified book.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'author'      => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category'    => 'nullable|string|max:255',
            'isbn'        => 'nullable|string|max:20',
            'publisher'   => 'nullable|string|max:255',
            'publish_year' => 'nullable|integer|min:1000|max:2100',
            'pages'       => 'nullable|integer|min:1',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:51200',
            'is_featured' => 'nullable|boolean',
            'is_free'     => 'nullable|boolean',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')
                ->store('books/covers', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            if ($book->pdf_file) {
                Storage::disk('public')->delete($book->pdf_file);
            }
            $validated['pdf_file'] = $request->file('pdf_file')
                ->store('books/pdfs', 'public');
        }

        $book->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully.',
            'data'    => $book->fresh(),
        ]);
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book): JsonResponse
    {
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        if ($book->pdf_file) {
            Storage::disk('public')->delete($book->pdf_file);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully.',
        ]);
    }

    /**
     * Download the book PDF and increment download count.
     */
    public function download(Book $book): JsonResponse
    {
        if (!$book->pdf_file || !Storage::disk('public')->exists($book->pdf_file)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF file not found for this book.',
            ], 404);
        }

        $book->increment('download_count');

        $url = Storage::disk('public')->url($book->pdf_file);

        return response()->json([
            'success'        => true,
            'message'        => 'Download link generated successfully.',
            'data'           => [
                'download_url'   => $url,
                'filename'       => basename($book->pdf_file),
                'download_count' => $book->fresh()->download_count,
            ],
        ]);
    }
}
