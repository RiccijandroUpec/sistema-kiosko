<?php

namespace App\Http\Middleware;

use App\Models\Kiosk;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskPinAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $kioskId = $request->session()->get('kiosk_access_id');

        if (!$kioskId) {
            return redirect()->route('kiosk.panel.login.form');
        }

        $kiosk = Kiosk::find($kioskId);

        if (!$kiosk) {
            $request->session()->forget('kiosk_access_id');
            return redirect()->route('kiosk.panel.login.form');
        }

        $request->attributes->set('kiosk_session', $kiosk);

        return $next($request);
    }
}
