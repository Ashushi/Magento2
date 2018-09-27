<?php

namespace TPB\QuickReorder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CustomerGroups implements OptionSourceInterface
{
    /**
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param GroupManagementInterface $groupManagement
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GroupManagementInterface $groupManagement,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->groupManagement = $groupManagement;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get an array of customer groups
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerGroups()
    {
        $groups = [];
        $notLoggedInGroup = $this->groupManagement->getNotLoggedInGroup();
        foreach ($this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $item) {
            $groups[$item->getId()] = $item->getCode();
        }
        $groups[$notLoggedInGroup->getId()] = $notLoggedInGroup->getCode();

        return $groups;
    }

    /**
     * @inheritdoc
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getCustomerGroups() as $id => $name) {
            $options[] = [
                'value' => $id,
                'label' => $name
            ];
        }
        return $options;
    }
}
