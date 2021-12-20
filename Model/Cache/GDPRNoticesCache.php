<?php
namespace Paynow\PaymentGateway\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool as FrontendPoolAlias;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Class GDPRNoticesCache
 *
 * @package Paynow\PaymentGateway\Model\Cache
 */
class GDPRNoticesCache extends TagScope
{
    const TYPE_IDENTIFIER = 'paynow';

    const CACHE_TAG = 'PAYNOW';

/**
 * @param FrontendPoolAlias $cacheFrontendPool
 */
    public function __construct(FrontendPoolAlias $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}

