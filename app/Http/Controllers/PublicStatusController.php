<?php

namespace App\Http\Controllers;

use App\Services\PublicStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public status page (no login). Data comes from PublicStatusService's cached snapshot,
 * which holds nothing private. See docs/PUBLIC-STATUS-PLAN.md.
 */
class PublicStatusController extends Controller
{
    public function show(PublicStatusService $service): View
    {
        return view('public.status', ['status' => $service->snapshot()]);
    }

    /** Incident history by month; ?category=<id> narrows it to one category. */
    public function history(Request $request, PublicStatusService $service): View
    {
        $category = filter_var($request->query('category'), FILTER_VALIDATE_INT);

        return view('public.history', ['history' => $service->history($category === false ? null : $category)]);
    }

    public function json(PublicStatusService $service): JsonResponse
    {
        $data = $service->snapshot();

        return response()
            ->json($data)
            ->header('Cache-Control', 'private, max-age=' . PublicStatusService::CACHE_SECONDS);
    }
}
