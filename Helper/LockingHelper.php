<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\Filesystem\DirectoryList;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class LockingHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class LockingHelper
{
    private static $LOCKS_DIR = 'paynow-locks';
    private static $LOCKS_PREFIX = 'paynow-lock-';
    private static $LOCKED_TIME = 35;

    /**
     * @string
     */
    public $locksDirPath;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var bool
     */
    public $lockEnabled = true;

    /**
     * @param DirectoryList $dir
     * @param Logger $logger
     */
    public function __construct(DirectoryList $dir, Logger $logger)
    {
        // Setup locks dir
        try {
            $varPath = $dir->getPath('var');
            $lockPath = $varPath . DIRECTORY_SEPARATOR . self::$LOCKS_DIR;
            // phpcs:ignore
            @mkdir($lockPath);
            // phpcs:ignore
            if (is_dir($lockPath) && is_writable($lockPath)) {
                $this->locksDirPath = $lockPath;
            } else {
                $this->locksDirPath = sys_get_temp_dir();
            }
        } catch (\Exception $exception) {
            $this->locksDirPath = sys_get_temp_dir();
        }
        // phpcs:ignore
        $this->lockEnabled = is_writable($this->locksDirPath);
        if ($this->lockEnabled == false) {
            $logger->critical('Locking mechanism disabled.', ['locksDirPath' => $this->locksDirPath]);
        }
    }

    /**
     * @param $externalId
     * @return bool
     */
    public function checkAndCreate($externalId)
    {
        if (!$this->lockEnabled) {
            return false;
        }
        $lockFilePath = $this->generateLockPath($externalId);
        // phpcs:ignore
        $lockExists = file_exists($lockFilePath);
        // phpcs:ignore
        if ($lockExists && (filemtime($lockFilePath) + self::$LOCKED_TIME) > time()) {
            return true;
        } else {
            $this->create($externalId, $lockExists);
            return false;
        }
    }

    /**
     * @param $externalId
     * @return void
     */
    public function delete($externalId)
    {
        if (empty($externalId)) {
            return;
        }
        $lockFilePath = $this->generateLockPath($externalId);
        // phpcs:ignore
        if (file_exists($lockFilePath)){
            // phpcs:ignore
            unlink($lockFilePath);
        }
    }

    public function cleanUpExpiredLocks()
    {
        // phpcs:ignore
        $allLocksList = @glob($this->locksDirPath . DIRECTORY_SEPARATOR . self::$LOCKS_PREFIX . '*.lock');
        if (!is_array($allLocksList)) {
            return;
        }
        foreach ($allLocksList as $lockFilePath) {
            // phpcs:ignore
            if ((filemtime($lockFilePath) + self::$LOCKED_TIME) < time()) {
                // phpcs:ignore
                unlink($lockFilePath);
            }
        }
    }

    /**
     * @param $externalId
     * @param $lockExists
     * @return void
     */
    private function create($externalId, $lockExists)
    {
        $lockPath = $this->generateLockPath($externalId);
        if ($lockExists) {
            // phpcs:ignore
            touch($lockPath);
        } else {
            // phpcs:ignore
           $fileSaved = @file_put_contents($lockPath, '');
            if ($fileSaved === false) {
               $this->logger->critical('Locking failed', ['externalId' => $externalId, 'lockPath' => $lockPath]);
            }
        }
    }

    /**
     * @param $externalId
     * @return string
     */
    private function generateLockPath($externalId)
    {
        // phpcs:ignore
        return $this->locksDirPath . DIRECTORY_SEPARATOR . self::$LOCKS_PREFIX . md5($externalId) . '.lock';
    }
}
