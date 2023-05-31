define([
        'underscore',
        'mage/utils/wrapper',
    ], function (_, wrapper) {
        'use strict';

        return function (model) {
            return _.extend(model, {

                /**
                 * @inheritDoc
                 */
                formAddressDataToQuoteAddress: wrapper.wrap(
                    model.formAddressDataToQuoteAddress, function (origAction, formData) {
                        var resultAddress = origAction(formData),
                            preparedCustomAttributes = [],
                            attributeData;

                        _.each(resultAddress.customAttributes, function (value, code) {
                            if (!_.isObject(value) || _.isArray(value)) {
                                attributeData = {
                                    attribute_code: code,
                                    value: value
                                }
                            } else {
                                attributeData = value;
                            }
                            preparedCustomAttributes.push(attributeData);
                        });
                        resultAddress.customAttributes = preparedCustomAttributes;

                        return resultAddress;
                    })
            });
        }
    }
);
