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
namespace Aheadworks\CustomerAttributes\Test\Unit\ViewModel;

use Aheadworks\CustomerAttributes\Model\Attribute\RelationLoader;
use Aheadworks\CustomerAttributes\ViewModel\Relation;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class RelationTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\ViewModel
 */
class RelationTest extends TestCase
{
    /**
     * @var Relation
     */
    private $viewModel;

    /**
     * @var RelationLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $relationLoaderMock;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->relationLoaderMock = $this->createMock(RelationLoader::class);
        $this->viewModel = $objectManager->getObject(
            Relation::class,
            ['relationLoader' => $this->relationLoaderMock]
        );
    }

    /**
     * Test getRelationsData method
     */
    public function testGetRelationData()
    {
        $relationsData = [
            'attribute1' => [
                'option_id_1' => [
                    'attribute2',
                    'attribute3'
                ],
                'option_id_2' => [
                    'attribute4',
                    'attribute5'
                ]
            ]
        ];
        $encodedRelationsData = json_encode($relationsData);

        $this->relationLoaderMock->expects($this->once())
            ->method('getRelationsData')
            ->willReturn($relationsData);

        $this->assertEquals($encodedRelationsData, $this->viewModel->getRelationsData());
    }
}
