<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_ReorderProduct
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\ReorderProduct\Model\Config\Source;

class Sortoder implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
                ['value' => 'name', 'label' => __('Name')],
                ['value' => 'price', 'label' => __('Price')],
                ['value' => 'created_at', 'label' => __('Ordered Date')],
                ['value' => 'qty_ordered', 'label' => __('Ordered Qty')]
                ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['name' => __('Name'),
                'price' => __('Price'),
                'created_at' => __('Ordered Date'),
                'qty_ordered' => __('Ordered Qty')
                ];
    }
}
