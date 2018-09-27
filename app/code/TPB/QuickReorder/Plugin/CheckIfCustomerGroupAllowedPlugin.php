<?php

namespace TPB\QuickReorder\Plugin;

use Magento\Customer\Model\Session as CustomerSession;
use TPB\QuickReorder\Controller\Index\Index;
use Magento\Framework\Exception\NotFoundException;
use TPB\QuickReorder\Model\Config;

class CheckIfCustomerGroupAllowedPlugin
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CustomerSession $customerSession
     * @param Config $config
     */
    public function __construct(
        CustomerSession $customerSession,
        Config $config
    ) {
        $this->customerSession = $customerSession;
        $this->config = $config;
    }

    /**
     * Redirect to 404 page if the customer group they are assigned to is not allowed to view the quick reorder page
     *
     * @see \TPB\QuickReorder\Controller\Index\Index::execute()
     * @param Index $subject
     * @param callable $proceed
     * @param mixed ...$args
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function aroundExecute(Index $subject, callable $proceed, ...$args)
    {
        if (! $this->isCustomerGroupAllowed($this->customerSession->getCustomerGroupId())) {
            throw new NotFoundException(__('Page not found'));
        }

        return $proceed(...$args);
    }

    /**
     * Check if $id is in the allowed customer groups list
     *
     * @param $id
     * @return bool
     */
    private function isCustomerGroupAllowed($id)
    {
        return in_array($id, $this->config->getAllowedCustomerGroups());
    }
}
