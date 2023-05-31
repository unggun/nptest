define([
    'underscore',
    'Magento_Ui/js/form/element/single-checkbox',
], function (_, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            listens: {
                '${ $.provider }:data.is_default.clear': 'clear'
            },
            preventFlag: false
        },

        /**
         * @inheritDoc
         */
        onCheckedChanged: function (newChecked) {
            if (newChecked && !this.preventFlag) {
                this.preventFlag = true;
                this.source.trigger('data.is_default.clear');
                this.preventFlag = false;
            }
            this._super(newChecked);
        },

        /**
         * @inheritDoc
         */
        clear: function () {
            if (!this.preventFlag) {
                this._super();
            }
        }
    });
});
