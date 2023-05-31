<?php
namespace Icube\CustomCustomer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;

class UpdateKycAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var CustomerSetup
     */
    private $customerSetupFactory;

    /**
     * @var SetFactory
     */
    private $attributeSetFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        SetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            AttributeProvider::ENTITY,
            'road_access',
            [
                'frontend_input' => 'select',
                'source_model' => Table::class
            ]
        );

        $eavSetup->updateAttribute(
            AttributeProvider::ENTITY,
            'front_store_photo',
            [
                'frontend_input' => 'image'
            ]
        );

        $eavSetup->updateAttribute(
            AttributeProvider::ENTITY,
            'near_store_photo',
            [
                'frontend_input' => 'image'
            ]
        );

        $eavSetup->updateAttribute(
            Customer::ENTITY,
            'path_selfie_ktp',
            [
                'frontend_input' => 'image',
                'frontend_label' => 'KTP Selfie'
            ]
        );

        $eavSetup->updateAttribute(
            Customer::ENTITY,
            'path_image_ktp',
            [
                'frontend_input' => 'image',
                'frontend_label' => 'KTP Image'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
