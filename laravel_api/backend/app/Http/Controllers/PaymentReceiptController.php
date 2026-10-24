<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentReceiptController extends Controller
{
    protected PaymentReceiptService $receiptService;

    public function __construct(PaymentReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Download receipt via secure signed temporary URL (no login required).
     */
    public function downloadSigned(Request $request, Payment $payment): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired receipt link.');
        }

        try {
            $pdfBinary = $this->receiptService->pdf($payment);
        } catch (\Throwable $e) {
            return response('Receipt PDF engine problem: ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $receiptNo = $payment->receipt_no ?: "payment-{$payment->id}";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-' . $receiptNo . '.pdf"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Download receipt for authenticated student (or their parent).
     */
    public function downloadStudent(Request $request, Payment $payment): Response
    {
        $user = $request->user();

        $canAccess = ($payment->user_id === $user->id) ||
                     ($payment->user && $payment->user->parent_id === $user->id) ||
                     ($user->role === 'admin' || $user->role === 'super_admin');

        if (!$canAccess) {
            abort(403, 'Unauthorized to view this receipt.');
        }

        try {
            $pdfBinary = $this->receiptService->pdf($payment);
        } catch (\Throwable $e) {
            return response('Receipt PDF engine problem: ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $receiptNo = $payment->receipt_no ?: "payment-{$payment->id}";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="receipt-' . $receiptNo . '.pdf"',
        ]);
    }
}
