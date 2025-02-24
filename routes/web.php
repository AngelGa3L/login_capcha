<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Rutas para el registro de usuarios.
 */
Route::get('/', [UsuarioController::class, 'showSinginPage'])->name('register.form');
Route::post('/register', [UsuarioController::class, 'registerNewUser'])->name('register');

/**
 * Rutas para el inicio de sesion.
 */
Route::post('/login',[UsuarioController::class,'authenticateUser'])->name('login');
Route::get('/login', [UsuarioController::class, 'showLoginPage'])->name('login.form');

/**
 * Rutas para el codigo de verificacion.
 */
Route::get('/verify', [UsuarioController::class, 'showCodePage'])->name('verify.form');
Route::post('/verify', [UsuarioController::class, 'verifyCode'])->name('verify');

/**
 * Ruta protegida por autenticacion JWT.
 * Solo los usuarios autenticados mediante JWT podrán acceder a esta pagina.
 */
Route::middleware('auth')->group(function () {
    Route::get('/home', [UsuarioController::class, 'showHomePage'])->name('home');
});