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
namespace Bss\ReorderProduct\Model\ResourceModel\OrderItem;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Item\Collection
{
    public function filterOrderIds($_orderids)
    {
        $this->addFieldToFilter('order_id', ['in' => $_orderids]);
        $this->addFieldToFilter('parent_item_id', ['null' => true]);
        $this->getSelect()
                    ->columns('MAX(item_id) as reoder_item_id')
                    ->columns('SUM(qty_ordered) as reoder_qty_ordered')
                    ->group(['reorder_item_options', 'sku']);
        return $this;
    }

    public function getChildProduct($parent_item_id)
    {
        $this->addFieldToFilter('parent_item_id',$parent_item_id);
        return $this;
    }
}
