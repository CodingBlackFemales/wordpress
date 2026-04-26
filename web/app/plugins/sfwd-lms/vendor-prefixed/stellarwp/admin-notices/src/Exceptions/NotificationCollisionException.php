<?php

declare(strict_types=1);


namespace StellarWP\Learndash\StellarWP\AdminNotices\Exceptions;

use RuntimeException;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;

class NotificationCollisionException extends RuntimeException
{
    protected $notificationId;

    protected $notification;

    public function __construct(string $notificationId, AdminNotice $notification)
    {
        $this->notificationId = $notificationId;
        $this->notification = $notification;

        parent::__construct("Notification with ID $notificationId already exists.");
    }
}
