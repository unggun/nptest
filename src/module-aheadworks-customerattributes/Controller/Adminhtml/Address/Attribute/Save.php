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

use Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\Save as AttributeSave;

/**
 * Class Save
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Address\Attribute
 */
class Save extends AttributeSave
{
    /**
     * {@inheritdoc}
     */
    const ADMIN_RESOURCE = 'Aheadworks_CustomerAttributes::address_attributes';
}
