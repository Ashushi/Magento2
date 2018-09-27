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

class DisplayColumns implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
                ['value' => '0', 'label' => __('Checkbox')],
                ['value' => '1', 'label' => __('Image')],
                ['value' => '2', 'label' => __('Product Name')],
                ['value' => '3', 'label' => __('Ordered Price')],
                ['value' => '4', 'label' => __('Qty')],
                ['value' => '5', 'label' => __('Ordered Qty')],
                ['value' => '6', 'label' => __('Ordered Date')],
                ['value' => '7', 'label' => __('Stock Status')],
                ['value' => '8', 'label' => __('Add to Cart')]
                ];
    }

    public function toArray()
    {
        return ['0' => __('Checkbox'),
                '1' => __('Image'),
                '2' => __('Product Name'),
                '3' => __('Ordered Price'),
                '4' => __('Qty'),
                '5' => __('Ordered Qty'),
                '6' => __('Ordered Date'),
                '7' => __('Stock Status'),
                '8' => __('Add to Cart')
                ];
    }
}
