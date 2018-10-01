<?php
namespace TPB\QuickReorder\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Pager;
use TPB\QuickReorder\Model\Config;

/**
 * Customer Quick Reorder block
 */
class QuickReorder extends Template
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var OrderConfig
     */
    private $orderConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Collection
     */
    private $orders;

    /**
     * @var ProductFactory
     */
    private $productloader;

    /**
     * @var ItemCollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * @var Configurable
     */
    private $productTypeConfigurable;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param Session $customerSession
     * @param OrderConfig $orderConfig
     * @param ProductFactory $productloader
     * @param Config $config
     * @param Configurable $productTypeConfigurable
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        ItemCollectionFactory $itemCollectionFactory,
        Session $customerSession,
        OrderConfig $orderConfig,
        ProductFactory $productloader,
        Config $config,
        Configurable $productTypeConfigurable,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
        $this->productloader = $productloader;
        $this->config = $config;
        $this->productTypeConfigurable = $productTypeConfigurable;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__($this->config->getTitle()));
    }

    /**
     * Retrieve a list of ordered products
     * 
     * @return bool|Collection
     */
    public function getOrderedItems()
    {
        $product_name = trim($this->getRequest()->getParam('product_name'));
        $p = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
        $_orders = $this->getOrders();
        $_orderids = [];
        foreach ($_orders as $_order) {
            $_orderids[] = $_order->getId();
        }
        $collection = $this->itemCollectionFactory->create();
        if (!empty($product_name)) {
            $collection->addFieldToFilter(
                array('name', 'sku'),
                array(
                    array('like'=>'%'.$product_name.'%'),
                    array('like'=>'%'.$product_name.'%')
                )
            );
        }
        $collection->setPageSize($this->config->getListperpage())->setCurPage($p);
        $collection->addFieldToFilter('order_id', ['in' => $_orderids])
                   ->addFieldToFilter('product_type', 'simple')
                   ->getSelect()
                   ->join(array('stock' => 'cataloginventory_stock_status'), 'main_table.product_id = stock.product_id', 'stock.stock_status')
                   ->where('stock.stock_status = 1')
                   ->columns('SUM(qty_ordered) as total_qty')
                   ->group('main_table.product_id')
                   ->order(array('total_qty DESC', 'name ASC'));
        return $collection;
    }

    /**
     * @return bool|Collection
     */
    public function getOrders()
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return false;
        }
        if (!$this->orders) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('status', $this->orderConfig->getVisibleOnFrontStatuses(), 'in')->create();
            $this->orders = $this->orderRepository->getList($searchCriteria);
        }
        return $this->orders;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getOrderedItems()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class, 'quick.reorder.pager'
            )->setAvailableLimit(array($this->config->getListperpage() => $this->config->getListperpage()))
            ->setCollection(
                $this->getOrderedItems()
            );
            $this->setChild('pager', $pager);
            $this->getOrderedItems()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * To retrieve product
     *
     * @param int $id
     * @return \Magento\Catalog\Model\Product
     */
    public function getProductById($id)
    {
        return $this->productloader->create()->load($id);
    }

    /**
     * To retrieve product's parent id
     *
     * @param int $id
     * @return int
     */
    public function getParentId($id)
    {
        $parentId = '';
        $parentByChild = $this->productTypeConfigurable->getParentIdsByChild($id);
        if (isset($parentByChild[0])) {
            $parentId = $parentByChild[0];
        }
        return $parentId;
    }
}
