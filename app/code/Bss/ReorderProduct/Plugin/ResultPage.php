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
namespace Bss\ReorderProduct\Plugin;

class ResultPage
{

    protected $request;

    protected $layout;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Layout $layout
    ) {
        $this->request = $request;
        $this->layout = $layout;
    }

    public function beforeAddPageLayoutHandles(\Magento\Framework\View\Result\Page $subject, array $parameters = [], $defaultHandle = null)
    {
        if ($this->request->getFullActionName() == 'reorder-product_catalog_product_view') {
            $arrayKeys = array_keys($parameters);
            if ((count($arrayKeys) == 3) &&
                in_array('id', $arrayKeys) &&
                in_array('sku', $arrayKeys) &&
                in_array('type', $arrayKeys)) {
                return [$parameters, 'catalog_product_view'];
            }
        } else {
            return [$parameters, $defaultHandle];
        }
    }
}
