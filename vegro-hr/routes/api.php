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
Route::post('/auth/register', 'App\Http\Controllers\AuthController@store')->middleware(['tenant.domain', 'throttle:10,1']);
Route::post('/auth/login', 'App\Http\Controllers\AuthController@login')->middleware(['tenant.domain', 'throttle:10,1']);
Route::post('/auth/forgot-password', 'App\Http\Controllers\AuthController@forgotPassword')->middleware(['tenant.domain', 'throttle:5,1']);
Route::post('/auth/reset-password', 'App\Http\Controllers\AuthController@resetPassword')->middleware(['tenant.domain', 'throttle:10,1']);
Route::post('/auth/logout', 'App\Http\Controllers\AuthController@logout')->middleware('check.api.token');
Route::get('/auth/me', 'App\Http\Controllers\AuthController@me')->middleware('check.api.token');
Route::patch('/auth/me', 'App\Http\Controllers\AuthController@updateMe')->middleware('check.api.token');
Route::get('/auth/check', 'App\Http\Controllers\AuthController@authCheck');
Route::get('/auth/email/verify/{id}/{hash}', 'App\Http\Controllers\AuthController@verifyEmail')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Public lead capture (email waitlist)
Route::post('/lead-capture', 'App\Http\Controllers\LeadCaptureController@store');
Route::get('/public/plans', 'App\Http\Controllers\PlanController@publicIndex')->middleware('throttle:60,1');

// Shared authenticated routes (available to superadmin and tenant users)
Route::middleware(['check.api.token'])->group(function () {
    Route::post('/auth/email/resend', 'App\Http\Controllers\AuthController@resendVerification')->middleware('throttle:3,1');
    Route::get('/chat/users', 'App\Http\Controllers\ChatController@users');
    Route::get('/chat/conversations', 'App\Http\Controllers\ChatController@conversations');
    Route::post('/chat/conversations', 'App\Http\Controllers\ChatController@createConversation');
    Route::get('/chat/conversations/{conversationId}', 'App\Http\Controllers\ChatController@showConversation');
    Route::get('/chat/conversations/{conversationId}/messages', 'App\Http\Controllers\ChatController@messages');
    Route::post('/chat/conversations/{conversationId}/messages', 'App\Http\Controllers\ChatController@sendMessage');
});

// Super Admin only routes (no tenant scoping)
Route::middleware(['check.api.token', 'superadmin'])->group(function () {
    Route::apiResource('companies', 'App\Http\Controllers\CompanyController')->only(['index', 'store', 'update']);
    Route::get('/super/dashboard', 'App\Http\Controllers\SuperAdminController@dashboard');
    Route::post('/companies/{company}/suspend', 'App\Http\Controllers\CompanyController@suspend');
    Route::post('/companies/{company}/resume', 'App\Http\Controllers\CompanyController@resume');
    Route::get('/companies/{company}/domains', 'App\Http\Controllers\CompanyController@listDomains');
    Route::post('/companies/{company}/domains', 'App\Http\Controllers\CompanyController@addDomain');
    Route::delete('/companies/{company}/domains/{domain}', 'App\Http\Controllers\CompanyController@removeDomain');
    Route::post('/companies/{company}/plan', 'App\Http\Controllers\CompanyController@updatePlan');
    Route::post('/admin/companies/{company}/assign-plan', 'App\Http\Controllers\CompanyController@assignPlan');

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
    Route::get('/departments', 'App\Http\Controllers\DepartmentController@index')->middleware('role:admin,hr,director,md');
    Route::get('/departments/{department}', 'App\Http\Controllers\DepartmentController@show')->middleware('role:admin,hr,director,md');
    Route::post('/departments', 'App\Http\Controllers\DepartmentController@store')->middleware('role:admin,hr');
    Route::put('/departments/{department}', 'App\Http\Controllers\DepartmentController@update')->middleware('role:admin,hr');
    Route::delete('/departments/{department}', 'App\Http\Controllers\DepartmentController@destroy')->middleware('role:admin,hr');
    Route::get('/org-chart', 'App\Http\Controllers\DepartmentController@orgChart')->middleware('role:hr');
    Route::get('/org-chart/matrix', 'App\Http\Controllers\DepartmentController@matrixOrgChart')->middleware('role:hr');
    Route::get('/org-chart/layout', 'App\Http\Controllers\DepartmentController@getOrgChartLayout')->middleware('role:hr');
    Route::put('/org-chart/layout', 'App\Http\Controllers\DepartmentController@saveOrgChartLayout')->middleware('role:hr');

    Route::get('/employees/{employee}/managers', 'App\Http\Controllers\EmployeeMatrixController@index')->whereNumber('employee')->middleware('role:admin,hr,manager,employee,director,md');
    Route::put('/employees/{employee}/managers', 'App\Http\Controllers\EmployeeMatrixController@sync')->whereNumber('employee')->middleware('role:admin,hr');

    Route::get('/projects', 'App\Http\Controllers\ProjectController@index')->middleware('role:admin,hr,manager,employee,director,md');
    Route::get('/projects/{project}', 'App\Http\Controllers\ProjectController@show')->middleware('role:admin,hr,manager,employee,director,md');
    Route::post('/projects', 'App\Http\Controllers\ProjectController@store')->middleware('role:admin,hr');
    Route::put('/projects/{project}', 'App\Http\Controllers\ProjectController@update')->middleware('role:admin,hr');
    Route::delete('/projects/{project}', 'App\Http\Controllers\ProjectController@destroy')->middleware('role:admin,hr');

    Route::post('/projects/{project}/members', 'App\Http\Controllers\ProjectController@addMember')->middleware('role:admin,hr');
    Route::put('/projects/{project}/members/{membership}', 'App\Http\Controllers\ProjectController@updateMember')->middleware('role:admin,hr');
    Route::delete('/projects/{project}/members/{membership}', 'App\Http\Controllers\ProjectController@removeMember')->middleware('role:admin,hr');

    // ATS Recruitment (Phase 1)
    Route::get('/ats/jobs', 'App\Http\Controllers\AtsJobPostingController@index')->middleware(['role:admin,hr,manager,director,md', 'permission:recruitment.view']);
    Route::get('/ats/jobs/{jobPosting}', 'App\Http\Controllers\AtsJobPostingController@show')->middleware(['role:admin,hr,manager,director,md', 'permission:recruitment.view']);
    Route::post('/ats/jobs', 'App\Http\Controllers\AtsJobPostingController@store')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::put('/ats/jobs/{jobPosting}', 'App\Http\Controllers\AtsJobPostingController@update')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::delete('/ats/jobs/{jobPosting}', 'App\Http\Controllers\AtsJobPostingController@destroy')->middleware(['role:admin,hr', 'permission:recruitment.manage']);

    Route::get('/ats/candidates', 'App\Http\Controllers\AtsCandidateController@index')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::get('/ats/candidates/{candidate}', 'App\Http\Controllers\AtsCandidateController@show')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::post('/ats/candidates', 'App\Http\Controllers\AtsCandidateController@store')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::put('/ats/candidates/{candidate}', 'App\Http\Controllers\AtsCandidateController@update')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::delete('/ats/candidates/{candidate}', 'App\Http\Controllers\AtsCandidateController@destroy')->middleware(['role:admin,hr', 'permission:recruitment.manage']);

    Route::get('/ats/applications', 'App\Http\Controllers\AtsApplicationController@index')->middleware(['role:admin,hr,manager,director,md', 'permission:recruitment.view']);
    Route::get('/ats/applications/{application}', 'App\Http\Controllers\AtsApplicationController@show')->middleware(['role:admin,hr,manager,director,md', 'permission:recruitment.view']);
    Route::post('/ats/applications', 'App\Http\Controllers\AtsApplicationController@store')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::put('/ats/applications/{application}', 'App\Http\Controllers\AtsApplicationController@update')->middleware(['role:admin,hr,manager', 'permission:recruitment.view']);
    Route::delete('/ats/applications/{application}', 'App\Http\Controllers\AtsApplicationController@destroy')->middleware(['role:admin,hr', 'permission:recruitment.manage']);
    Route::post('/ats/applications/{application}/notes', 'App\Http\Controllers\AtsApplicationController@addNote')->middleware(['role:admin,hr,manager', 'permission:recruitment.view']);
    Route::get('/employees', 'App\Http\Controllers\EmployeeController@index')->middleware('role:admin,hr,finance,employee,director,md');
    Route::post('/employees', 'App\Http\Controllers\EmployeeController@store')->middleware('role:admin,hr');
    Route::get('/employees/export/csv', 'App\Http\Controllers\EmployeeController@exportToCSV')->middleware('role:admin,hr');
    Route::post('/employees/import/csv', 'App\Http\Controllers\EmployeeController@importFromCSV')->middleware('role:admin,hr');
    Route::get('/employees/my-department', 'App\Http\Controllers\EmployeeController@getMyDepartmentEmployees')->middleware('permission:employees.view');
    Route::post('/employees/leave-balances/sync-defaults', 'App\Http\Controllers\EmployeeController@syncLeaveBalancesForCompany')->middleware('role:admin,hr');
    Route::get('/employees/email/{email}', 'App\Http\Controllers\EmployeeController@getEmployeeByEmail')->middleware('role:admin,HR,finance,employee');
    Route::get('/employees/department/{departmentId}', 'App\Http\Controllers\EmployeeController@getEmployeesByDepartment')->middleware('role:admin,hr,manager');
    Route::get('/employees/{employee}/leave-balances', 'App\Http\Controllers\EmployeeController@getLeaveBalances')->whereNumber('employee')->middleware('role:admin,hr,manager,employee,director,md');
    Route::get('/employees/{employee}/leave-summary', 'App\Http\Controllers\EmployeeController@getLeaveSummary')->whereNumber('employee')->middleware('role:admin,hr,manager,employee,director,md');
    Route::get('/employees/{employee}', 'App\Http\Controllers\EmployeeController@show')->whereNumber('employee')->middleware('role:admin,hr,finance,employee,director,md');
    Route::put('/employees/{employee}', 'App\Http\Controllers\EmployeeController@update')->whereNumber('employee')->middleware('role:admin,hr,employee');
    Route::delete('/employees/{employee}', 'App\Http\Controllers\EmployeeController@destroy')->whereNumber('employee')->middleware('role:admin,hr');
    
    Route::get('/payrolls', 'App\Http\Controllers\PayrollController@index')->middleware('role:admin,hr,finance,director,md');
    Route::get('/payrolls/{payroll}', 'App\Http\Controllers\PayrollController@show')->middleware('role:admin,hr,finance,director,md');
    Route::post('/payrolls', 'App\Http\Controllers\PayrollController@store')->middleware('role:admin,hr,finance');
    Route::put('/payrolls/{payroll}', 'App\Http\Controllers\PayrollController@update')->middleware('role:admin,hr,finance');
    Route::delete('/payrolls/{payroll}', 'App\Http\Controllers\PayrollController@destroy')->middleware('role:admin,hr,finance');
    Route::post('/payrolls/{payroll}/approve', 'App\Http\Controllers\PayrollController@approve')->middleware(['role:admin,finance,manager', 'permission:payroll.approve']);
    Route::get('/payrolls/export/csv', 'App\Http\Controllers\PayrollController@exportToCSV')->middleware('role:admin,hr,finance');
    Route::post('/payrolls/import/csv', 'App\Http\Controllers\PayrollController@importFromCSV')->middleware('role:admin,hr,finance');
    Route::get('/payslips/me', 'App\Http\Controllers\PayslipController@myPayslips')->middleware('role:admin,hr,finance,employee');
    Route::get('/payslips', 'App\Http\Controllers\PayslipController@index')->middleware('role:admin,hr,finance,director,md');
    Route::get('/payslips/{payslip}', 'App\Http\Controllers\PayslipController@show')->middleware('role:admin,hr,finance,director,md');
    Route::post('/payslips', 'App\Http\Controllers\PayslipController@store')->middleware('role:admin,hr,finance');
    Route::put('/payslips/{payslip}', 'App\Http\Controllers\PayslipController@update')->middleware('role:admin,hr,finance');
    Route::delete('/payslips/{payslip}', 'App\Http\Controllers\PayslipController@destroy')->middleware('role:admin,hr,finance');
    Route::get('/payslips/export/csv', 'App\Http\Controllers\PayslipController@exportToCSV')->middleware('role:admin,hr,finance');
    Route::post('/payslips/{id}/approve', 'App\Http\Controllers\PayslipController@approve')->middleware('role:admin,hr,finance,manager');
    Route::post('/payslips/{id}/issue', 'App\Http\Controllers\PayslipController@issue')->middleware('role:admin,hr,finance');
    
    Route::apiResource('attendances', 'App\Http\Controllers\AttendanceController')->middleware('role:admin,hr,manager');
    Route::get('/attendances/export/csv', 'App\Http\Controllers\AttendanceController@exportToCSV')->middleware('role:admin,HR,manager');
    Route::post('/attendances/import/csv', 'App\Http\Controllers\AttendanceController@importFromCSV')->middleware('role:admin,HR,manager');
    
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

    Route::get('/leave-types', 'App\Http\Controllers\LeaveTypeSettingController@index')->middleware('role:admin,hr');
    Route::put('/leave-types/{type}', 'App\Http\Controllers\LeaveTypeSettingController@update')->middleware('role:admin,hr');
    Route::post('/leave-types/reset-defaults', 'App\Http\Controllers\LeaveTypeSettingController@resetDefaults')->middleware('role:admin,hr');

    Route::get('/notifications', 'App\Http\Controllers\InAppNotificationController@index')->middleware('role:admin,hr,manager,employee,director,md');
    Route::put('/notifications/{id}/read', 'App\Http\Controllers\InAppNotificationController@markRead')->middleware('role:admin,hr,manager,employee,director,md');
    Route::put('/notifications/read-all', 'App\Http\Controllers\InAppNotificationController@markAllRead')->middleware('role:admin,hr,manager,employee,director,md');

    Route::get('/feedback', 'App\Http\Controllers\EmployeeFeedbackController@index')->middleware(['role:admin,hr,director,md', 'permission:feedback.view']);
    Route::get('/feedback/mine', 'App\Http\Controllers\EmployeeFeedbackController@mine')->middleware(['role:admin,hr,finance,manager,employee,director,md', 'permission:feedback.submit,feedback.view']);
    Route::post('/feedback', 'App\Http\Controllers\EmployeeFeedbackController@store')->middleware(['role:admin,hr,finance,manager,employee,director,md', 'permission:feedback.submit']);
    Route::put('/feedback/{feedback}', 'App\Http\Controllers\EmployeeFeedbackController@update')->middleware(['role:admin,hr', 'permission:feedback.manage']);

    Route::get('/onboarding/templates', 'App\Http\Controllers\OnboardingDocumentController@templates')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::get('/onboarding/templates/{template}/download', 'App\Http\Controllers\OnboardingDocumentController@downloadTemplate')->middleware(['role:admin,hr,finance,manager,employee,director,md', 'permission:onboarding.view,onboarding.sign']);
    Route::post('/onboarding/templates', 'App\Http\Controllers\OnboardingDocumentController@createTemplate')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::put('/onboarding/templates/{template}', 'App\Http\Controllers\OnboardingDocumentController@updateTemplate')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::delete('/onboarding/templates/{template}', 'App\Http\Controllers\OnboardingDocumentController@deleteTemplate')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::get('/onboarding/assignments', 'App\Http\Controllers\OnboardingDocumentController@assignments')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::post('/onboarding/assignments', 'App\Http\Controllers\OnboardingDocumentController@assign')->middleware(['role:admin,hr', 'permission:onboarding.manage']);
    Route::get('/onboarding/assignments/mine', 'App\Http\Controllers\OnboardingDocumentController@myAssignments')->middleware(['role:admin,hr,finance,manager,employee,director,md', 'permission:onboarding.view,onboarding.sign']);
    Route::post('/onboarding/assignments/{assignment}/sign', 'App\Http\Controllers\OnboardingDocumentController@sign')->middleware(['role:employee', 'permission:onboarding.sign']);
    
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

    Route::get('/reports/metadata', 'App\Http\Controllers\ReportController@metadata')->middleware('role:admin,hr,finance,director,md');
    Route::post('/reports/run', 'App\Http\Controllers\ReportController@run')->middleware('role:admin,hr,finance,director,md');
    Route::post('/reports/{report}/run', 'App\Http\Controllers\ReportController@runSaved')->middleware('role:admin,hr,finance,director,md');
    Route::get('/reports', 'App\Http\Controllers\ReportController@index')->middleware('role:admin,hr,finance,director,md');
    Route::get('/reports/{report}', 'App\Http\Controllers\ReportController@show')->middleware('role:admin,hr,finance,director,md');
    Route::post('/reports', 'App\Http\Controllers\ReportController@store')->middleware('role:admin,hr,finance');
    Route::put('/reports/{report}', 'App\Http\Controllers\ReportController@update')->middleware('role:admin,hr,finance');
    Route::delete('/reports/{report}', 'App\Http\Controllers\ReportController@destroy')->middleware('role:admin,hr,finance');

    Route::get('/compliance/alerts', 'App\Http\Controllers\ComplianceAlertController@index')->middleware(['role:admin,hr,finance,manager,director,md', 'permission:compliance.view']);
    Route::post('/compliance/scan', 'App\Http\Controllers\ComplianceAlertController@runScan')->middleware(['role:admin,hr,finance,director,md', 'permission:compliance.manage']);
    Route::put('/compliance/alerts/{alert}/acknowledge', 'App\Http\Controllers\ComplianceAlertController@acknowledge')->middleware(['role:admin,hr,finance,director,md', 'permission:compliance.manage']);

    Route::get('/audits/model-changes', 'App\Http\Controllers\ModelChangeAuditController@index')->middleware(['role:admin,hr,finance,director,md', 'permission:audits.view']);

    Route::get('/dashboards', 'App\Http\Controllers\DashboardBuilderController@index')->middleware('role:admin,hr,finance,director,md');
    Route::get('/dashboards/{dashboard}', 'App\Http\Controllers\DashboardBuilderController@show')->middleware('role:admin,hr,finance,director,md');
    Route::post('/dashboards/{dashboard}/run', 'App\Http\Controllers\DashboardBuilderController@run')->middleware('role:admin,hr,finance,director,md');
    Route::post('/dashboards', 'App\Http\Controllers\DashboardBuilderController@store')->middleware('role:admin,hr,finance,director,md');
    Route::put('/dashboards/{dashboard}', 'App\Http\Controllers\DashboardBuilderController@update')->middleware('role:admin,hr,finance,director,md');
    Route::delete('/dashboards/{dashboard}', 'App\Http\Controllers\DashboardBuilderController@destroy')->middleware('role:admin,hr,finance,director,md');
    Route::post('/dashboards/{dashboard}/widgets', 'App\Http\Controllers\DashboardBuilderController@addWidget')->middleware('role:admin,hr,finance,director,md');
    Route::put('/dashboards/{dashboard}/widgets/{widget}', 'App\Http\Controllers\DashboardBuilderController@updateWidget')->middleware('role:admin,hr,finance,director,md');
    Route::delete('/dashboards/{dashboard}/widgets/{widget}', 'App\Http\Controllers\DashboardBuilderController@deleteWidget')->middleware('role:admin,hr,finance,director,md');
});
