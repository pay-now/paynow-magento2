<?php

namespace Paynow\PaymentGateway\Block\Adminhtml\System\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class StoredKeyMasker
 *
 * @package Paynow\PaymentGateway\Block\Adminhtml\System\Config\Comment
 */
class StoredKeyMasker implements CommentInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * ApiKeyStore constructor.
     *
     * @param EncryptorInterface $encryptor Magento encryptor, used to decrypt the stored key.
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Returns the last 6 chars in stored key.
     *
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        $apiKeyStored = substr($this->encryptor->decrypt(trim($elementValue ?? '')), -6);
        if (!$apiKeyStored) {
            return '';
        }
        return "Stored Key: *******-****-****-****-******$apiKeyStored";
    }
}
