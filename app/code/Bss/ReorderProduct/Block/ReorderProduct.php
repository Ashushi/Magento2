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
namespace Bss\ReorderProduct\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class ReorderProduct extends \Magento\Framework\View\Element\Template
{
    protected $orderCollectionFactory;

    protected $customerSession;

    protected $orderConfig;

    protected $helper;

    protected $orders;

    protected $productloader;

    protected $stockRegistry;

    protected $stockStatusCriteriaFactory;

    protected $orderItem;

    protected $stockStatusRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bss\ReorderProduct\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Model\ProductFactory $productloader,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory,
        \Bss\ReorderProduct\Model\OrderItemFactory $orderItem,
        \Magento\CatalogInventory\Api\StockStatusRepositoryInterface $stockStatusRepository,
        array $data = []
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
        $this->productloader = $productloader;
        $this->stockRegistry = $stockRegistry;
        $this->stockStatusCriteriaFactory = $stockStatusCriteriaFactory;
        $this->orderItem = $orderItem;
        $this->stockStatusRepository = $stockStatusRepository;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Reorder Product'));
    }

    public function getProductById($id)
    {
        return $this->productloader->create()->load($id);
    }
    
    public function getStock($productId)
    {
        $criteria = $this->stockStatusCriteriaFactory->create();
        $criteria->setProductsFilter($productId);
        $result = $this->stockStatusRepository->getList($criteria);
        $stockStatus = current($result->getItems());
        return $stockStatus;
    }

    public function getMinSaleQty($item)
    {
        $productId = $item->getProductId();
        if ($item->getProductType() == 'configurable' && $this->getChildProduct($item) != null) {
           $productId = $this->getChildProduct($item);
        }
        $webid = $this->_storeManager->getStore()->getWebsiteId();
        $stockItem = $this->stockRegistry->getStockItem($productId, $webid);
        return $stockItem->getMinSaleQty()? $stockItem->getMinSaleQty() : 1;
    }

    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }


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

    function getMediaBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function truncateString($value, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        return $this->filterManager->truncate(
            $value,
            ['length' => $length, 'etc' => $etc, 'remainder' => $remainder, 'breakWords' => $breakWords]
        );
    }
    
    public function getFormattedOption($value)
    {
        $remainder = '';
        $value = $this->truncateString($value, 55, '', $remainder);
        $result = ['value' => nl2br($value), 'remainder' => nl2br($remainder)];

        return $result;
    }

    // get options
    public function getOrderOptions($item)
    {
        $result = [];
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (!empty($options['attributes_info'])) {
                $result = array_merge($options['attributes_info'], $result);
            }
        }
        return $result;
    }
    // end

    public function getAvailableOrders()
    {
        $sort = [
                    'name'=>'2',
                    'price'=>'3',
                    'qty_ordered'=>'5',
                    'created_at'=>'6',
                    'stock_status'=>'7'
                    ];
        return $sort;
    }

    public function getOrderDefault()
    {
        $sortby = $this->getAvailableOrders();
        return $sortby[$this->helper->getSortby()];
    }

    public function getItems()
    {
        $_orders = $this->getOrders();
        $_orderids = [];
        foreach ($_orders as $_order) {
            $_orderids[] = $_order->getId();
        }

        $collection  = $this->orderItem->create()->getCollection();
        $collection->filterOrderIds($_orderids);
        return $collection;
    }
    
    public function getListperpagevalue()
    {
        $item_per_page = [];
        $item_per_page = array_combine(explode(',', $this->helper->getListperpagevalue()), explode(',', $this->helper->getListperpagevalue()));
        if ($this->helper->showAlllist()) {
            $item_per_page['-1'] = 'All';
        }
        return $item_per_page;
    }

    public function getListperpage()
    {
        return $this->helper->getListperpage();
    }

    public function getProductId($item)
    {
        $productId = $item->getProductId();
        $itemOptions = unserialize($item->getReorderItemOptions());
        if ($item->getProductType() == 'configurable' && isset($itemOptions['product'])) {
            $productId = $itemOptions['product'];
        }
        if ($item->getProductType() == 'grouped' && isset($itemOptions['super_product_config']['product_id'])) {
            $productId = $itemOptions['super_product_config']['product_id'];
        }
        return $productId;
    }

    public function getChildProduct($item)
    {
        $productId = null;
        $collection  = $this->orderItem->create()->getCollection();
        $collection->getChildProduct($item->getId());
        $collection->addAttributeToSelect('product_id');
        if ($collection->getSize() > 0) {
            foreach ($collection as $item) {
                $productId = $item->getProductId();
                break;
            }
        }
        return $productId;
    }

}
