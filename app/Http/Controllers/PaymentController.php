<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Marcar pago como confirmado (Admin).
     */
    public function confirmPayment(Request $request, Payment $payment)
    {
        $request->validate([
            'bank_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment->update([
                'status' => 'confirmed',
                'bank_name' => $request->bank_name,
                'notes' => $request->notes,
            ]);

            // Marcar trabajo como pagado y listo para imprimir
            $payment->printJob->update([
                'paid' => true,
                'status' => 'printing',
            ]);

            Log::info('Pago confirmado', [
                'payment_id' => $payment->id,
                'reference_code' => $payment->reference_code,
                'amount' => $payment->amount,
            ]);

            return back()->with('success', 'Pago confirmado. El trabajo está listo para imprimir.');
        } catch (\Exception $e) {
            Log::error('Error al confirmar pago', ['error' => $e->getMessage()]);
            return back()->withErrors('Error al confirmar el pago.');
        }
    }

    /**
     * Cancelar pago.
     */
    public function cancelPayment(Request $request, Payment $payment)
    {
        try {
            $payment->update(['status' => 'cancelled']);
            $payment->printJob->update(['status' => 'cancelled']);

            Log::info('Pago cancelado', ['payment_id' => $payment->id]);

            return back()->with('success', 'Pago cancelado.');
        } catch (\Exception $e) {
            Log::error('Error al cancelar pago', ['error' => $e->getMessage()]);
            return back()->withErrors('Error al cancelar el pago.');
        }
    }

    /**
     * Obtener detalles de pago (API).
     */
    public function getPaymentDetails(Payment $payment)
    {
        return response()->json([
            'success' => true,
            'payment' => [
                'reference_code' => $payment->reference_code,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
