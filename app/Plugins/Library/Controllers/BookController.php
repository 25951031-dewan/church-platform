<?php

namespace App\Plugins\Library\Controllers;

use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Requests\ModifyBook;
use App\Plugins\Library\Services\BookLoader;
use App\Plugins\Library\Services\CrupdateBook;
use App\Plugins\Library\Services\DeleteBooks;
use App\Plugins\Library\Services\PaginateBooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function __construct(
        private BookLoader $loader,
        private CrupdateBook $crupdate,
        private PaginateBooks $paginator,
        private DeleteBooks $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Book::class);
        $books = $this->paginator->execute($request);
        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        Gate::authorize('view', $book);
        $book->incrementView();
        $book->refresh();
        return response()->json(['book' => $this->loader->loadForDetail($book)]);
    }

    public function store(ModifyBook $request): JsonResponse
    {
        Gate::authorize('create', Book::class);

        $data = $request->validated();
        $data['uploaded_by'] = $request->user()->id;

        $book = $this->crupdate->execute($data);

        return response()->json([
            'book' => $this->loader->loadForDetail($book),
        ], 201);
    }

    public function update(ModifyBook $request, Book $book): JsonResponse
    {
        Gate::authorize('update', $book);

        $book = $this->crupdate->execute($request->validated(), $book);

        return response()->json([
            'book' => $this->loader->loadForDetail($book),
        ]);
    }

    public function destroy(Book $book): Response
    {
        Gate::authorize('delete', $book);

        $this->deleter->execute([$book->id]);

        return response()->noContent();
    }

    public function trackDownload(Book $book): JsonResponse
    {
        Gate::authorize('download', $book);

        if (!$book->hasPdf()) {
            return response()->json(['message' => 'No PDF available for this book.'], 404);
        }

        $book->incrementDownload();
        $book->refresh();

        return response()->json([
            'pdf_url' => Storage::url($book->pdf_path),
            'download_count' => $book->download_count,
        ]);
    }
}
