define([
    'underscore',
    'mage/utils/wrapper',
    'Magento_Customer/js/model/address-list'
], function (_, wrapper, addressList) {
    'use strict';

    return function (selectShippingAddressAction) {
        return wrapper.wrap(selectShippingAddressAction, function (originalAction, shippingAddress) {
            if (!_.has(shippingAddress, 'customAttributes')) {
                _.each(addressList(), function(address){
                    if (_.has(address, 'customAttributes') && shippingAddress.getKey() === address.getKey()) {
                        shippingAddress.customAttributes = address.customAttributes;
                    }
                });
            }

            return originalAction(shippingAddress);
        });
    };
});
