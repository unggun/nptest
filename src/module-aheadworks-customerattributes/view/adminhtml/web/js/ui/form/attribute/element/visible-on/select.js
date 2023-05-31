define([
    'underscore',
    'Magento_Catalog/js/components/visible-on-option/select'
], function (_, Select) {
    'use strict';

    return Select.extend({

        /**
         * Filter options
         *
         * @param {Array} values
         * @param {string} field
         */
        filterOptions: function (values, field) {
            var source = this.initialOptions,
                valueBefore = this.value(),
                result;

            result = _.filter(source, function (item) {
                return _.contains(values, item[field]);
            });

            this.setOptions(result);
            this.value(valueBefore);
        },

        /**
         * @inheritDoc
         */
        reset: function () {
            if (!this.source.data.isEdit) {
                this._super();
            }
        }
    });
});
