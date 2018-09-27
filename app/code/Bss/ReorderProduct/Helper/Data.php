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
namespace Bss\ReorderProduct\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    private $configSectionId = 'reorderproduct';

    public function getConfigFlag($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->isSetFlag($path, $scope, $store);
    }

    public function getConfigValue($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }

    public function isActive()
    {
        return $this->getConfigFlag($this->configSectionId.'/general/active');
    }

    public function isRedirecttocart()
    {
        return $this->getConfigFlag($this->configSectionId.'/general/redirect_cart');
    }
    public function isRedirecttowishlist()
    {
        return $this->getConfigFlag($this->configSectionId.'/general/redirect_wishlist');
    }
    public function showbtnWishlist()
    {
        return $this->getConfigFlag($this->configSectionId.'/list_reoderproduct/btnwishlist');
    }
    public function showColumns()
    {
        return $this->getConfigValue($this->configSectionId.'/list_reoderproduct/show_columns');
    }
    public function showbtnQuickview()
    {
        return $this->getConfigValue($this->configSectionId.'/list_reoderproduct/show_quickview');
    }
    public function showSku()
    {
        return $this->getConfigFlag($this->configSectionId.'/list_reoderproduct/show_sku');
    }
    public function showQtyInventory()
    {
        return $this->getConfigFlag($this->configSectionId.'/list_reoderproduct/qty_inventory');
    }
    public function getListperpagevalue()
    {
        return $this->getConfigValue($this->configSectionId.'/list_reoderproduct/list_per_page_values');
    }
    public function getListperpage()
    {
        return $this->getConfigValue($this->configSectionId.'/list_reoderproduct/list_per_page');
    }
    public function getSortby()
    {
        return $this->getConfigValue($this->configSectionId.'/list_reoderproduct/sort_by');
    }
    public function showAlllist()
    {
        return $this->getConfigFlag($this->configSectionId.'/list_reoderproduct/list_allow_all');
    }
}
