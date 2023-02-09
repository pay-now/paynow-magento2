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
    private static $LOCKS_DIR = 'paynowLocks';
    private static $LOCKS_PREFIX = 'paynowLock_';
    private static $LOCKED_TIME = 6;

    /**
     * @string
     */
    public $locksDirPath;

    /**
     * @param DirectoryList $dir
     */
    public function __construct(DirectoryList $dir)
    {
        // Setup locks dir
        try {
            $varPath = $dir->getPath('var');
            $lockPath = $varPath . self::$LOCKS_DIR;
            if (mkdir($lockPath)) {
                $this->locksDirPath = $lockPath;
            } else {
                $this->locksDirPath = sys_get_temp_dir();
            }
        } catch (\Exception $exception) {
            $this->locksDirPath = sys_get_temp_dir();
        }
    }

    /**
     * @param $externalId
     * @return bool
     */
    public function isLocked($externalId)
    {
        $lockFilePath = $this->generateLockPath($externalId);
        $lockExists = file_exists($lockFilePath);
        if (
            $lockExists && filemtime($lockFilePath) + self::$LOCKED_TIME > time()
        ) {
            return true;
        } else {
            $this->lock($externalId, $lockExists);
            return false;
        }
    }

    public function cleanUpExpiredLocks()
    {
        $allLocksList = glob($this->locksDirPath . DIRECTORY_SEPARATOR . self::$LOCKS_PREFIX . '*.lock');
        if ($allLocksList == false || (is_array($allLocksList) && count($allLocksList) == 0)) {
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
    private function lock($externalId, $lockExists)
    {
        $lockPath = $this->generateLockPath($externalId);
        if ($lockExists == true) {
            unlink($lockPath);
        }
        file_put_contents($lockPath, '');
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