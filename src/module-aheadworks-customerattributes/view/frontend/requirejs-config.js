var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping-address/list': {
                'Aheadworks_CustomerAttributes/js/checkout/view/shipping-address/list-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information/list': {
                'Aheadworks_CustomerAttributes/js/checkout/view/shipping-information/list-mixin': true
            },
            'Magento_Checkout/js/view/billing-address': {
                'Aheadworks_CustomerAttributes/js/checkout/view/billing-address-mixin': true
            },
            'Magento_Checkout/js/model/address-converter': {
                'Aheadworks_CustomerAttributes/js/checkout/model/address-converter-mixin': true
            },
            'Magento_Checkout/js/action/select-shipping-address': {
                'Aheadworks_CustomerAttributes/js/checkout/action/select-shipping-address-mixin': true
            }
        }
    }
};