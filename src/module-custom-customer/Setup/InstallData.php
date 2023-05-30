<?php

namespace Icube\CustomCustomer\Setup;


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
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
          $eav = $objectManager->get('\Magento\Eav\Model\Config');
          /** @var CustomerSetup $customerSetup */
          $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
          $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
          $attributeSetId = $customerEntity->getDefaultAttributeSetId();
          /** @var $attributeSet AttributeSet */
          $attributeSet = $this->attributeSetFactory->create();
          $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

          $setup->getConnection()->addColumn(
              $setup->getTable('customer_entity'),'business_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Business type'
                ]
            );
          $customerSetup->addAttribute(Customer::ENTITY, 'business_type', [
            'type' => 'static',
            'label' => 'Business Type',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'sort_order' => 1000,
            'position' => 1000,
            'system' => 0
          ]);
          //add attribute to attribute set
          $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'business_type')
              ->addData([
                  'attribute_set_id' => $attributeSetId,
                  'attribute_group_id' => $attributeGroupId,
                  'used_in_forms' => ['adminhtml_customer','customer_account_create','customer_account_edit'],
              ]);
          $attribute->save();
      }
  }