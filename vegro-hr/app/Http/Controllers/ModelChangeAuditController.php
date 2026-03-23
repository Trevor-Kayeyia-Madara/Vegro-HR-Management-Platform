<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\ModelChangeAudit;
use Illuminate\Http\Request;

class ModelChangeAuditController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 50), 1);
        $query = ModelChangeAudit::query()
            ->with('actor:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', $request->query('actor_user_id'));
        }

        return ApiResponse::success($query->paginate($perPage), 'Detailed audits retrieved');
    }
}
