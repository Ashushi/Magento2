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
namespace Bss\ReorderProduct\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;

class Add extends \Magento\Framework\App\Action\Action
{

    protected $checkoutSession;

    protected $storeManager;

    protected $formKeyValidator;

    protected $orderCollectionFactory;

    protected $customerSession;

    protected $orders;

    protected $cart;
    
    protected $productRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderConfig = $orderConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->formKeyValidator = $formKeyValidator;
        $this->cart = $cart;
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
            $item_ids[] = $params['item'];
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
        //     return $this->resultRedirectFactory->create()->setPath('*/*/');
        // }

        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getDataParams();
        
        $addedProducts = $result = [];

        $item_ids = $params['item_ids'];

        $orders = $this->getOrders();
        $result['status'] = '';
        foreach ($orders as $order) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                $itemId = $item->getId();
                if (in_array($itemId, $item_ids)) {
                    try {
                        if (isset($params['type']) && $params['type'] == 'addmultiple') {
                            $qty = $params['qty_'.$itemId];
                        } else {
                            $qty = $params['qty'];
                        }
                        if ($qty <= 0) {
                            continue;
                        }
                        $this->addOrderItem($item, $qty);

                        $addedProducts[] = $item->getProduct();
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        if ($this->checkoutSession->getUseNotice(true)) {
                            $this->messageManager->addNotice($e->getMessage());
                        } else {
                            $this->messageManager->addError($e->getMessage());
                        }

                        $cartItem = $this->cart->getQuote()->getItemByProduct($item->getProduct());
                        if ($cartItem) {
                            $this->cart->getQuote()->deleteItem($cartItem);
                        }
                        $result['status'] = 'ERROR';
                    } catch (\Exception $e) {
                        $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
                        $result['status'] = 'ERROR';
                    }
                }
            }
        }

        $result['type'] = 'cart';
        $result['status'] = $this->saveCart($addedProducts, $result['status']);
        $this->getResponse()->setBody(json_encode($result));
        return;
    }

    private function saveCart($addedProducts = null, $result)
    {
        if ($addedProducts) {
            try {
                $this->cart->save()->getQuote()->collectTotals();
                if (!$this->cart->getQuote()->getHasError()) {
                    $products = [];
                    foreach ($addedProducts as $product) {
                        $products[] = '"' . $product->getName() . '"';
                    }
                    $this->messageManager->addSuccess(
                        __('%1 product(s) have been added to shopping cart: %2.', count($addedProducts), join(', ', $products))
                    );
                    $result = 'SUCCESS';
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->checkoutSession->getUseNotice(true)) {
                    $this->messageManager->addNotice(
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
                    );
                } else {
                    $errormessage = array_unique(explode("\n", $e->getMessage()));
                    $errormessageCart = end($errormessage);
                    $this->messageManager->addError(
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Escaper')->escapeHtml($errormessageCart)
                    );
                }
                $result = 'ERROR';
            }
        }
        return $result;
    }

    private function addOrderItem($orderItem, $qty)
    {

        if ($orderItem->getParentItem() === null) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($orderItem->getProductId(), false, $storeId, true);
            } catch (NoSuchEntityException $e) {
                return $this;
            }

            $info = $orderItem->getProductOptionByCode('info_buyRequest');
            $info = new \Magento\Framework\DataObject($info);
            $info->setQty($qty);

            $this->cart->addProduct($product, $info);
        }
        return $this;
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
