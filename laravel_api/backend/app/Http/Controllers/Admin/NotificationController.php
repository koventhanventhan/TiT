<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Auth::user()->notifications()->latest();
        
        if ($request->ajax() || $request->wantsJson()) {
            $unreadCount = Auth::user()->unreadNotifications()->count();
            
            // System Alerts
            $pendingApprovals = \App\Models\User::where('role', 'user')->whereNull('admin_confirmed_at')->count();
            $pendingPayments = \App\Models\Payment::where('status', 'pending')->count();
            $pendingTopups = \App\Models\Payment::where('status', 'pending')->where('kind', 'topup')->count();
            $pendingMonthly = $pendingPayments - $pendingTopups;

            return response()->json([
                'notifications' => $notifications->take(10)->get(),
                'unreadCount' => $unreadCount,
                'systemAlerts' => [
                    'pendingApprovals' => $pendingApprovals,
                    'pendingPayments' => $pendingPayments,
                    'pendingMonthly' => $pendingMonthly,
                    'pendingTopups' => $pendingTopups,
                    'pendingPaymentsSplit' => "{$pendingMonthly} monthly + {$pendingTopups} top-ups",
                ]
            ]);
        }

        $notifications = $notifications->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request)
    {
        $notification = Auth::user()->notifications()->find($request->id);
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

}
