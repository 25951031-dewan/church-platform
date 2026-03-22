<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * List media files.
     *
     * @group Admin / Media
     *
     * @queryParam mime_type string optional Filter by MIME type prefix (e.g. "image/"). Example: image/
     * @queryParam search string optional Search by file name. Example: photo
     *
     * @response 200 {
     *   "data": [{"id":1,"name":"photo.jpg","mime_type":"image/jpeg","size":204800,"url":"http://..."}],
     *   "stats": {"used": 204800, "total": 10737418240, "count": 1}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->get('church_id');

        $query = DB::table('media_files')
            ->when($churchId, fn ($q) => $q->where('church_id', $churchId))
            ->when($request->mime_type, fn ($q) => $q->where('mime_type', 'like', $request->mime_type . '%'))
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderByDesc('created_at');

        $files = $query->get();

        $data = $files->map(fn ($f) => [
            'id'        => $f->id,
            'name'      => $f->name,
            'mime_type' => $f->mime_type,
            'size'      => $f->size,
            'url'       => Storage::disk($f->disk)->url($f->path),
            'created_at' => $f->created_at,
        ]);

        $usedBytes = $files->sum('size');
        $maxUploadMb = $this->maxUploadMb();

        return response()->json([
            'data'  => $data,
            'stats' => [
                'used'  => $usedBytes,
                'total' => $maxUploadMb * 1024 * 1024 * 100, // display quota = 100× max upload size
                'count' => $files->count(),
            ],
        ]);
    }

    /**
     * Upload one or more media files.
     *
     * @group Admin / Media
     *
     * @bodyParam files[] file[] required Files to upload.
     *
     * @response 201 {"uploaded": 2, "errors": []}
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => [
                'file',
                'max:' . ($this->maxUploadMb() * 1024),
                'mimes:jpg,jpeg,png,gif,webp,svg,mp4,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,zip',
            ],
        ]);

        $churchId  = $request->get('church_id');
        $uploadedBy = $request->user()?->id;
        $uploaded  = 0;
        $errors    = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $original  = $file->getClientOriginalName();
                $sanitised = Str::slug(pathinfo($original, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path      = $file->store('media/' . now()->format('Y/m'), 'public');

                DB::table('media_files')->insert([
                    'name'          => $sanitised,
                    'original_name' => $original,
                    'mime_type'     => $file->getMimeType(),
                    'path'          => $path,
                    'disk'          => 'public',
                    'size'          => $file->getSize(),
                    'church_id'     => $churchId,
                    'uploaded_by'   => $uploadedBy,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $uploaded++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return response()->json(['uploaded' => $uploaded, 'errors' => $errors], 201);
    }

    /**
     * Bulk delete media files.
     *
     * @group Admin / Media
     *
     * @bodyParam ids integer[] required IDs of files to delete. Example: [1, 2, 3]
     *
     * @response 200 {"deleted": 3}
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $files = DB::table('media_files')->whereIn('id', $request->ids)->get();

        foreach ($files as $file) {
            Storage::disk($file->disk)->delete($file->path);
        }

        DB::table('media_files')->whereIn('id', $request->ids)->delete();

        return response()->json(['deleted' => $files->count()]);
    }

    private function maxUploadMb(): int
    {
        $row = DB::table('settings')->where('key', 'storage')->first();

        if ($row && $row->value) {
            $data = json_decode($row->value, true);
            return (int) ($data['max_upload_mb'] ?? 10);
        }

        return 10;
    }
}
