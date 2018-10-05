<?php
namespace TPB\QuickReorder\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;

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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataObject
     */
    private $dataObject;

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
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param StoreManagerInterface $storeManager
     * @param DataObject $dataObject
     * @param Escaper $escaper
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        StoreManagerInterface $storeManager,
        DataObject $dataObject,
        Escaper $escaper,
        Json $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->storeManager = $storeManager;
        $this->dataObject = $dataObject;
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
            $store = $this->storeManager->getStore();
            $quoteId = $this->checkoutSession->getQuoteId();
            $cartId = isset($quoteId) ? $quoteId : $this->cartManagement->createEmptyCart();
            $this->quote = $this->cartRepository->get($cartId);
            $this->quote->setStore($store);
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $this->quote->assignCustomer($customer);
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
                    $this->messageManager->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.')->render());
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
        if ($addedProducts && $result != 'ERROR') {
            try {
                $this->quote->collectTotals()->save();
                if (!$this->quote->getHasError()) {
                    $this->messageManager->addSuccessMessage(
                        __('%1 product(s) have been added to shopping cart', count($addedProducts))->render()
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
        $_product = $this->productRepository->getById($productId);
        if ($parentId) {
            $_parentProduct = $this->productRepository->getById($parentId);
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
                $params = $this->dataObject->setData($cartParams);
                $this->quote->addProduct($_parentProduct, $params);
            }
        } else {
            $params = $this->dataObject->setData($cartParams);
            $this->quote->addProduct($_product, $params);
        }
        return $this;
    }
}
