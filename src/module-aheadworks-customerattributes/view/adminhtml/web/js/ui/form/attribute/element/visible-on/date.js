define([
    'mageUtils',
    'Magento_Catalog/js/components/visible-on-option/date'
], function (utils, Date) {
    'use strict';

    return Date.extend({
        dataFromPersistor: false,

        /**
         * @inheritdoc
         */
        setListeners: function () {
            this._super();
            this.dataFromPersistor = !!this.source.data.from_persistor;

            return this;
        },

        /**
         * @inheritdoc
         */
        onValueChange: function () {
            var inputDateFormat = this.inputDateFormat;

            if (this.dataFromPersistor) {
                this.inputDateFormat = this.outputDateFormat;
            }

            this._super();

            if (this.dataFromPersistor) {
                this.inputDateFormat = inputDateFormat;
                this.dataFromPersistor = false;
            }
        }
    });
});