define([
    'jquery',
    'underscore'
], function($, _) {

    $.widget('aw.awCustAttrRelationManager', {
        options: {
            relationsData: [],
            fieldInputSelectorPatterns: ['[id={attr_code}]'],
            fieldWrapperSelector: ''
        },

        /**
         * Initialize widget
         */
        _create: function() {
            $(document).ready(this._bind.bind(this));
        },

        /**
         * Event binding
         */
        _bind: function() {
            var self = this,
                mainElement;

            _.each(this.options.relationsData, function (relatedData, attributeCode) {
                _.each(self.options.fieldInputSelectorPatterns, function (pattern) {
                    mainElement = $(pattern.replace('{attr_code}', attributeCode));
                    if (mainElement.length) {
                        $(mainElement).change(self.onChange.bind(self, attributeCode, relatedData, pattern));
                        $(mainElement).change();
                    }
                });
            });
        },

        /**
         * On change handler
         *
         * @param {String} attributeCode
         * @param {Object} relatedData
         * @param {String} pattern
         * @param {jQuery.Event} event
         * @return {aw.awCustAttrRelationManager}
         */
        onChange: function (attributeCode, relatedData, pattern, event) {
            var value = parseInt($(event.currentTarget).val()),
                self = this;
            
            if (relatedData[value]) {
                this._toggleFieldsVisibility(relatedData[value], pattern, true);
            }
            _.each(relatedData, function (dependentAttrCodes, optionValue) {
                if (parseInt(optionValue) !== value) {
                    self._toggleFieldsVisibility(dependentAttrCodes, pattern, false);
                }
            });

            return this;
        },

        /**
         * Hide fields
         *
         * @param {Object} dependentAttrCodes
         * @param {String} pattern
         * @param {Boolean} toShow
         * @private
         */
        _toggleFieldsVisibility: function (dependentAttrCodes, pattern, toShow) {
            var self = this;

            _.each(dependentAttrCodes, function (dependentAttrCode) {
                toShow ? self._show(dependentAttrCode, pattern) : self._hide(dependentAttrCode, pattern);
            });
        },

        /**
         * Hide element
         *
         * @param {String} dependentAttrCode
         * @param {String} pattern
         * @private
         */
        _hide: function(dependentAttrCode, pattern) {
            var elem = $(pattern.replace('{attr_code}', dependentAttrCode));

            if (elem.length) {
                $(elem).closest(this.options.fieldWrapperSelector).hide();
            }

        },

        /**
         * Show element
         *
         * @param {String} dependentAttrCode
         * @param {String} pattern
         * @private
         */
        _show: function(dependentAttrCode, pattern) {
            var elem = $(pattern.replace('{attr_code}', dependentAttrCode));

            if (elem.length) {
                $(elem).closest(this.options.fieldWrapperSelector).show();
            }
        }
    });

    return $.aw.awCustAttrRelationManager;
});
