<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    public function sendNotification($deviceToken, $title, $body)
    {
        $messaging = app('firebase.messaging');
        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);
        try
        {
            $messaging->send($message);
            return "success sent";
        } catch (\Exception $e)
        {
            return "fail sent" . $e->getMessage();
        }
    }
}
