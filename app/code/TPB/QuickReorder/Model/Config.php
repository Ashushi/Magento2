<?php

namespace TPB\QuickReorder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Used to retrieve store configuration values.
 */
class Config
{
    const CONFIG_XML_PATH_CUSTOMER_GROUP    = 'tpb_quickreorder/general/customer_group';
    const CONFIG_XML_PATH_TITLE             = 'tpb_quickreorder/general/title';
    const CONFIG_XML_PATH_PRODUCT_PER_PAGE  = 'tpb_quickreorder/general/product_per_page';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve a list of allowed customer groups
     *
     * @param string|null $store
     * @param string $scope
     * @return array
     */
    public function getAllowedCustomerGroups($store = null, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $result = $this->getConfigValue(static::CONFIG_XML_PATH_CUSTOMER_GROUP, $store, $scope);
        return $result === null ? [] : explode(',', $result);
    }

    /**
     * Retrieve a title
     *
     * @param string|null $store
     * @param string $scope
     * @return string|null
     */
    public function getTitle($store = null, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->getConfigValue(static::CONFIG_XML_PATH_TITLE, $store, $scope);
    }

    /**
     * Retrieve Product per page
     *
     * @param string|null $store
     * @param string $scope
     * @return string|null
     */
    public function getListperpage($store = null, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->getConfigValue(static::CONFIG_XML_PATH_PRODUCT_PER_PAGE, $store, $scope);
    }

    /**
     * Fetch value from configuration based on the given scope.
     *
     * @param string $path
     * @param string $scopeId
     * @param string $scope
     * @return string|null
     */
    private function getConfigValue($path, $scopeId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue($path, $scope, $scopeId);
    }
}
