<?php
namespace Icube\CustomCustomer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class AddOtherBusinessType implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
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

        $customAdditionalCustomerAttributes = [
            'other_business_type' => 'Other Business Type'
        ];
        $position = 1003;
        
        foreach ($customAdditionalCustomerAttributes as $attrCode => $attrLabel) {
            $customerSetup->addAttribute(Customer::ENTITY, $attrCode, [
                'type' => 'varchar',
                'label' => $attrLabel,
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'position' => $position,
                'system' => false,
                'user_defined' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attrCode);
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

    public function revert()
    {
        $customAdditionalCustomerAttributes = [
            'other_business_type' => 'Other Business Type'
        ];
        $this->moduleDataSetup->getConnection()->startSetup();
        foreach ($customAdditionalCustomerAttributes as $attrCode => $attrLabel) {
            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $customerSetup->removeAttribute(Customer::ENTITY, $attrCode);
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
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.3';
    }
}
