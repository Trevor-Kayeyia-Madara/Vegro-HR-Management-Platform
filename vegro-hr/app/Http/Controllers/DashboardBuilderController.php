<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DashboardDefinition;
use App\Models\DashboardWidget;
use App\Services\ReportEngine;
use Illuminate\Http\Request;

class DashboardBuilderController extends Controller
{
    protected ReportEngine $engine;

    public function __construct(ReportEngine $engine)
    {
        $this->engine = $engine;
    }

    public function index(Request $request)
    {
        $userId = $request->user()?->id;
        $dashboards = DashboardDefinition::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($dashboards, 'Dashboards retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $dashboard = DashboardDefinition::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id' => $request->user()?->id,
        ]);

        return ApiResponse::success($dashboard, 'Dashboard created', 201);
    }

    public function show(DashboardDefinition $dashboard)
    {
        $dashboard->load('widgets');
        return ApiResponse::success($dashboard, 'Dashboard retrieved');
    }

    public function update(Request $request, DashboardDefinition $dashboard)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $dashboard->update([
            'name' => $validated['name'] ?? $dashboard->name,
            'description' => $validated['description'] ?? $dashboard->description,
        ]);

        return ApiResponse::success($dashboard, 'Dashboard updated');
    }

    public function destroy(DashboardDefinition $dashboard)
    {
        $dashboard->delete();
        return ApiResponse::success(null, 'Dashboard deleted');
    }

    public function addWidget(Request $request, DashboardDefinition $dashboard)
    {
        $validated = $this->validateWidget($request);
        $position = (int) ($validated['position'] ?? ($dashboard->widgets()->max('position') ?? 0) + 1);

        $widget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'title' => $validated['title'],
            'source' => $validated['source'],
            'chart_type' => $validated['chart_type'] ?? 'table',
            'columns' => $validated['columns'] ?? [],
            'filters' => $validated['filters'] ?? [],
            'sort' => $validated['sort'] ?? null,
            'limit' => $validated['limit'] ?? null,
            'x_field' => $validated['x_field'] ?? null,
            'y_field' => $validated['y_field'] ?? null,
            'aggregate' => $validated['aggregate'] ?? null,
            'width' => $validated['width'] ?? 6,
            'height' => $validated['height'] ?? 4,
            'position' => $position,
        ]);

        return ApiResponse::success($widget, 'Widget added', 201);
    }

    public function updateWidget(Request $request, DashboardDefinition $dashboard, DashboardWidget $widget)
    {
        if ($widget->dashboard_id !== $dashboard->id) {
            return ApiResponse::notFound('Widget not found');
        }

        $validated = $this->validateWidget($request, true);

        $widget->update([
            'title' => $validated['title'] ?? $widget->title,
            'source' => $validated['source'] ?? $widget->source,
            'chart_type' => $validated['chart_type'] ?? $widget->chart_type,
            'columns' => $validated['columns'] ?? $widget->columns,
            'filters' => $validated['filters'] ?? $widget->filters,
            'sort' => $validated['sort'] ?? $widget->sort,
            'limit' => $validated['limit'] ?? $widget->limit,
            'x_field' => $validated['x_field'] ?? $widget->x_field,
            'y_field' => $validated['y_field'] ?? $widget->y_field,
            'aggregate' => $validated['aggregate'] ?? $widget->aggregate,
            'width' => $validated['width'] ?? $widget->width,
            'height' => $validated['height'] ?? $widget->height,
            'position' => $validated['position'] ?? $widget->position,
        ]);

        return ApiResponse::success($widget, 'Widget updated');
    }

    public function deleteWidget(DashboardDefinition $dashboard, DashboardWidget $widget)
    {
        if ($widget->dashboard_id !== $dashboard->id) {
            return ApiResponse::notFound('Widget not found');
        }

        $widget->delete();
        return ApiResponse::success(null, 'Widget deleted');
    }

    public function run(DashboardDefinition $dashboard)
    {
        $dashboard->load('widgets');

        $widgets = $dashboard->widgets->map(function ($widget) {
            $payload = [
                'source' => $widget->source,
                'columns' => $widget->columns ?? [],
                'filters' => $widget->filters ?? [],
                'sort' => $widget->sort ?? null,
                'limit' => $widget->limit ?? null,
            ];

            $data = $widget->chart_type === 'table'
                ? $this->engine->run($widget->source, $payload)
                : $this->engine->aggregate($widget->source, $payload, [
                    'x_field' => $widget->x_field,
                    'y_field' => $widget->y_field,
                    'aggregate' => $widget->aggregate,
                ]);

            return [
                'id' => $widget->id,
                'title' => $widget->title,
                'chart_type' => $widget->chart_type,
                'source' => $widget->source,
                'columns' => $payload['columns'],
                'data' => $data,
                'width' => $widget->width,
                'height' => $widget->height,
                'position' => $widget->position,
                'x_field' => $widget->x_field,
                'y_field' => $widget->y_field,
                'aggregate' => $widget->aggregate,
            ];
        });

        return ApiResponse::success(['dashboard' => $dashboard, 'widgets' => $widgets], 'Dashboard data');
    }

    protected function validateWidget(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => $partial ? 'sometimes|string|max:255' : 'required|string|max:255',
            'source' => $partial ? 'sometimes|string' : 'required|string',
            'chart_type' => 'nullable|in:table,bar,line,donut,area',
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
            'x_field' => 'nullable|string',
            'y_field' => 'nullable|string',
            'aggregate' => 'nullable|in:count,sum,avg,min,max',
            'width' => 'nullable|integer|min:3|max:12',
            'height' => 'nullable|integer|min:2|max:12',
            'position' => 'nullable|integer|min:0',
        ];

        return $request->validate($rules);
    }
}
