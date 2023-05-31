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
namespace Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Class FrontendLabels
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class FrontendLabels implements ModifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        if ($labels = $data[AttributeInterface::FRONTEND_LABELS]) {
            $resultLabels = [];
            /** @var AttributeFrontendLabelInterface $label */
            foreach ($labels as $label) {
                $resultLabels[] = [
                    AttributeFrontendLabelInterface::STORE_ID => $label->getStoreId(),
                    AttributeFrontendLabelInterface::LABEL => $label->getLabel()
                ];
            }
            $data[AttributeInterface::FRONTEND_LABELS] = $resultLabels;
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
