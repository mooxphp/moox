<?php

namespace Moox\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Moox\Notification\Models\Notification;
use Illuminate\Http\Request;


class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     * Currently not used
     */
    public function index()
    {
        $notifications = Notification::all();
        return response()->json([
            'notifications' => $notifications
        ],200,[],JSON_PRETTY_PRINT);
    }

     /**
     * Display Notification by UserId
     */
    public function getView($user)
    {
        $notifications = Notification::where('notifiable_id',$user)->count();

        return view('notifications::notificationBell',['unreadNotificationsCount'=>$notifications]);
    }

}
