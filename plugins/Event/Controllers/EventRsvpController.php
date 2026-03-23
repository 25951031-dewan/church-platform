<?php
namespace Plugins\Event\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class EventRsvpController extends Controller {
    public function update(Request $request, int $id): JsonResponse { return response()->json([]); }
    public function destroy(Request $request, int $id): JsonResponse { return response()->json([]); }
}
