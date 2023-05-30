<?php
declare(strict_types=1);

namespace Icube\CustomCustomer\Setup\Patch\Data;

use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class MoveCustomAddressAttributes implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
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
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        SetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        
        /** @var $attributeSet Set */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customCustomerAddressAttributes = [
            'warung_category' => 'Warung Category',
            'ready_for_order' => 'Ready for Order',
            'warung_name' => 'Warung Name',
        ];

        $this->removeFromAddressAttribute($customCustomerAddressAttributes);
        $position = 1121;
        
        foreach ($customCustomerAddressAttributes as $attrCode => $attrLabel) {
            $customerSetup->addAttribute(Customer::ENTITY, $attrCode, [
                'type' => 'varchar',
                'label' => $attrLabel,
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'position' => $position++,
                'system' => false,
                'user_defined' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
                'backend' => ''
            ]);

            $attribute = $customerSetup->getEavConfig()->clear()->getAttribute(Customer::ENTITY, $attrCode);
            $attribute->addData([
                'used_in_forms' => [
                    'adminhtml_customer',
                    'customer_account_create',
                    'customer_account_edit'
                ]
            ]);
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            ]);
            $attribute->save();
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function removeFromAddressAttribute(array $customCustomerAddressAttributes)
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        foreach ($customCustomerAddressAttributes as $attrCode => $attrLabel) {
            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $attributeExists = $customerSetup->getEavConfig()->clear()->getAttribute(AttributeProvider::ENTITY, $attrCode);
            if (!$attributeExists->getAttributeId()) {
                continue;
            }
            $customerSetup->removeAttribute(AttributeProvider::ENTITY, $attrCode);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $customAdditionalCustomerAttributes = [
            'warung_category' => 'Warung Category',
            'ready_for_order' => 'Ready for Order',
            'warung_name' => 'Warung Name',
        ];
        $this->moduleDataSetup->getConnection()->startSetup();
        foreach ($customAdditionalCustomerAttributes as $attrCode => $attrLabel) {
            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, $attrCode);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            \Icube\CustomCustomer\Setup\Patch\Data\AddWarpinCustomerAddressAttributes::class
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.3';
    }
}
