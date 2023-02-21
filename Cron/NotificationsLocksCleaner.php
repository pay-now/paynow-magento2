<?php

namespace Paynow\PaymentGateway\Cron;

use Paynow\PaymentGateway\Helper\LockingHelper;

/**
 * Class NotificationsLocksCleaner.php
 *
 * @package Paynow\PaymentGateway\Cron
 */
class NotificationsLocksCleaner
{

    /**
     * @var LockingHelper
     */
    private $lockingHelper;

    /**
     * @param LockingHelper $lockingHelper
     */
    public function __construct(LockingHelper $lockingHelper)
    {
        $this->lockingHelper = $lockingHelper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->lockingHelper->cleanUpExpiredLocks();
    }
}
