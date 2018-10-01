<?php

namespace TPB\QuickReorder\Plugin;

use Magento\Customer\Model\Session as CustomerSession;
use TPB\QuickReorder\Controller\Index\Index;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\Result\Redirect;
use TPB\QuickReorder\Controller\Index\Index;
use TPB\QuickReorder\Model\Config;

/**
 * Plugin To check customer in the allowed customer groups list
 */
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
     * @see Index::execute()
     * @param Index $subject
     * @param callable $proceed
     * @param mixed ...$args
     * @return Redirect
     * @throws NotFoundException
     */
    public function aroundExecute(Index $subject, callable $proceed, ...$args)
    {
        if (!$this->isCustomerGroupAllowed($this->customerSession->getCustomerGroupId())) {
            throw new NotFoundException(__('Page not found'));
        }

        return $proceed(...$args);
    }

    /**
     * Check if $id is in the allowed customer groups list
     *
     * @param int|string $id
     * @return bool
     */
    private function isCustomerGroupAllowed($id)
    {
        return in_array($id, $this->config->getAllowedCustomerGroups());
    }
}
