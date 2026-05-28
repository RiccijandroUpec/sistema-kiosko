<?php

namespace App\Http\Controllers;

use App\Models\Kiosko;
use Illuminate\Http\Request;

class KioskPanelAuthController extends Controller
{
    public function showLoginForm()
    {
        // Redirigir al login principal unificado
        return redirect()->route('login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        // Buscar el kiosko directamente por su PIN único
        $kiosk = Kiosko::where('pin', $validated['pin'])->first();

        if (!$kiosk) {
            return back()->withErrors([
                'pin' => 'PIN inválido o no asociado a ningún kiosko.',
            ]);
        }

        $request->session()->put('kiosk_access_id', $kiosk->id);
        $request->session()->regenerate();

        return redirect()->route('kiosk.panel.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('kiosk_access_id');
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión de kiosko cerrada.');
    }
}
