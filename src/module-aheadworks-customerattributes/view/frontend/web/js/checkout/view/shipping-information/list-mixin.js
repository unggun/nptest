define(
    [
        'underscore',
        'Aheadworks_CustomerAttributes/js/checkout/model/address/processor'
    ],
    function (_, addressProcessor) {
        'use strict';

        return function (Component) {
            return Component.extend({

                /**
                 * @inheritDoc
                 */
                createRendererComponent: function (address) {
                    this._super(
                        addressProcessor.removeCustomAttributes(address)
                    );
                }
            });
        }
    }
);
