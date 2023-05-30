<?php

namespace Icube\GlobalMessage\Setup;


  use Magento\Customer\Setup\CustomerSetupFactory;
  use Magento\Customer\Model\Customer;
  use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
  use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
  use Magento\Framework\Setup\InstallDataInterface;
  use Magento\Framework\Setup\ModuleContextInterface;
  use Magento\Framework\Setup\ModuleDataSetupInterface;
  class InstallData implements InstallDataInterface
  {
      /**
       * CustomerSetupFactory
       * @var CustomerSetupFactory
       */
      protected $customerSetupFactory;
      /**
       * $attributeSetFactory
       * @var AttributeSetFactory
       */
      private $attributeSetFactory;
      /**
       * initiate object
       * @param CustomerSetupFactory $customerSetupFactory
       * @param AttributeSetFactory $attributeSetFactory
       */
      public function __construct(
          CustomerSetupFactory $customerSetupFactory,
          AttributeSetFactory $attributeSetFactory
      )
      {
          $this->customerSetupFactory = $customerSetupFactory;
          $this->attributeSetFactory = $attributeSetFactory;
      }
      /**
       * install data method
       * @param ModuleDataSetupInterface $setup
       * @param ModuleContextInterface $context
       */
      public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
      {
          $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
          $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
          $attributeSetId = $customerEntity->getDefaultAttributeSetId();

          /** @var $attributeSet AttributeSet */
          $attributeSet = $this->attributeSetFactory->create();
          $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

          $customerSetup->addAttribute(Customer::ENTITY, 'verification_status', [
              'type' => 'text',
              'label' => 'Verification Status',
              'input' => 'select',
              'required' => false,
              'visible' => true,
              'source' => 'Icube\GlobalMessage\Model\Config\Source\Options',
              'user_defined' => true,
              'sort_order' => 1000,
              'position' => 1000,
              'system' => 0,
          ]);

          $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'verification_status')
              ->addData([
                  'attribute_set_id' => $attributeSetId,
                  'attribute_group_id' => $attributeGroupId,
                  'used_in_forms' => ['adminhtml_customer'],
              ]);
          $attribute->save();
      }
  }
