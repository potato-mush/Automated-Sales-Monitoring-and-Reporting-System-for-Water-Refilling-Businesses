<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Show the admin login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user is admin
            if ($user->role !== 'admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Only admin users can access this portal.',
                ]);
            }

            // Check if account is active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Show transactions page
     */
    public function transactions()
    {
        return view('admin.transactions');
    }

    /**
     * Show gallons page
     */
    public function gallons()
    {
        return view('admin.gallons');
    }

    /**
     * Show reports page
     */
    public function reports()
    {
        return view('admin.reports');
    }

    /**
     * Show inventory page
     */
    public function inventory()
    {
        return view('admin.inventory');
    }

    /**
     * Show employees page
     */
    public function employees()
    {
        return view('admin.employees');
    }

    /**
     * Show QR print page
     */
    public function qrPrint()
    {
        return view('admin.qr-print');
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        return view('admin.settings');
    }
}
