<?php

namespace Icube\OtpFazpass\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('icube_sms_otp'),
            'otp_id_fazpass',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'id otp fazpass for hit api verify otp'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('icube_sms_otp'),
            'otp_type',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'otp type sms, email or wa'
            ]
        );

        $setup->endSetup();
	}
}
