define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/file-uploader'
], function ($, _, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            fileInput: null
        },

        /**
         * {@inheritdoc}
         */
        initUploader: function (fileInput) {
            this.fileInput = $(fileInput)[0];
            if (!_.isEmpty(this.value())) {
                $(this.fileInput).hide();
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        removeFile: function (file) {
            $(this.fileInput).show();
            return this._super(file);
        },

        /**
         * @inheritDoc
         */
        validate: function () {
            return {
                valid: true,
                target: this
            };
        },

        /**
         * Browse file
         */
        browseFile: function () {
            $(this.fileInput).click();
        },

        /**
         * Retrieve input name
         *
         * @param {String} inputName
         * @return {String}
         */
        getInputName: function (inputName) {
            return this.inputName + '[' + inputName + ']';
        }
    });
});
