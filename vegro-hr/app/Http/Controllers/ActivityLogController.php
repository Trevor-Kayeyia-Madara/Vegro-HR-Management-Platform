<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(?Request $request = null)
    {
        $request ??= request();
        $query = ActivityLog::with(['company', 'actor'])->orderBy('created_at', 'desc');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        $perPage = max((int) $request->query('per_page', 50), 1);
        return ApiResponse::success($query->paginate($perPage), 'Activity logs retrieved successfully');
    }
}
