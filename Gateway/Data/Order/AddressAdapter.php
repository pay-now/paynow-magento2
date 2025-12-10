<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paynow\PaymentGateway\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\Order\AddressAdapter as MagentoAddressAdapter;
use Paynow\PaymentGateway\Gateway\Data\AddressAdapterInterface;

/**
 * Class AddressAdapter
 * Extends Magento's payment AddressAdapter to provide possibility get all street addresses.
 */
class AddressAdapter extends MagentoAddressAdapter implements AddressAdapterInterface
{
}
