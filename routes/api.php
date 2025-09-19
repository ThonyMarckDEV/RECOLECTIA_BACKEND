<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Locations\LocationsController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/google-login', [AuthController::class, 'googleLogin']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/validate-refresh-token', [AuthController::class, 'validateRefreshToken']);

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:admin'])->group(function () { 


});

// RUTAS PARA usuario VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:usuario'])->group(function () { 
 
 Route::get('/locations/getCollector', [LocationsController::class, 'getCollectorLocation']);

 Route::post('/reports/create', [ReportController::class, 'store']);

});

// RUTAS PARA recolector VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:recolector'])->group(function () { 

 Route::post('/locations/update', [LocationsController::class, 'updateLocation']);

});


// RUTAS PARA ROL ADMIN Y CLIENTE  Y AUDITOR
Route::middleware(['auth.jwt', 'CheckRolesMW_ADMIN_CLIENTE'])->group(function () { 
    

});

// RUTAS PARA VARIOS ROLES
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

    Route::post('/logout', [AuthController::class, 'logout']);

});
