<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
           return Inertia::render('Admin/Auth/Login', [
            'currentYear' => intval(date('Y')),
        ]);
    }

    public function login(Request $request)
    {
        // Add your login logic here
        // Check if the user is an admin and redirect accordingly
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route('admin.dashboard'); // Redirect to the admin dashboard
        }

        return redirect()->route('admin.login')->with('error', 'Los datos proporcionados no corresponden a los registrados en el sistema. Inténtelo nuevamente.');
    }

    public function logout(Request $request)
    {        
        Auth::guard('web')->logout();
       
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // return redirect('/');
        return redirect()->route('admin.login');
    }

    public function showRegisterForm()
    {
        return Inertia::render('Admin/Register');
    }  

    public function register(Request $request)
    {
        // Validación de los datos del formulario
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:3|confirmed',
        ]);

        // Crear el usuario
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        // Autenticar al usuario después del registro
        auth()->login($user);

        // Redirigir a una página específica
        return redirect()->route('admin.dashboard')->with('success', 'Usuario registrado correctamente');
    }


}
