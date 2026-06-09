<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientNotificationsController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return view('client.notifications.index', compact('notifications'));
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->route('client.notifications.index')->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
