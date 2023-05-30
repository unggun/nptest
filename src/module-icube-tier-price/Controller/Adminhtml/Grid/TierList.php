<?php

namespace Icube\TierPrice\Controller\Adminhtml\Grid;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class TierList extends Action
{
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Icube_TierPrice::tierprice_list');
        $resultPage->getConfig()->getTitle()->prepend((__('Tier Price List')));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return true;
    }
}
