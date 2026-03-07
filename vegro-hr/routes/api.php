use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveRequestController;

Route::apiResource('departments', DepartmentController::class);
Route::apiResource('employees', EmployeeController::class);
Route::apiResource('payrolls', PayrollController::class);
Route::apiResource('payslips', PayslipController::class);
Route::apiResource('attendances', AttendanceController::class);
Route::apiResource('leave-requests', LeaveRequestController::class);