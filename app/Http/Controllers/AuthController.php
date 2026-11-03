<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoUsers = [
            'admin' => User::where('role', 'admin')->first(),
            'supervisor' => User::where('role', 'supervisor')->first(),
            'field_officer' => User::where('role', 'field_officer')->first(),
        ];

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember', false);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            ActivityLog::log(
                'user_login',
                "User logged in: " . Auth::user()->name . " (" . strtoupper(Auth::user()->role) . ")",
                "IP Address: " . $request->ip(),
                Auth::user(),
                Auth::user()
            );

            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // One-click quick demo login for easy testing across roles
    public function quickLogin(Request $request, $role)
    {
        $user = User::where('role', $role)->first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            ActivityLog::log(
                'demo_switch',
                "Switched active session to: {$user->name} (" . strtoupper($user->role) . ")",
                "One-click RBAC role switcher.",
                $user,
                $user
            );

            return redirect()->route('dashboard')->with('success', "Logged in as {$user->name} ({$user->role})");
        }

        return redirect()->route('login')->withErrors(['email' => 'Demo role not found.']);
    }

    public function logout(Request $request)
    {
        $userName = Auth::user()?->name ?? 'User';
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', "Logged out successfully. Have a nice day, {$userName}!");
    }
}
