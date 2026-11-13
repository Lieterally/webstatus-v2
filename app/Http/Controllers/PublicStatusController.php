<?php

namespace App\Http\Controllers;

use App\Services\PublicStatusService;
use Illuminate\Http\JsonResponse;
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

    public function json(PublicStatusService $service): JsonResponse
    {
        $data = $service->snapshot();

        return response()
            ->json($data)
            ->header('Cache-Control', 'private, max-age=' . PublicStatusService::CACHE_SECONDS);
    }
}
