define([
    'underscore',
    'Aheadworks_CustomerAttributes/js/ui/form/attribute/element/visible-on/dynamic-rows',
], function (_, Element) {
    'use strict';

    return Element.extend({

        /**
         * @inheritDoc
         */
        reinitRecordData: function () {
            var deletedOptions = this.source.data.deleted_options,
                newDeletedOptions =  _.filter(this.recordData(), function (elem) {
                return elem && elem[this.deleteProperty] === this.deleteValue && elem.option_id;
            }, this);

            this.source.data.deleted_options = _.union(deletedOptions, newDeletedOptions);
            this._super();
        }
    });
});
