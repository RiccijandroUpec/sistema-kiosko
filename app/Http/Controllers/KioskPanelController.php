<?php

namespace App\Http\Controllers;

use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskPanelController extends Controller
{
    public function dashboard(Request $request)
    {
        $kiosk = $this->getSessionKiosk($request);

        $query = OrdenImpresion::with(['cliente'])
            ->where('kiosko_id', $kiosk->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('estado', $request->status);
        }

        $printJobs = $query->paginate(15);

        $stats = [
            'total' => OrdenImpresion::where('kiosko_id', $kiosk->id)->count(),
            'pending' => OrdenImpresion::where('kiosko_id', $kiosk->id)->where('estado', 'pendiente')->count(),
            'printing' => OrdenImpresion::where('kiosko_id', $kiosk->id)->where('estado', 'imprimiendo')->count(),
            'completed' => OrdenImpresion::where('kiosko_id', $kiosk->id)->where('estado', 'completado')->count(),
        ];

        return view('kiosko.panel', compact('kiosk', 'printJobs', 'stats'));
    }

    public function markAsPrinted(Request $request, OrdenImpresion $printJob)
    {
        $kiosk = $this->getSessionKiosk($request);
        $this->ensureOwnedByKiosk($printJob, $kiosk);

        if ($printJob->estado === 'pendiente') {
            // Si está pendiente, al dar "Completar/Cobrar" en el panel cobramos el efectivo
            // y liberamos el trabajo marcándolo como 'pagado' para que el agente lo imprima físicamente.
            $printJob->update([
                'estado' => 'pagado',
            ]);

            $payment = $printJob->transacciones()->first();
            if ($payment) {
                $payment->update(['estado' => 'completado']);
            }

            return back()->with('success', 'Pago registrado. Trabajo enviado a la impresora.');
        }

        // Si ya estaba pagado o imprimiendo, lo marcamos directamente como completado
        $printJob->update([
            'estado' => 'completado',
        ]);

        $payment = $printJob->transacciones()->first();
        if ($payment) {
            $payment->update(['estado' => 'completado']);
        }

        return back()->with('success', 'Trabajo marcado como completado.');
    }

    public function cancelJob(Request $request, OrdenImpresion $printJob)
    {
        $kiosk = $this->getSessionKiosk($request);
        $this->ensureOwnedByKiosk($printJob, $kiosk);

        $printJob->update(['estado' => 'cancelado']);

        $payment = $printJob->transacciones()->first();
        if ($payment) {
            $payment->update([
                'estado' => 'cancelado',
            ]);
        }

        Log::info('Trabajo cancelado desde panel kiosko', [
            'kiosk_id' => $kiosk->id,
            'job_reference' => $printJob->id,
        ]);

        return back()->with('success', 'Trabajo cancelado.');
    }

    private function getSessionKiosk(Request $request): Kiosko
    {
        /** @var Kiosko $kiosk */
        $kiosk = $request->attributes->get('kiosk_session');

        return $kiosk;
    }

    private function ensureOwnedByKiosk(OrdenImpresion $printJob, Kiosko $kiosk): void
    {
        if ($printJob->kiosko_id !== $kiosk->id) {
            abort(403, 'No puedes gestionar trabajos de otro kiosko.');
        }
    }
}
