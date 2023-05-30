define([
    'Magento_Ui/js/dynamic-rows/record',
], function (record) {
    'use strict';

    return record.extend({
        defaults: {
            invisibleComponents: ['sort_order', 'option_id']
        },

        /**
         * Set visibility to record child
         *
         * @param {Boolean} state
         */
        setVisible: function (state) {
            this.elems.each(function (cell) {
                cell.visible(state);
                this.hideInvisibleComponent(cell);
            }.bind(this));
        },

        /**
         * Hide invisible component
         *
         * @param component
         */
        hideInvisibleComponent: function(component) {
            this.invisibleComponents.forEach(function(element){
                if (component.name.indexOf(element) + 1) {
                    component.hide();
                }
            });
        }
    });
});
