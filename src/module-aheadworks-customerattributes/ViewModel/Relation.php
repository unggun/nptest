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
namespace Aheadworks\CustomerAttributes\ViewModel;

use Aheadworks\CustomerAttributes\Model\Attribute\RelationLoader;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Relation
 * @package Aheadworks\CustomerAttributes\ViewModel
 */
class Relation implements ArgumentInterface
{
    /**
     * @var RelationLoader
     */
    private $relationLoader;

    /**
     * @param RelationLoader $relationLoader
     */
    public function __construct(
        RelationLoader $relationLoader
    ) {
        $this->relationLoader = $relationLoader;
    }

    /**
     * Retrieve relations data
     *
     * @return string
     */
    public function getRelationsData()
    {
        return json_encode($this->relationLoader->getRelationsData());
    }
}
