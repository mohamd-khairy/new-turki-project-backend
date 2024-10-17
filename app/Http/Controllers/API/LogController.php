<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-log', ['only' => ['index']]);
        }
    }


    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $activityLogs = Activity::with('causer', 'subject')
            ->when(request('q'), function ($q) {
                $q->where('subject_id', 'like',  '%' . request('q') . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate(request('per_page', 10));

        return successResponse($activityLogs);
    }
}
