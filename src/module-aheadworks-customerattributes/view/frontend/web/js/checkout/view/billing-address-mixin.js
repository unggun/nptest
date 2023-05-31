define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Aheadworks_CustomerAttributes/js/checkout/model/address/processor'
    ], function (
        ko,
        quote,
        addressProcessor
    ) {
        'use strict';

        return function (Component) {
            return Component.extend({
                currentBillingAddress: ko.observable(quote.billingAddress()),

                /**
                 * @inheritDoc
                 */
                initialize: function () {
                    var self = this;

                    this._super();
                    quote.billingAddress.subscribe(function (newAddress) {
                        self.currentBillingAddress(
                            addressProcessor.removeCustomAttributes(newAddress)
                        );
                    });
                }
            });
        }
    }
);
