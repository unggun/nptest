/* eslint-disable */

define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Magento_Customer/js/customer-data'
], function ($, mageurl, $t, customerdata) {
    'use strict';

    return function (config, element) {
        $.ajax(
            {
                url: mageurl.build('custom_tierprice/ajax/tierprice?sku=' + config.sku),
                type: 'GET',
                success: function(response) {
                    if (response.group_code !== undefined) {
                        response.tier_prices.forEach((res, idx) => {
                            var content = '';
                            var getAmount = (res, idx) => {
                                return '<div id="price-block-default-'+idx+'" class="custom-price" style=""><span class="price-container "><span id="'+idx+'" data-price-amount="'+res.website_price+'" data-price-type="" class="price-wrapper ">'+res.amount_render+'</span></span></div>';
                            };
                            if (res.all_groups == '0' && res.price_value_type == 'fixed') {
                                content = $t('For customer group %1 Buy %2 Discount %3')
                                    .replace('%1', response.group_code)
                                    .replace('%2', res.price_qty)
                                    .replace('%3', getAmount(res, idx));
                            } else if (res.all_groups == '0' && res.price_value_type == 'percent') {
                                content = $t('For customer group %1 Buy %2 Discount %3%')
                                    .replace('%1', response.group_code)
                                    .replace('%2', res.price_qty)
                                    .replace('%3', res.website_price);
                            } else if (res.price_value_type == 'fixed') {
                                content = $t('Buy %1 Discount %2')
                                    .replace('%1', res.price_qty)
                                    .replace('%2', getAmount(res, idx));
                            } else {
                                content = $t('Buy %1 Discount %2%')
                                    .replace('%1', res.price_qty)
                                    .replace('%2', res.website_price);
                            }
                            $(element).append("<li class=\"item\">"+content+"</li>");
                        });
                    }    
                }
            }
        );
    }
});
