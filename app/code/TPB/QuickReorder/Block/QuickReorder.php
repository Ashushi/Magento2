<?php
namespace TPB\QuickReorder\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Catalog\Model\ProductFactory;
use TPB\QuickReorder\Model\Config;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\View\Element\Template;

/**
 * Customer Quick Reorder block
 */
class QuickReorder extends Template
{
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

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
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
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
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param Session $customerSession
     * @param OrderConfig $orderConfig
     * @param ProductFactory $productloader
     * @param Config $config
     * @param Configurable $productTypeConfigurable
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        ItemCollectionFactory $itemCollectionFactory,
        Session $customerSession,
        OrderConfig $orderConfig,
        ProductFactory $productloader,
        Config $config,
        Configurable $productTypeConfigurable,
        array $data = []
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
        $this->productloader = $productloader;
        $this->config = $config;
        $this->productTypeConfigurable = $productTypeConfigurable;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve a list of ordered products
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__($this->config->getTitle()));
        $product_name = trim($this->getRequest()->getParam('product_name'));
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
        $collection->addFieldToFilter('order_id', ['in' => $_orderids])
                    ->addFieldToFilter('product_type', 'simple')
                    ->getSelect()
                    ->join(array('stock' => 'cataloginventory_stock_status'), 'main_table.product_id = stock.product_id', 'stock.stock_status')
                    ->where('stock.stock_status = 1')
                    ->columns('SUM(qty_ordered) as total_qty')
                    ->group('main_table.product_id')
                    ->order(array('total_qty DESC', 'name ASC'));
        $this->setCollection($collection);
    }

	/**
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return false;
        }
        if (!$this->orders) {
            $this->orders = $this->getOrderCollectionFactory()->create($customerId)->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'status',
                ['in' => $this->orderConfig->getVisibleOnFrontStatuses()]
            )->setOrder(
                'created_at',
                'desc'
            );
        }
        return $this->orders;
    }

    /**
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'quick.reorder.pager'
            )->setAvailableLimit(array($this->config->getListperpage() => $this->config->getListperpage()))
            ->setCollection(
                $this->getCollection()
            );
            $this->setChild('pager', $pager);
            $this->getCollection()->load();
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
