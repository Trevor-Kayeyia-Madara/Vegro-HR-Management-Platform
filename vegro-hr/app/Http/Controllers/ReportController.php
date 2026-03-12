<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\ReportDefinition;
use App\Services\ReportEngine;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportEngine $engine;

    public function __construct(ReportEngine $engine)
    {
        $this->engine = $engine;
    }

    public function metadata()
    {
        return ApiResponse::success(['sources' => $this->engine->metadata()], 'Report metadata');
    }

    public function index()
    {
        $reports = ReportDefinition::orderBy('created_at', 'desc')->get();
        return ApiResponse::success($reports, 'Reports retrieved');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $report = ReportDefinition::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'source' => $validated['source'],
            'columns' => $validated['columns'] ?? [],
            'filters' => $validated['filters'] ?? [],
            'sort' => $validated['sort'] ?? null,
            'limit' => $validated['limit'] ?? null,
            'is_shared' => (bool) ($validated['is_shared'] ?? false),
            'created_by' => $request->user()?->id,
        ]);

        return ApiResponse::success($report, 'Report saved', 201);
    }

    public function show(ReportDefinition $report)
    {
        return ApiResponse::success($report, 'Report retrieved');
    }

    public function update(Request $request, ReportDefinition $report)
    {
        $validated = $this->validatePayload($request, true);

        $report->update([
            'name' => $validated['name'] ?? $report->name,
            'description' => $validated['description'] ?? $report->description,
            'source' => $validated['source'] ?? $report->source,
            'columns' => $validated['columns'] ?? $report->columns,
            'filters' => $validated['filters'] ?? $report->filters,
            'sort' => $validated['sort'] ?? $report->sort,
            'limit' => $validated['limit'] ?? $report->limit,
            'is_shared' => isset($validated['is_shared']) ? (bool) $validated['is_shared'] : $report->is_shared,
        ]);

        return ApiResponse::success($report, 'Report updated');
    }

    public function destroy(ReportDefinition $report)
    {
        $report->delete();
        return ApiResponse::success(null, 'Report deleted');
    }

    public function run(Request $request)
    {
        $validated = $this->validatePayload($request, true);

        $source = $validated['source'] ?? null;
        if (!$source) {
            return ApiResponse::error('Source is required', 422);
        }

        $result = $this->engine->run($source, $validated);

        return ApiResponse::success($result, 'Report generated');
    }

    public function runSaved(ReportDefinition $report)
    {
        $payload = [
            'source' => $report->source,
            'columns' => $report->columns ?? [],
            'filters' => $report->filters ?? [],
            'sort' => $report->sort ?? null,
            'limit' => $report->limit ?? null,
        ];

        $result = $this->engine->run($report->source, $payload);

        return ApiResponse::success($result, 'Report generated');
    }

    protected function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => $partial ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'source' => $partial ? 'sometimes|string' : 'required|string',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'filters' => 'nullable|array',
            'filters.*.field' => 'required_with:filters|string',
            'filters.*.op' => 'required_with:filters|string',
            'filters.*.value' => 'nullable',
            'sort' => 'nullable|array',
            'sort.field' => 'nullable|string',
            'sort.direction' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:5000',
            'is_shared' => 'nullable|boolean',
        ];

        return $request->validate($rules);
    }

}
