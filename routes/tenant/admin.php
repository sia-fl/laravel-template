<?php

use App\Cache\PermissionCache;
use App\Http\Controllers\AdminControllers\AdminOrganizationController;
use App\Http\Controllers\CommonControllers\LogController;
use App\Http\Controllers\RegionController;
use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\AuthController;

// Admin
use App\Http\Controllers\AdminControllers\AdminUserController;
use App\Http\Controllers\AdminControllers\AdminDepartmentController;
use App\Http\Controllers\AdminControllers\AdminPositionController;
use App\Http\Controllers\AdminControllers\AdminRoleController;

// Enterprise
use App\Http\Controllers\EnterpriseControllers\AuthController as EnterpriseAuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/region', [RegionController::class, 'region']);
Route::post('/child_regions', [RegionController::class, 'childRegions']);
Route::post('/stop_log/{id}', [LogController::class, 'stopLog']);

Route::middleware(debugMiddleware())->group(function () {
    routePack(
        null,
        AuthController::class,
        ['logout', 'userinfo']
    );
    routePack(
        PermissionCache::PAdminOrganization,
        AdminOrganizationController::class,
        [
            'lock' => '$/{id}'
        ]
    );
    Route::prefix('admin')->group(function () {
        routePack(
            PermissionCache::PEnterprise,
            EnterpriseAuthController::class,
            ['uploadZj', 'uploadId', 'uploadMm', 'uploadHj']
        );
        routePack(
            PermissionCache::PAdminUser,
            AdminUserController::class,
            [
                'departmentOptions',
                'positionOptions',
                'roleOptions',
                'forgotPassword'    => '$/{id}',
                'syncOrganizations' => '$/{id}',
                'findOrganizations' => '$/{id}'
            ]
        );
        routePack(
            PermissionCache::PAdminDepartment,
            AdminDepartmentController::class
        );
        routePack(
            PermissionCache::PAdminPosition,
            AdminPositionController::class,
            [
                'departmentOptions',
            ]
        );
        routePack(
            PermissionCache::PAdminRole,
            AdminRoleController::class,
            [
                'syncPermissionNames' => '$/{id}',
                'findPermissionNames' => '$/{id}'
            ]
        );
    });
});

// ???????????????, ???????????? laravel ?????????????????????, ??????????????? spa,
// ?????? 300+ ???????????????????????????????????????, ???????????? json ??????????????? 403 ??????
Route::get('/login', function () {
    return se(['status' => 403, 'message' => '???????????????']);
})->name('login');

Route::middleware('auth:sanctum')->get('/hi', function () {
    // ??? debug ???????????????????????????
    return 'hello word';
});
