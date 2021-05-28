<?php

namespace Paynow\PaymentGateway\Test\Unit\Block\Adminhtml\System\Config\Comment;

use Magento\Framework\Encryption\Encryptor;
use Paynow\PaymentGateway\Block\Adminhtml\System\Config\Comment\StoredKeyMasker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class StoredKeyMaskerTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Block\Adminhtml\System\Config\Comment
 */
class StoredKeyMaskerTest extends TestCase
{
    /**
     * @var StoredKeyMasker
     */
    private $storedKeyMasker;

    public function setUp()
    {
        /** @var MockObject|EncryptorInterface $encryptor */
        $encryptor = $this->createMock(Encryptor::class);
        $map       = [
            ['694586fc-e15a-4de8-a582-7d9884976a70', '*******-****-****-****-******976a70']
        ];
        $encryptor
            ->method('decrypt')
            ->will($this->returnValueMap($map));

        $this->storedKeyMasker = new StoredKeyMasker($encryptor);
    }

    public function testComment()
    {
        $this->assertEquals(
            'Stored Key: *******-****-****-****-******976a70',
            $this->storedKeyMasker->getCommentText('694586fc-e15a-4de8-a582-7d9884976a70')
        );
    }
}
