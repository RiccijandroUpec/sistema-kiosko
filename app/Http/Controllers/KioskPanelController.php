<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskPanelController extends Controller
{
    public function dashboard(Request $request)
    {
        $kiosk = $this->getSessionKiosk($request);

        $query = PrintJob::with(['pdfFile', 'payment'])
            ->where('kiosk_id', $kiosk->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('paid')) {
            $query->where('paid', $request->paid === 'yes');
        }

        $printJobs = $query->paginate(15);

        $stats = [
            'total' => PrintJob::where('kiosk_id', $kiosk->id)->count(),
            'pending' => PrintJob::where('kiosk_id', $kiosk->id)->where('status', 'pending')->count(),
            'printing' => PrintJob::where('kiosk_id', $kiosk->id)->where('status', 'printing')->count(),
            'completed' => PrintJob::where('kiosk_id', $kiosk->id)->where('status', 'completed')->count(),
        ];

        return view('kiosko.panel', compact('kiosk', 'printJobs', 'stats'));
    }

    public function markAsPrinted(Request $request, PrintJob $printJob)
    {
        $kiosk = $this->getSessionKiosk($request);
        $this->ensureOwnedByKiosk($printJob, $kiosk);

        $printJob->update([
            'status' => 'completed',
            'paid' => true,
            'printed_at' => now(),
        ]);

        if ($printJob->payment) {
            $printJob->payment->update(['status' => 'confirmed']);
        }

        return back()->with('success', 'Trabajo marcado como completado.');
    }

    public function cancelJob(Request $request, PrintJob $printJob)
    {
        $kiosk = $this->getSessionKiosk($request);
        $this->ensureOwnedByKiosk($printJob, $kiosk);

        $printJob->update(['status' => 'cancelled']);

        if ($printJob->payment && $printJob->payment->status === 'confirmed') {
            $printJob->payment->update([
                'status' => 'cancelled',
                'notes' => 'Trabajo cancelado desde panel de kiosko',
            ]);
        }

        Log::info('Trabajo cancelado desde panel kiosko', [
            'kiosk_id' => $kiosk->id,
            'job_reference' => $printJob->job_reference,
        ]);

        return back()->with('success', 'Trabajo cancelado.');
    }

    private function getSessionKiosk(Request $request): Kiosk
    {
        /** @var Kiosk $kiosk */
        $kiosk = $request->attributes->get('kiosk_session');

        return $kiosk;
    }

    private function ensureOwnedByKiosk(PrintJob $printJob, Kiosk $kiosk): void
    {
        if ((int) $printJob->kiosk_id !== (int) $kiosk->id) {
            abort(403, 'No puedes gestionar trabajos de otro kiosko.');
        }
    }
}
