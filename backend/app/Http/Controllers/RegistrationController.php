<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    /**
     * Step 1: Validate student form and create user with pending_payment.
     * Requires phone_number for WhatsApp.
     */
    public function step1(Request $request)
    {
        $gradeNum = null;
        if ($request->current_grade) {
            if (preg_match('/தரம்\s*(\d+)/', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            } elseif (preg_match('/Grade\s*(\d+)/i', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            }
        }

        $rules = [
            'username' => 'required|string|max:255|unique:users,name',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'required|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'required|string',
        ];

        $request->validate($rules);

        $userData = [
            'name' => $request->username,
            'email' => $request->username . '@student.local',
            'password' => Hash::make('student123'),
            'role' => 'user',
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'school_name' => $request->school_name,
            'medium' => $request->medium,
            'online_experience' => $request->boolean('online_experience'),
            'device_used' => is_array($request->device_used)
                ? json_encode($request->device_used)
                : $request->device_used,
            'current_grade' => $request->current_grade,
            'stream' => $request->stream ?? null,
            'selected_subjects' => $request->selected_subjects,
            'registration_status' => 'pending_payment',
            'admin_confirmed_at' => null,
        ];

        $user = User::create($userData);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration step 1 complete. Proceed to payment.',
            'user' => $this->formatUserResponse($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Step 2: Payment choice. Offline = just success. Online = create payment order (Razorpay).
     */
    public function step2(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:offline,online',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $user = $request->user();
        if ($user->role !== 'user' || !$user->full_name) {
            return response()->json(['message' => 'Invalid user.'], 403);
        }

        if ($request->payment_method === 'offline') {
            return response()->json([
                'message' => 'Registration submitted. Please complete payment offline. Admin will confirm and you will receive a WhatsApp message.',
                'registration_status' => $user->registration_status,
            ]);
        }

        // Online: create Razorpay order if configured, else create a pending payment record and return order_id placeholder
        $amount = $request->amount ? (float) $request->amount : (float) config('payment.monthly_amount', 500);
        $yearMonth = now()->format('Y-m');

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 'pending',
            'year_month' => $yearMonth,
            'gateway_ref' => 'order_' . uniqid(),
        ]);

        $orderId = $payment->gateway_ref;

        // If Razorpay is configured, create real order (we'll add Razorpay service later)
        $razorpayOrderId = null;
        if (config('payment.razorpay_key') && config('payment.razorpay_secret')) {
            try {
                $razorpayOrderId = $this->createRazorpayOrder($payment);
                if ($razorpayOrderId) {
                    $payment->update(['gateway_ref' => $razorpayOrderId]);
                    $orderId = $razorpayOrderId;
                }
            } catch (\Throwable $e) {
                \Log::warning('Razorpay order creation failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Payment order created.',
            'order_id' => $orderId,
            'amount' => $amount * 100, // paise for Razorpay
            'currency' => 'INR',
            'key' => config('payment.razorpay_key'),
        ]);
    }

    /**
     * Called by frontend after successful gateway payment. Verify and mark payment paid.
     */
    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_id' => 'nullable|string', // Razorpay payment_id
        ]);

        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->where('gateway_ref', $request->order_id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found or already processed.'], 404);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $user->update(['registration_status' => 'paid_pending_confirm']);

        return response()->json([
            'message' => 'Payment successful. Admin will confirm and you will receive a WhatsApp message.',
            'registration_status' => 'paid_pending_confirm',
        ]);
    }

    private function createRazorpayOrder(Payment $payment): ?string
    {
        $key = config('payment.razorpay_secret');
        if (!$key || !class_exists(\Razorpay\Api\Api::class)) {
            return null;
        }
        $amountPaise = (int) round($payment->amount * 100);
        $client = new \Razorpay\Api\Api(config('payment.razorpay_key'), $key);
        $order = $client->order->create([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => 'pay_' . $payment->id,
        ]);
        return $order['id'] ?? null;
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'school_name' => $user->school_name,
            'medium' => $user->medium,
            'online_experience' => $user->online_experience,
            'device_used' => $user->device_used,
            'current_grade' => $user->current_grade,
            'stream' => $user->stream,
            'selected_subjects' => $user->selected_subjects,
            'registration_status' => $user->registration_status,
        ];
    }
}
