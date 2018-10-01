<?php
namespace TPB\QuickReorder\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;

/**
 * Controller For search and pagination
 */
class Search extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Renders Quick Reorder search and pagination
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()
                ->createBlock('TPB\QuickReorder\Block\QuickReorder')
                ->setTemplate('TPB_QuickReorder::search.phtml')
                ->toHtml();
        $this->getResponse()->setBody($block);
    }
}
