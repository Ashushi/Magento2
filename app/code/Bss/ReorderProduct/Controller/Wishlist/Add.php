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
namespace Bss\ReorderProduct\Controller\Wishlist;

use Magento\Wishlist\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;

class Add extends \Magento\Framework\App\Action\Action
{
 
    protected $wishlistProvider;

    protected $customerSession;

    protected $productRepository;

    protected $orderCollectionFactory;

    protected $orderConfig;
   
    protected $formKeyValidator;

    protected $orders;

    public function __construct(
        Action\Context $context,
        Data $wishlistData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        Validator $formKeyValidator
    ) {
        $this->wishlistData = $wishlistData;
        $this->customerSession = $customerSession;
        $this->wishlistProvider = $wishlistProvider;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderConfig = $orderConfig;
        $this->productRepository = $productRepository;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    private function getDataParams()
    {
        $params = $this->getRequest()->getParams();
        $item_ids = [];
        if (isset($params['type']) && $params['type'] == 'addmultiple') {
            foreach (json_decode($params['item']) as $item) {
                $item_ids[] = $item->id;
                $params['qty_'.$item->id] = $item->qty;
            }
        } else {
            $item_ids = $params['item'];
        }

        if (($key = array_search('reorder-select-all', $item_ids)) !== false) {
            unset($item_ids[$key]);
        }
        $params['item_ids'] = $item_ids;
        return $params;
    }

    public function execute()
    {
        // if (!$this->formKeyValidator->validate($this->getRequest())) {
        //      return $this->resultRedirectFactory->create()->setPath('*/*/');
        // }
        $params = $this->getDataParams();
        $wishlist = $this->wishlistProvider->getWishlist();
        $session = $this->customerSession;
               
        $addedProducts = [];
        $error = [];
        $item_ids = $params['item_ids'];
        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                $itemId = $item->getId();
                if (in_array($itemId, $item_ids)) {
                    try {
                        $productId = $item->getProductId();
                        $buyRequest = $item->getBuyRequest();
                        // set qty
                        $qty = $params['qty_'.$itemId];

                        if ($qty < 0) {
                            continue;
                        }
                        $buyRequest->setQty($qty);

                        $wishlist->addNewItem($productId, $buyRequest);

                        $referer = $session->getBeforeWishlistUrl();
                        if ($referer) {
                            $session->setBeforeWishlistUrl(null);
                        } else {
                            $referer = $this->_redirect->getRefererUrl();
                        }

                        $product = $this->productRepository->getById($productId);

                        $addedProducts[] = $product;
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $this->messageManager->addErrorMessage(
                            __('We can\'t add the item to Wish List right now: %1.', $e->getMessage())
                        );
                        $error[] = $e->getMessage();
                        $result['status'] = 'ERROR';
                    } catch (\Exception $e) {
                        $this->messageManager->addExceptionMessage(
                            $e,
                            __('We can\'t add the item to Wish List right now.')
                        );
                        $error[] = $e->getMessage();
                        $result['status'] = 'ERROR';
                    }
                }
            }
        }

        if ($addedProducts) {
            $products = [];
            foreach ($addedProducts as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            $this->messageManager->addSuccess(
                __('%1 product(s) have been added to your Wish List: %2.', count($addedProducts), join(', ', $products))
            );
            $this->wishlistData->calculate();
            $result['status'] = 'SUCCESS';
        }
        $result['type'] = 'wishlist';
        $this->getResponse()->setBody(json_encode($result));
        return;
    }

    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }

    private function getOrders()
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
}
