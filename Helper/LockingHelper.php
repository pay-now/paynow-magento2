<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\Filesystem\DirectoryList;

/**
 * Class LockingHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class LockingHelper
{
    private static $LOCKS_DIR = 'paynow-locks';
    private static $LOCKS_PREFIX = 'paynow-lock_';
    private static $LOCKED_TIME = 6;

    /**
     * @string
     */
    public $locksDirPath;

    /**
     * @var bool
     */
    public $lockEnabled = true;

    /**
     * @param DirectoryList $dir
     */
    public function __construct(DirectoryList $dir)
    {
        // Setup locks dir
        try {
            $varPath = $dir->getPath('var');
            $lockPath = $varPath . DIRECTORY_SEPARATOR . self::$LOCKS_DIR;
            @mkdir($lockPath);
            if (is_dir($lockPath) && is_writable($lockPath)) {
                $this->locksDirPath = $lockPath;
            } else {
                $this->locksDirPath = sys_get_temp_dir();
            }
        } catch (\Exception $exception) {
            $this->locksDirPath = sys_get_temp_dir();
        }
        $this->lockEnabled = is_writable($this->locksDirPath);
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
        $lockExists = file_exists($lockFilePath);
        if (
            $lockExists && filemtime($lockFilePath) + self::$LOCKED_TIME > time()
        ) {
            return true;
        } else {
            $this->create($externalId, $lockExists);
            return false;
        }
    }

    public function cleanUpExpiredLocks()
    {
        $allLocksList = @glob($this->locksDirPath . DIRECTORY_SEPARATOR . self::$LOCKS_PREFIX . '*.lock');
        if (!is_array($allLocksList)) {
            return;
        }
        foreach ($allLocksList as $lockFilePath) {
            if (filemtime($lockFilePath) + self::$LOCKED_TIME < time()) {
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
            touch($lockPath);
        } else {
            @file_put_contents($lockPath, '');
        }
    }

    /**
     * @param $externalId
     * @return string
     */
    private function generateLockPath($externalId)
    {
        return $this->locksDirPath . DIRECTORY_SEPARATOR . self::$LOCKS_PREFIX . $externalId . '.lock';
    }
}