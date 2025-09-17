<?php

use App\Http\Controllers\Auth\AuthController;

use App\Http\Controllers\Auth\ResetPassword\PasswordResetController;


use Illuminate\Support\Facades\Route;

Route::post('/google-login', [AuthController::class, 'googleLogin']);

Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/validate-refresh-token', [AuthController::class, 'validateRefreshToken']);

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:admin'])->group(function () { 


});

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () { 

 

});


// RUTAS PARA ROL ADMIN Y ASESOR
Route::middleware(['auth.jwt', 'CheckRolesMW_ADMIN_ASESOR'])->group(function () { 


});

// RUTAS PARA ROL ADMIN Y AUDITOR
Route::middleware(['auth.jwt', 'CheckRolesMW_ADMIN_AUDITOR'])->group(function () { 
    
   
});

// RUTAS PARA ROL ADMIN Y CLIENTE  Y AUDITOR
Route::middleware(['auth.jwt', 'CheckRolesMW_ADMIN_CLIENTE'])->group(function () { 
    

});

// RUTAS PARA VARIOS ROLES
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

    Route::post('/logout', [AuthController::class, 'logout']);

});
