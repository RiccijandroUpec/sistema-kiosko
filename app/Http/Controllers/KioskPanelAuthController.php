<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use Illuminate\Http\Request;

class KioskPanelAuthController extends Controller
{
    public function showLoginForm()
    {
        $kiosks = Kiosk::orderBy('nombre')->get(['id', 'nombre', 'ubicacion']);

        return view('kiosko.panel-login', compact('kiosks'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'kiosk_id' => 'required|exists:kiosks,id',
            'pin' => 'required|digits:4',
        ]);

        $kiosk = Kiosk::find($validated['kiosk_id']);

        if (!$kiosk || empty($kiosk->access_pin) || $kiosk->access_pin !== $validated['pin']) {
            return back()->withErrors([
                'pin' => 'PIN inválido para el kiosko seleccionado.',
            ])->onlyInput('kiosk_id');
        }

        $request->session()->put('kiosk_access_id', $kiosk->id);
        $request->session()->regenerate();

        return redirect()->route('kiosk.panel.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('kiosk_access_id');
        $request->session()->regenerateToken();

        return redirect()->route('kiosk.panel.login.form')->with('success', 'Sesión de kiosko cerrada.');
    }
}
