<?php

namespace Icube\CatalogRestrictions\Helper;

use Icube\CatalogRestrictions\Api\Data\SellerConfigInterface;
use Icube\CatalogRestrictions\Api\Data\SellerConfigInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_TABLE_NAME = 'icube_sellerconfig';

    protected ResourceConnection $resource;
    protected LoggerInterface $logger;
    protected EavConfig $eavConfig;
    protected EntityMetadataInterface $metadata;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        EavConfig $eavConfig,
        MetadataPool $metadataPool,
        ResourceConnection $resource,
        SellerConfigInterfaceFactory $sellerConfigFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->eavConfig = $eavConfig;
        $this->sellerConfigFactory = $sellerConfigFactory;
        $this->metadata  = $metadataPool->getMetadata(CustomerInterface::class);
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Get seller id list by jwt token
     *
     * @param string $token
     * @return array
     */
    public function getAvailableSellerByToken(string $token): array
    {
        try {
            $availableIds = [];
            $customerId = $this->getCustomerId($token);
            $join = sprintf(
                "SELECT ce.entity_id AS id,cae.postcode,cg.customer_group_code AS group_code FROM %s AS ce
                LEFT JOIN %s AS cae ON ce.default_shipping = cae.entity_id 
                LEFT JOIN %s AS cg ON ce.group_id = cg.customer_group_id
                WHERE ce.entity_id = ?",
                $this->resource->getTableName('customer_entity'),
                $this->resource->getTableName('customer_address_entity'),
                $this->resource->getTableName('customer_group')
            );
            // using default shipping address to get customer zip code
            $customerData = $this->resource->getConnection()->fetchRow($join, $customerId);

            if (!$customerData['postcode']) {
                throw new \Magento\Framework\Exception\LocalizedException(__("Customer hasn't set the default address"));
            }

            $attributes = $this->scopeConfig->getValue(
                'icube_catalog_restrictions/general/attributes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $attributes = explode(',', $attributes);
            $customerGroup = strtolower($customerData['group_code']);
            $conditions = sprintf("(`type` = '%s' AND LOWER(`value`) = '%s')", SellerConfigInterface::GROUP_KEY, $customerGroup);
            $count = 1;
            foreach ($attributes as $attributeCode) {
                if ($attributeCode == SellerConfigInterface::GROUP_KEY) {
                    continue;
                }
                if ($attributeCode == SellerConfigInterface::ZIPCODE_KEY) {
                    $conditions .= sprintf(" OR (`type` = '%s' AND `value` = '%s')", SellerConfigInterface::ZIPCODE_KEY, $customerData['postcode']);
                } else {
                    $attributeValue = $this->getCustomerAttributeValue($customerId, $attributeCode);
                    if (is_array($attributeValue)) {
                        foreach ($attributeValue as $value) {
                            $value = strtolower($value);
                            $conditions .= sprintf(" OR (`type` = '%s' AND LOWER(`value`)  = '%s')", $attributeCode, $value);
                        }
                    } elseif ($attributeValue) {
                        $value = strtolower($attributeValue);
                        $conditions .= sprintf(" OR (`type` = '%s' AND LOWER(`value`)  = '%s')", $attributeCode, $value);
                    }
                }
                $count++;
            }

            $conditions .= sprintf(" GROUP BY seller_id HAVING COUNT(CASE WHEN `type`='%s' THEN 1 END) > 0", SellerConfigInterface::GROUP_KEY);
            foreach ($attributes as $attribute) {
                $conditions .= sprintf(" AND COUNT(CASE WHEN `type`= '%s' THEN 1 END) > 0", $attribute);
            }

            $query = sprintf(
                "SELECT seller_id FROM %s
                WHERE %s
                AND COUNT(DISTINCT `type`) >= ?",
                $this->resource->getTableName(self::CONFIG_TABLE_NAME),
                $conditions
            );
            $sellerIds = $this->resource->getConnection()->fetchCol($query, $count);

            $whitelistIds = $this->getSellerIdsByType('is_whitelist');
            $blacklistIds = $this->getSellerIdsByType('is_blacklist');

            $whitelisted = array_merge($sellerIds, $whitelistIds);
            $availableIds = array_diff($whitelisted, $blacklistIds);

            $availableIds = array_unique($availableIds);
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage(), ['location' => __METHOD__, 'scope' => 'Get available seller list']);
        } finally {
            return $availableIds;
        }
    }

    public function getAvailableSellerByCustomerId(string $customerId): array
    {
        try {
            $availableIds = [];
            // $customerId = $this->getCustomerId($customerId);
            $join = sprintf(
                "SELECT ce.entity_id AS id,cae.postcode,cg.customer_group_code AS group_code FROM %s AS ce
                LEFT JOIN %s AS cae ON ce.default_shipping = cae.entity_id 
                LEFT JOIN %s AS cg ON ce.group_id = cg.customer_group_id
                WHERE ce.entity_id = ?",
                $this->resource->getTableName('customer_entity'),
                $this->resource->getTableName('customer_address_entity'),
                $this->resource->getTableName('customer_group')
            );
            // using default shipping address to get customer zip code
            $customerData = $this->resource->getConnection()->fetchRow($join, $customerId);

            if (!$customerData['postcode']) {
                throw new \Magento\Framework\Exception\LocalizedException(__("Customer hasn't set the default address"));
            }

            $attributes = $this->scopeConfig->getValue(
                'icube_catalog_restrictions/general/attributes',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $attributes = explode(',', $attributes);
            $customerGroup = strtolower($customerData['group_code']);
            $conditions = sprintf("(`type` = '%s' AND LOWER(`value`) = '%s')", SellerConfigInterface::GROUP_KEY, $customerGroup);
            $count = 1;
            foreach ($attributes as $attributeCode) {
                if ($attributeCode == SellerConfigInterface::GROUP_KEY) {
                    continue;
                }
                if ($attributeCode == SellerConfigInterface::ZIPCODE_KEY) {
                    $conditions .= sprintf(" OR (`type` = '%s' AND `value` = '%s')", SellerConfigInterface::ZIPCODE_KEY, $customerData['postcode']);
                } else {
                    $attributeValue = $this->getCustomerAttributeValue($customerId, $attributeCode);
                    if (is_array($attributeValue)) {
                        foreach ($attributeValue as $value) {
                            $value = strtolower($value);
                            $conditions .= sprintf(" OR (`type` = '%s' AND LOWER(`value`)  = '%s')", $attributeCode, $value);
                        }
                    } elseif ($attributeValue) {
                        $value = strtolower($attributeValue);
                        $conditions .= sprintf(" OR (`type` = '%s' AND LOWER(`value`)  = '%s')", $attributeCode, $value);
                    }
                }
                $count++;
            }

            $conditions .= sprintf(" GROUP BY seller_id HAVING COUNT(CASE WHEN `type`='%s' THEN 1 END) > 0", SellerConfigInterface::GROUP_KEY);
            foreach ($attributes as $attribute) {
                $conditions .= sprintf(" AND COUNT(CASE WHEN `type`= '%s' THEN 1 END) > 0", $attribute);
            }

            $query = sprintf(
                "SELECT seller_id FROM %s
                WHERE %s
                AND COUNT(DISTINCT `type`) >= ?",
                $this->resource->getTableName(self::CONFIG_TABLE_NAME),
                $conditions
            );
            $sellerIds = $this->resource->getConnection()->fetchCol($query, $count);

            $whitelistIds = $this->getSellerIdsByType('is_whitelist');
            $blacklistIds = $this->getSellerIdsByType('is_blacklist');

            $whitelisted = array_merge($sellerIds, $whitelistIds);
            $availableIds = array_diff($whitelisted, $blacklistIds);

            $availableIds = array_unique($availableIds);
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage(), ['location' => __METHOD__, 'scope' => 'Get available seller list']);
        } finally {
            return $availableIds;
        }
    }

    /**
     * Get customer id from token
     *
     * @param string $token
     * @return int
     */
    public function getCustomerId(string $token): int
    {
        $customerId = 0;
        if (strlen($token) > 32) {
            // jwt token
            $tokenParts = explode(".", $token);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $tokenPayload = base64_decode($tokenParts[1]);
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            $jwtPayload = json_decode($tokenPayload);
            // @see Magento\JwtUserToken\Model\Issuer
            $customerId = (int)$jwtPayload->uid;
        }
        return $customerId;
    }

    /**
     * Get seller_id by type and value
     *
     * @param string $type
     * @param string $value
     * @return array
     */
    public function getSellerIdsByType($type, $value = '1')
    {
        $rawQuery = sprintf(
            "SELECT seller_id FROM %s
            WHERE (`type` = '%s' AND `value` = '%s')",
            $this->resource->getTableName(self::CONFIG_TABLE_NAME),
            $type,
            $value
        );
        return $this->resource->getConnection()->fetchCol($rawQuery);
    }

    /**
     * Get customer custom attribute value
     *
     * @param int $customerId
     * @param string $attributeCode
     * @return string|array
     */
    public function getCustomerAttributeValue($customerId, $attributeCode)
    {
        $attribute  = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
        $connection = $this->resource->getConnection();

        $select  = sprintf('SELECT %%columns%% FROM %s AS e', $this->resource->getTableName('customer_entity'));
        $columns = [];
        $bind = [
            ':id' => $customerId,
        ];

        if ($attribute->isStatic()) {
            $columns[] = 'e.' . $attribute->getAttributeCode();
        } else {
            $linkField = $this->metadata->getLinkField();
            $select .= sprintf(
                ' INNER JOIN %s AS vt ON e.%s = vt.entity_id AND vt.attribute_id = :attribute_id',
                $attribute->getBackendTable(),
                $linkField
            );
            $bind[':attribute_id'] = (int)$attribute->getAttributeId();
            $columns[] = 'vt.value';
        }
        $select .= ' WHERE e.entity_id = :id';
        $select = strtr($select, ['%columns%' => implode(', ', $columns)]);
        $attributeValue = $connection->fetchOne($select, $bind);
        if ($attribute->getFrontendInput() == 'multiselect' && $attributeValue) {
            $selectOptionValue = sprintf(
                "SELECT value FROM %s WHERE option_id IN (%s)",
                $connection->getTableName('eav_attribute_option_value'),
                $attributeValue
            );
            $attributeValue = $connection->fetchCol($selectOptionValue);
        }
        return $attributeValue;
    }
}
