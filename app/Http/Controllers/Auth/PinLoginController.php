<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PinLoginController extends Controller
{
    /**
     * Show the PIN login form.
     */
    public function showForm()
    {
        return view('auth.pin-login');
    }

    /**
     * Handle the PIN login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $user = User::where('pin', $request->pin)->where('role', 'admin')->first();

        if ($user) {
            Auth::login($user, true);
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'pin' => 'El PIN ingresado es incorrecto.',
        ]);
    }
}
