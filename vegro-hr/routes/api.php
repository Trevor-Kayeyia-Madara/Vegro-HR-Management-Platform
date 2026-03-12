<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Root `/` route returns JSON with dynamic API URLs based on APP_URL.
|
*/

Route::get('/', function () {
    $base = rtrim(config('app.url'), '/'); // Get APP_URL from .env

    return response()->json([
        'message' => 'Welcome to Vegro HR API',
        'routes' => [
            'departments' => $base . '/api/departments',
            'employees' => $base . '/api/employees',
            'payrolls' => $base . '/api/payrolls',
            'payslips' => $base . '/api/payslips',
            'attendances' => $base . '/api/attendances',
            'leave-requests' => $base . '/api/leave-requests',
            'auth' => [
                'login' => $base . '/api/auth/login',
                'register' => $base . '/api/auth/register',
                'logout' => $base . '/api/auth/logout',
                'me' => $base . '/api/auth/me',
                'check' => $base . '/api/auth/check',
            ]
        ]
    ]);
});

// Authentication Routes
Route::post('/auth/register', 'App\Http\Controllers\AuthController@store')->middleware('tenant.domain');
Route::post('/auth/login', 'App\Http\Controllers\AuthController@login')->middleware('tenant.domain');
Route::post('/auth/logout', 'App\Http\Controllers\AuthController@logout')->middleware('check.api.token');
Route::get('/auth/me', 'App\Http\Controllers\AuthController@me')->middleware('check.api.token');
Route::get('/auth/check', 'App\Http\Controllers\AuthController@authCheck');

// Super Admin only routes (no tenant scoping)
Route::middleware(['check.api.token', 'superadmin'])->group(function () {
    Route::apiResource('companies', 'App\Http\Controllers\CompanyController')->only(['index', 'store']);
    Route::get('/super/dashboard', 'App\Http\Controllers\SuperAdminController@dashboard');
    Route::post('/companies/{company}/suspend', 'App\Http\Controllers\CompanyController@suspend');
    Route::post('/companies/{company}/resume', 'App\Http\Controllers\CompanyController@resume');
    Route::get('/companies/{company}/domains', 'App\Http\Controllers\CompanyController@listDomains');
    Route::post('/companies/{company}/domains', 'App\Http\Controllers\CompanyController@addDomain');
    Route::delete('/companies/{company}/domains/{domain}', 'App\Http\Controllers\CompanyController@removeDomain');
    Route::post('/companies/{company}/plan', 'App\Http\Controllers\CompanyController@updatePlan');

    Route::get('/plans', 'App\Http\Controllers\PlanController@index');
    Route::post('/plans', 'App\Http\Controllers\PlanController@store');
    Route::put('/plans/{plan}', 'App\Http\Controllers\PlanController@update');

    Route::get('/subscriptions', 'App\Http\Controllers\SubscriptionController@index');
    Route::post('/subscriptions', 'App\Http\Controllers\SubscriptionController@store');
    Route::put('/subscriptions/{subscription}', 'App\Http\Controllers\SubscriptionController@update');

    Route::get('/activity-logs', 'App\Http\Controllers\ActivityLogController@index');
});

// Protected Routes (require authentication)
Route::middleware(['check.api.token', 'tenant.domain', 'tenant', 'tenant.env'])->group(function () {
    Route::apiResource('departments', 'App\Http\Controllers\DepartmentController')->middleware('role:admin,hr');
    Route::get('/employees', 'App\Http\Controllers\EmployeeController@index')->middleware('role:admin,hr,finance,employee');
    Route::post('/employees', 'App\Http\Controllers\EmployeeController@store')->middleware('role:admin,hr');
    Route::get('/employees/{employee}', 'App\Http\Controllers\EmployeeController@show')->middleware('role:admin,hr,finance,employee');
    Route::put('/employees/{employee}', 'App\Http\Controllers\EmployeeController@update')->middleware('role:admin,hr,employee');
    Route::delete('/employees/{employee}', 'App\Http\Controllers\EmployeeController@destroy')->middleware('role:admin,hr');
    Route::get('/employees/email/{email}', 'App\Http\Controllers\EmployeeController@getEmployeeByEmail')->middleware('role:admin,hr,finance,employee');
    Route::get('/employees/department/{departmentId}', 'App\Http\Controllers\EmployeeController@getEmployeesByDepartment')->middleware('role:admin,hr,manager');
    Route::get('/employees/my-department', 'App\Http\Controllers\EmployeeController@getMyDepartmentEmployees')->middleware('role:manager');
    
    Route::apiResource('payrolls', 'App\Http\Controllers\PayrollController')->middleware('role:admin,hr,finance');
    Route::get('/payslips/me', 'App\Http\Controllers\PayslipController@myPayslips')->middleware('role:admin,hr,finance,employee');
    Route::apiResource('payslips', 'App\Http\Controllers\PayslipController')->middleware('role:admin,hr,finance');
    Route::get('/payslips/export/csv', 'App\Http\Controllers\PayslipController@exportToCSV')->middleware('role:admin,hr,finance');
    Route::post('/payslips/{id}/approve', 'App\Http\Controllers\PayslipController@approve')->middleware('role:admin,hr,finance,manager');
    Route::post('/payslips/{id}/issue', 'App\Http\Controllers\PayslipController@issue')->middleware('role:admin,hr,finance');
    
    Route::apiResource('attendances', 'App\Http\Controllers\AttendanceController')->middleware('role:admin,hr,manager');
    
    Route::get('/leave-requests/pending', 'App\Http\Controllers\LeaveRequestController@getPendingLeaves')->middleware('role:admin,hr,manager,director,md');
    Route::get('/leave-requests/approved', 'App\Http\Controllers\LeaveRequestController@getApprovedLeaves')->middleware('role:admin,hr,manager,director,md');
    Route::get('/leave-requests/rejected', 'App\Http\Controllers\LeaveRequestController@getRejectedLeaves')->middleware('role:admin,hr,manager,director,md');
    Route::get('/leave-requests/approvers', 'App\Http\Controllers\LeaveRequestController@getApprovalChain')->middleware('role:admin,hr,manager,employee,director,md');
    Route::get('/leave-requests/employee/{employeeId}', 'App\Http\Controllers\LeaveRequestController@getLeavesByEmployee')->middleware('role:admin,hr,manager,employee');
    Route::post('/leave-requests/{leaveRequest}/approve', 'App\Http\Controllers\LeaveRequestController@approve')->middleware('role:admin,hr,manager,director,md');
    Route::post('/leave-requests/{leaveRequest}/reject', 'App\Http\Controllers\LeaveRequestController@reject')->middleware('role:admin,hr,manager,director,md');
    Route::get('leave-requests/all','App\Http\Controllers\LeaveRequestController@getAllLeaveRequests')->middleware('role:admin,hr,manager,director,md');
    Route::get('/leave-requests/export/csv', 'App\Http\Controllers\LeaveRequestController@exportLeavesToCSV')->middleware('role:admin,hr,manager,director,md');
    Route::apiResource('leave-requests', 'App\Http\Controllers\LeaveRequestController')->middleware('role:admin,hr,manager,employee');
    
    Route::get('/roles/assignable', 'App\Http\Controllers\RoleController@assignable')->middleware('role:admin,hr');
    Route::get('/permissions', 'App\Http\Controllers\RoleController@permissions')->middleware('role:admin');
    Route::get('/roles/permissions/matrix', 'App\Http\Controllers\RoleController@permissionsMatrix')->middleware('role:admin');
    Route::put('/roles/{role}/permissions', 'App\Http\Controllers\RoleController@updatePermissions')->middleware('role:admin');
    Route::apiResource('roles', 'App\Http\Controllers\RoleController')->middleware('role:admin');
    Route::apiResource('users', 'App\Http\Controllers\UserController')->middleware('role:admin');
    Route::apiResource('tax-profiles', 'App\Http\Controllers\TaxProfileController')->middleware('role:admin,finance');
    Route::post('/roles/{role}/users/{user}', 'App\Http\Controllers\RoleController@assignUserByRoute')->middleware('role:admin');

    Route::get('/company/admin/dashboard', 'App\Http\Controllers\CompanyAdminController@dashboard')->middleware('role:admin');
    Route::get('/company/admin/settings', 'App\Http\Controllers\CompanyAdminController@settings')->middleware('role:admin');
    Route::put('/company/admin/settings', 'App\Http\Controllers\CompanyAdminController@updateSettings')->middleware('role:admin');
    Route::get('/company/admin/index-data', 'App\Http\Controllers\CompanyAdminController@indexData')->middleware('role:admin');
    Route::get('/company/admin/subscription', 'App\Http\Controllers\CompanyAdminController@subscription')->middleware('role:admin');
});
