<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin controller over Laravel's built-in database notifications
 * (Notifiable::notifications() / unreadNotifications()) - there is no
 * dedicated Notification model of our own, since DatabaseNotification
 * (backed by the notifications table created in Stage 1) already covers
 * everything this feature needs.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    /**
     * Marks one notification read, then sends the user on to whatever it
     * was about (the payment request's detail page) - so clicking a
     * notification both dismisses it and takes you where it points.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $note = $request->user()->notifications()->findOrFail($notification);
        $note->markAsRead();

        return redirect($note->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
