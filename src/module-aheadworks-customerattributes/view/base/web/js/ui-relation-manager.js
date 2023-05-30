define([
    'jquery',
    'underscore',
    'uiRegistry',
    'rjsResolver'
], function($, _, registry, resolver) {

    $.widget('aw.awUiCustAttrRelationManager', {
        options: {
            relationsData: [],
            componentsNamespace: '',
            componentsCustomScope: '',
        },

        /**
         * @inheritDoc
         */
        _create: function() {
            resolver(this._bind, this);
        },

        /**
         * @inheritDoc
         */
        _bind: function() {
            var self = this;

            _.each(this.options.relationsData, function (relatedData, attributeCode) {
                self._initSwitcher(attributeCode, relatedData);
            });
        },

        /**
         * Init switcher for UI component
         *
         * @param {String} attributeCode
         * @param {Array} relatedData
         * @private
         */
        _initSwitcher: function (attributeCode, relatedData) {
            var self = this;

            registry.get(this._prepareRegistryQuery(attributeCode), function (component) {
                component.switcherConfig = self._prepareSwitcherConfig(component, relatedData);
                if (registry.has(component.switcherConfig.name)) {
                    registry.remove(component.switcherConfig.name);
                }
                component.initSwitcher();
            });
        },

        /**
         * Prepare switcher config
         *
         * @param {Object} component
         * @param {Object} relatedData
         * @return {Object}
         * @private
         */
        _prepareSwitcherConfig: function (component, relatedData) {
            var additionalConfig = { enabled: true },
                options = component.options instanceof Function
                    ? component.options()
                    : component.options,
                self = this,
                rules = [],
                actions,
                visibility;

            _.each(options, function (optionData) {
                actions = [];

                _.each(relatedData, function (dependentAttributes, optionValue) {
                    visibility = parseInt(optionValue) === parseInt(optionData.value);

                    _.each(dependentAttributes, function (dependentAttribute) {
                        actions.push(
                            {
                                target: self._prepareRegistryQuery(dependentAttribute),
                                callback: 'visible',
                                params: [visibility]
                            }
                        );
                    });
                });
                rules.push(
                    {
                        value: optionData.value,
                        actions: actions
                    }
                );
            });
            additionalConfig.rules = rules;

            return _.extend(component.switcherConfig, additionalConfig);
        },

        /**
         * Prepare registry query to find component
         *
         * @param {String} attributeCode
         * @return {String}
         * @private
         */
        _prepareRegistryQuery: function (attributeCode) {
            return this.options.componentsCustomScope
                ? 'index = ' + attributeCode + ', customScope = ' + this.options.componentsCustomScope
                : this.options.componentsNamespace + '.' + attributeCode;
        }
    });

    return $.aw.awUiCustAttrRelationManager;
});
