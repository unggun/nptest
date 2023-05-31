define([
    'underscore',
    'Aheadworks_CustomerAttributes/js/ui/form/attribute/element/visible-on/select'
], function (_, Select) {
    'use strict';

    return Select.extend({

        /**
         * @inheritDoc
         */
        filterOptions: function (values, field) {
            this._super(values, field);
            if (_.contains(values, 'date')) {
                 this.visible(false);
                this.value('date');
            } else {
                this.visible(true);
            }
        }
    });
});
