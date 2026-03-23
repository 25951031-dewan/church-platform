<?php
namespace Plugins\Event\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class EventPostController extends Controller {
    public function index(int $id): JsonResponse { return response()->json([]); }
    public function store(Request $request, int $id): JsonResponse { return response()->json([]); }
}
