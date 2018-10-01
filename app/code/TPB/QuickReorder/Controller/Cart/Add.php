<?php
namespace TPB\QuickReorder\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Escaper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

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
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ProductFactory $productFactory
     * @param CustomerCart $cart
     * @param Escaper $escaper
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        ProductFactory $productFactory,
        CustomerCart $cart,
        Escaper $escaper,
        Json $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productFactory = $productFactory;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Add product to shopping cart action
     * 
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $addedProducts = $result = [];
        $result['status'] = '';
        $items = $this->serializer->unserialize($params['item']);
        if (count($items)) {
            foreach ($items as $item) {
                try {
                    $itemId = $item['id'];
                    $parentId = $item['parentId'];
                    $qty = $item['qty'];
                    if ($qty <= 0) {
                        continue;
                    }
                    $this->addOrderItem($itemId, $parentId, $qty);

                    $addedProducts[] = $itemId;
                } catch (LocalizedException $e) {
                    if ($this->checkoutSession->getUseNotice(true)) {
                        $this->messageManager->addNoticeMessage($e->getMessage());
                    } else {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }
                    $result['status'] = 'ERROR';
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.'));
                    $result['status'] = 'ERROR';
                }
            }
            $result['type'] = 'cart';
            $result['status'] = $this->saveCart($result['status'], $addedProducts);
        }
        $this->getResponse()->setBody($this->serializer->serialize($result));
    }

    /**
     * To save products into cart
     *
     * @param null|array $addedProducts
     * @param string $result
     * @return string
     */
    private function saveCart($result, $addedProducts = null)
    {
        if ($addedProducts) {
            try {
                $this->cart->save()->getQuote()->collectTotals();
                if (!$this->cart->getQuote()->getHasError()) {
                    $this->messageManager->addSuccessMessage(
                        __('%1 product(s) have been added to shopping cart', count($addedProducts))
                    );
                    $result = 'SUCCESS';
                }
            } catch (LocalizedException $e) {
                if ($this->checkoutSession->getUseNotice(true)) {
                    $this->messageManager->addNoticeMessage(
                        $this->escaper->escapeHtml($e->getMessage())
                    );
                } else {
                    $errormessage = array_unique(explode("\n", $e->getMessage()));
                    $errormessageCart = end($errormessage);
                    $this->messageManager->addErrorMessage(
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
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.'));
        }
        return $this;
    }
}
