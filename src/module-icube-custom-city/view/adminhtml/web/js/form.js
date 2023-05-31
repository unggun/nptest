/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/form/form',
    'underscore',
    'jqueryui',
    'Magento_Sales/order/create/scripts',
    'mage/translate'
], function ($, uiAlert, uiConfirm, Form, _, ui, $t) {
    'use strict';

    AdminOrder.prototype.loadAreaResponseHandler = function(response){
        if (response.error) {
         alert({
             content: response.message
         });
     }
     if (response.ajaxExpired && response.ajaxRedirect) {
         setLocation(response.ajaxRedirect);
     }
     if (!this.loadingAreas) {
         this.loadingAreas = [];
     }
     if (typeof this.loadingAreas == 'string') {
         this.loadingAreas = [this.loadingAreas];
     }
     if (this.loadingAreas.indexOf('message') == -1) {
         this.loadingAreas.push('message');
     }
     if (response.header) {
         jQuery('.page-actions-inner').attr('data-title', response.header);
     }

     for (var i = 0; i < this.loadingAreas.length; i++) {
         var id = this.loadingAreas[i];
         if ($(this.getAreaId(id))) {
             if ((id in response) && id !== 'message' || response[id]) {
                 $(this.getAreaId(id)).update(response[id]);
             }
             if ($(this.getAreaId(id)).callback) {
                 this[$(this.getAreaId(id)).callback]();
             }
         }
     }
    }  

    setInterval(() => {
        let country = jQuery('.customer_form_areas_address_address_customer_address_update_modal select[name="country_id"]');
        let selectTypeRegion = jQuery('.customer_form_areas_address_address_customer_address_update_modal select[name="region_id"]');
        let inputTypeRegion = jQuery('.customer_form_areas_address_address_customer_address_update_modal input[name="region"]');
        let city = jQuery('.customer_form_areas_address_address_customer_address_update_modal input[name="city"]');
        let postcode = jQuery('.customer_form_areas_address_address_customer_address_update_modal input[name="postcode"]');

        country.on('change', () => {
            if(country.val() == 'ID'){
                selectTypeRegion.closest('.admin__field').css('display', 'none');
                city.attr('placeholder', jQuery.mage.__('please enter 4 or more characters')).attr('autocomplete', 'autocomplete_hack_off');
                let cityListBilling = city.autocomplete({
                    source: function( request, response ) {
                        city.parent().find('.mage-error').remove();
                        jQuery.ajax( {
                            url: "/citylist/ajax/citykecamatan",
                            dataType: "json",
                            data: {
                                term: request.term
                            },
                            success: function( data ) {
                                response(data);
                            }
                        });
                    },
                    minLength: 4,
                    select: function( event, ui ) {
                        selectTypeRegion.val(ui.item.id).trigger('change');
                        postcode.val(ui.item.zip).trigger('change');
                    },
                    change: function( event, ui ){
                        jQuery(this).val((ui.item ? ui.item.value : ""));
                    
                        if(!ui.item) {
                            let error = '<div generated="true" class="mage-error">' + jQuery.mage.__('Please choose city from auto suggestion.') + '</div>';
                            city.parent().append(error);
                        }
                    }
                });
            } else {
                let regionOption = jQuery('.customer_form_areas_address_address_customer_address_update_modal select[name="region_id"] option');
                if(regionOption.length > 1){
                    selectTypeRegion.closest('.admin__field').css('display', 'block');
                }
                city.val(' ');
                selectTypeRegion.val(' ');
                inputTypeRegion.val(' ');
                postcode.val(' ');
                city.attr('placeholder', jQuery.mage.__(''));
                if (city.hasClass('ui-autocomplete-input'))
                    city.autocomplete('destroy');
            }
        });
    }, 500);

    return Form.extend({
        defaults: {
            deleteConfirmationMessage: '',
            ajaxSettings: {
                method: 'POST',
                dataType: 'json'
            }
        },

        /**
         * Delete customer address by provided url.
         * Will call confirmation message to be sure that user is really wants to delete this address
         *
         * @param {String} url - ajax url
         */
        deleteAddress: function (url) {
            var that = this;

            uiConfirm({
                content: this.deleteConfirmationMessage,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        that._delete(url);
                    }
                }
            });
        },

        /**
         * Perform asynchronous DELETE request to server.
         * @param {String} url - ajax url
         * @returns {Deferred}
         */
        _delete: function (url) {
            var settings = _.extend({}, this.ajaxSettings, {
                    url: url,
                    data: {
                        'form_key': window.FORM_KEY
                    }
                }),
                that = this;

            $('body').trigger('processStart');

            return $.ajax(settings)
                .done(function (response) {
                    if (response.error) {
                        uiAlert({
                            content: response.message
                        });
                    } else {
                        that.trigger('deleteAddressAction', that.source.get('data.entity_id'));
                    }
                })
                .fail(function () {
                    uiAlert({
                        content: $t('Sorry, there has been an error processing your request. Please try again later.')
                    });
                })
                .always(function () {
                    $('body').trigger('processStop');
                });
        }
    });
});