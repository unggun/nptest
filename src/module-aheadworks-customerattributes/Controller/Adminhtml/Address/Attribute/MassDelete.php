<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Controller\Adminhtml\Address\Attribute;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class MassDelete
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Address\Attribute
 */
class MassDelete extends AbstractMassAction
{
    /**
     * {@inheritdoc}
     */
    protected function massAction(Collection $collection)
    {
        $deletedRecords = 0;
        foreach ($collection->getAllIds() as $attributeId) {
            try {
                $this->attributeRepository->deleteById($attributeId);
                $deletedRecords++;
            } catch (LocalizedException $e) {
                 continue;
            }
        }

        if ($deletedRecords) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 attribute(s) have been deleted.', $deletedRecords)
            );
        } else {
            $this->messageManager->addSuccessMessage(__('No attribute have been deleted.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }
}
