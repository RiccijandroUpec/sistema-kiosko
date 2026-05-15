<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class KioskController extends Controller
{
    public function index()
    {
        $kiosks = Kiosk::withCount(['printJobs', 'payments'])
            ->orderBy('nombre')
            ->get();

        return view('admin.kiosks', compact('kiosks'));
    }

    public function printableQr(Kiosk $kiosk)
    {
        return view('admin.kiosk-qr', compact('kiosk'));
    }

    public function printableQrPdf(Kiosk $kiosk)
    {
        $html = view('admin.kiosk-qr-pdf', [
            'kiosk' => $kiosk,
            'qrDataUri' => $this->buildQrDataUri($kiosk),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $fileName = 'QR-' . preg_replace('/[^A-Za-z0-9\-_]+/', '-', $kiosk->nombre) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'ubicacion' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:80|unique:kiosks,api_token',
            'access_pin' => 'nullable|digits:4',
            'estado_conexion' => 'required|in:offline,online,maintenance',
        ]);

        Kiosk::create([
            'nombre' => $validated['nombre'],
            'ubicacion' => $validated['ubicacion'] ?? null,
            'api_token' => !empty($validated['api_token']) ? $validated['api_token'] : Str::random(60),
            'access_pin' => $validated['access_pin'] ?? null,
            'estado_conexion' => $validated['estado_conexion'],
            'last_seen_at' => $validated['estado_conexion'] === 'online' ? now() : null,
        ]);

        return redirect()->route('admin.kiosks.index')->with('success', 'Kiosko creado correctamente.');
    }

    public function update(Request $request, Kiosk $kiosk)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'ubicacion' => 'nullable|string|max:255',
            'access_pin' => 'nullable|digits:4',
            'estado_conexion' => 'required|in:offline,online,maintenance',
        ]);

        $kiosk->update([
            'nombre' => $validated['nombre'],
            'ubicacion' => $validated['ubicacion'] ?? null,
            'access_pin' => $validated['access_pin'] ?? null,
            'estado_conexion' => $validated['estado_conexion'],
            'last_seen_at' => $validated['estado_conexion'] === 'online' ? now() : $kiosk->last_seen_at,
        ]);

        return back()->with('success', 'Kiosko actualizado correctamente.');
    }

    public function destroy(Kiosk $kiosk)
    {
        $kiosk->delete();

        return back()->with('success', 'Kiosko eliminado correctamente.');
    }

    private function buildQrDataUri(Kiosk $kiosk): string
    {
        $whatsappNumber = config('evolution.whatsapp_number', '+1234567890');
        $location = trim((string) ($kiosk->ubicacion ?? ''));
        $label = trim($kiosk->nombre . ($location !== '' ? ' - ' . $location : ''));
        $whatsappMessage = "Estoy en {$label}";

        $cleanNumber = str_replace(['+', ' ', '-'], '', $whatsappNumber);
        $whatsappLink = "https://wa.me/{$cleanNumber}?text=" . rawurlencode($whatsappMessage);

        $qrCode = new QrCode($whatsappLink);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }
}