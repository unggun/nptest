define([
    'Magento_Ui/js/form/element/multiselect',
    'Magento_Catalog/js/components/visible-on-option/strategy'
], function (Element, strategy) {
    'use strict';

    return Element.extend(strategy).extend({

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
        }
    });
});
