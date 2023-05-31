
define([
    'Magento_Catalog/js/components/visible-on-option/fieldset'
], function (Fieldset) {
    'use strict';

    return Fieldset.extend(
        {
            /**
             * @inheritDoc
             */
            toggleVisibility: function () {
                if (this.source.data.isEdit) {
                    this._super();
                } else {
                    this.visible(false);
                }
            }
        }
    );
});
