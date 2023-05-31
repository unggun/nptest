define([
    'underscore'
], function (_) {
    'use strict';

    return {

        /**
         * Remove customer attributes from the address if applicable
         *
         * @param {Object|null} address
         * @returns {Object|null}
         */
        removeCustomAttributes: function (address) {
            return (address == null)
                ? address
                : _.omit(address, 'customAttributes');
        }
    };
});
