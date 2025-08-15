<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ColoniaController;
use App\Http\Controllers\LocalidadController;
use Illuminate\Support\Facades\Auth; // Asegúrate de importar Auth
use App\Providers\RouteServiceProvider;
use Inertia\Inertia;

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

//Login por default
// Route::get('/', function () {
//     return Inertia::render('Admin/Auth/Login', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//     ]);
// });

Route::get('/', function () {
    // Si el usuario está autenticado, redirigirlo a su página principal (definida en RouteServiceProvider::HOME)
    if (Auth::check()) {
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    // Si el usuario NO está autenticado, mostrar la vista de login de Inertia
    return Inertia::render('Admin/Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//admin routs
Route::group(['prefix' => 'admin', 'middleware' => 'redirectAdmin'], function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Ruta para mostrar el formulario de registro
    Route::get('register', [AdminAuthController::class, 'showRegisterForm'])->name('admin.register');
    // Ruta para procesar el registro
    Route::post('register', [AdminAuthController::class, 'register'])->name('admin.register.post');
});

// Ruta para mostrar el formulario de registro POR FUERA del SISTEMA (Para dar de alta al ADMIN)
Route::get('registrar', [UsuarioController::class, 'showRegisterForm'])->name('registrar');
// Ruta para procesar el registro POR FUERA del SISTEMA (Para dar de alta al ADMIN)
Route::post('registrar', [UsuarioController::class, 'registrar'])->name('registrar.post');

// Route::get('admin/usuarios/search', [UsuarioController::class, 'search'])->name('admin.usuarios.search')->middleware('auth');
// Route::get('admin/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios')->middleware('auth');
// Route::post('admin/usuarios/store', [UsuarioController::class, 'store'])->name('admin.usuarios.store')->middleware('auth');
// Route::post('admin/usuarios/update/{id}', [UsuarioController::class, 'update'])->name('admin.usuarios.update')->middleware('auth');
// Route::get('admin/usuarios/get-puestos/{id_dependencia}', [UsuarioController::class, 'getPuestos'])->name('admin.usuarios.get-puestos')->middleware('auth');
// Ver usuarios
Route::get('admin/usuarios', [UsuarioController::class, 'index'])
    ->name('admin.usuarios')
    ->middleware(['auth', 'can:ver_usuarios']);

Route::get('admin/usuarios/search', [UsuarioController::class, 'search'])
    ->name('admin.usuarios.search')
    ->middleware(['auth', 'can:ver_usuarios']);

Route::get('admin/usuarios/get-puestos/{id_dependencia}', [UsuarioController::class, 'getPuestos'])
    ->name('admin.usuarios.get-puestos')
    ->middleware(['auth', 'can:ver_usuarios']); // este también muestra info, no modifica

// Crear usuarios
Route::post('admin/usuarios/store', [UsuarioController::class, 'store'])
    ->name('admin.usuarios.store')
    ->middleware(['auth', 'can:crear_usuarios']);

// Editar usuarios
Route::post('admin/usuarios/update/{id}', [UsuarioController::class, 'update'])
    ->name('admin.usuarios.update')
    ->middleware(['auth', 'can:editar_usuarios']);

    
Route::match(['get', 'post'],'solicitudes', [SolicitudController::class, 'index'])->name('solicitudes')->middleware('auth');
Route::get('solicitudes/get-solicitud/{id}', [SolicitudController::class, 'getSolicitud'])->name('solicitudes.get-solicitud')->middleware('auth');
Route::get('solicitudes/get-persona/{curp}', [SolicitudController::class, 'getPersona'])->name('solicitudes.get-persona')->middleware('auth');
Route::get('solicitudes/get-propiedad/{claveCatastral}', [SolicitudController::class, 'getPropiedad'])->name('solicitudes.get-propiedad')->middleware('auth');
Route::get('solicitudes/get-propiedad-solicitud/{idSolicitud}', [SolicitudController::class, 'getPropiedadSolicitud'])->name('solicitudes.get-propiedad-solicitud')->middleware('auth');
Route::get('solicitudes/get-colonias', [SolicitudController::class, 'getColonias'])->name('solicitudes.get-colonias')->middleware('auth');
Route::get('solicitudes/get-localidades', [SolicitudController::class, 'getLocalidades'])->name('solicitudes.get-localidades')->middleware('auth');
Route::post('solicitudes/store', [SolicitudController::class, 'store'])->name('solicitudes.store')->middleware('auth');
Route::post('solicitudes/update/{id}', [SolicitudController::class, 'update'])->name('solicitudes.update')->middleware('auth'); 
Route::post('solicitudes/valida', [SolicitudController::class, 'valida'])->name('solicitudes.valida')->middleware('auth'); 
Route::post('solicitudes/upload-croquis/{id}', [SolicitudController::class, 'uploadCroquis'])->name('solicitudes.upload-croquis')->middleware('auth'); 
Route::post('solicitudes/delete-croquis/{id}', [SolicitudController::class, 'deleteCroquis'])->name('solicitudes.delete-croquis')->middleware('auth'); 
Route::get('solicitudes/print-pdf/{id}', [SolicitudController::class, 'printPDF'])->name('solicitudes.print-pdf')->middleware(['auth', 'checkSolicitudStatus']);
Route::get('solicitudes/print-preview-pdf/{id}', [SolicitudController::class, 'printPreviewPDF'])->name('solicitudes.print-preview-pdf')->middleware('auth');
Route::post('solicitudes/print-pdf/{id}', [SolicitudController::class, 'printPDFPrepare'])->name('solicitudes.print-pdf.prepare')->middleware(['auth', 'checkSolicitudStatus']);
Route::post('solicitudes/print-preview-pdf/{id}', [SolicitudController::class, 'printPreviewPDFPrepare'])->name('solicitudes.print-preview-pdf.prepare')->middleware('auth');
Route::get('solicitudes/view/{folio_digital}', [SolicitudController::class, 'view'])->name('solicitudes.view');
Route::match(['get', 'post'],'solicitudes/{solicitud}/enviar-email', [SolicitudController::class, 'enviarSolicitudPorEmail'])->middleware('auth');

Route::match(['get', 'post'],'estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas')->middleware('auth');

Route::post('colonias/store', [ColoniaController::class, 'store'])->name('colonias.store')->middleware('auth');

Route::post('localidades/store', [LocalidadController::class, 'store'])->name('localidades.store')->middleware('auth');



Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
});

require __DIR__.'/auth.php';
