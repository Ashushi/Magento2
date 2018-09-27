<?php
namespace TPB\QuickReorder\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Escaper;
use Magento\Framework\App\Action\Action;

/**
 * Used to add products into cart
 */
class Add extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    
    /**
     * @var CustomerCart
     */
    private $cart;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ProductFactory $ProductFactory
     * @param CustomerCart $cart
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        ProductFactory $productFactory,
        CustomerCart $cart,
        Escaper $escaper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productFactory = $productFactory;
        $this->cart = $cart;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * Add product to shopping cart action
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();

        $addedProducts = $result = [];
        $result['status'] = '';

        foreach (json_decode($params['item']) as $item) {
            try {
                $itemId = $item->id;
                $parentId = $item->parentId;
                $qty = $item->qty;
                if ($qty <= 0) {
                    continue;
                }
                $this->addOrderItem($itemId, $parentId, $qty);

                $addedProducts[] = $itemId;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->checkoutSession->getUseNotice(true)) {
                    $this->messageManager->addNotice($e->getMessage());
                } else {
                    $this->messageManager->addError($e->getMessage());
                }
                $result['status'] = 'ERROR';
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
                $result['status'] = 'ERROR';
            }
        }

        $result['type'] = 'cart';
        $result['status'] = $this->saveCart($addedProducts, $result['status']);
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * To save products into cart
     *
     * @param null|array $addedProducts
     * @param string $result
     * @return string
     */
    private function saveCart($addedProducts = null, $result)
    {
        if ($addedProducts) {
            try {
                $this->cart->save()->getQuote()->collectTotals();
                if (!$this->cart->getQuote()->getHasError()) {
                    $this->messageManager->addSuccess(
                        __('%1 product(s) have been added to shopping cart', count($addedProducts))
                    );
                    $result = 'SUCCESS';
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->checkoutSession->getUseNotice(true)) {
                    $this->messageManager->addNotice(
                        $this->escaper->escapeHtml($e->getMessage())
                    );
                } else {
                    $errormessage = array_unique(explode("\n", $e->getMessage()));
                    $errormessageCart = end($errormessage);
                    $this->messageManager->addError(
                        $this->escaper->escapeHtml($errormessageCart)
                    );
                }
                $result = 'ERROR';
            }
        }
        return $result;
    }

    /**
     * To add product into cart
     *
     * @param int $productId
     * @param int $parentId
     * @param int $qty
     * @return this
     */
    private function addOrderItem($productId, $parentId, $qty)
    {
        $cartParams = array();
        $cartParams['qty'] = $qty;
        try {
            $_product = $this->productFactory->create()->load($productId);
            if ($parentId) {
                $_parentProduct = $this->productFactory->create()->load($parentId);
                if ($_parentProduct->getTypeId() === 'configurable') {
                    $productAttributes = $_parentProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($_parentProduct);
                    $superAttributeArr = array();
                    foreach ($productAttributes as $val) {
                        $att_code = $val['attribute_code'];
                        $att_id = $val['attribute_id'];
                        $option_val = $_product->getData($att_code);
                        $superAttributeArr[$att_id] = $option_val;
                    }
                    $cartParams['product_id'] = $parentId;
                    $cartParams['super_attribute'] = $superAttributeArr;
                    $this->cart->addProduct($_parentProduct, $cartParams);
                }
            } else {
                $this->cart->addProduct($_product, $cartParams);
            }
        } catch(\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
        }
        return $this;
    }
}
