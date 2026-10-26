<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return $this->redirectToDashboard();
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            \App\Models\StaffAccount::where('id', Auth::id())->update(['last_login' => now()]);
            return $this->redirectToDashboard();
        }

        return back()->withErrors(['username' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function redirectToDashboard()
    {
        $role = Auth::user()->role;
        return match ($role) {
            'ADMIN' => redirect('/admin'),
            'FNB_MANAGER' => redirect('/fnb-manager'),
            'FRONT_DESK' => redirect('/frontdesk'),
            'COFFEE_MANAGER' => redirect('/coffee-manager'),
            'WAITER' => redirect('/waiter'),
            'KITCHEN' => redirect('/kitchen'),
            default => redirect('/'),
        };
    }
}