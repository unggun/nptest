<?php

/**
 * Block to show form
 *
 * @since 1.0.3
 * @link ./registration.php
 */

namespace Icube\OverrideAdvancerate\Block\Adminhtml\Shipmentrate\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_systemStore;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Icube\ShipmentRate\Ui\Component\Listing\Column\Website $website,
        \Icube\ShipmentRate\Ui\Component\Listing\Column\Region $region,
        \Icube\ShipmentRate\Ui\Component\Listing\Column\City $city,
        \Icube\OverrideAdvancerate\Ui\Component\Listing\Column\CustomerGroup $cgroup,
        array $data = []
    ) {
        $this->website = $website;
        $this->region = $region;
        $this->city = $city;
        $this->cgroup = $cgroup;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'enctype' => 'multipart/form-data',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );

        if ($model->getId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Edit Rate'), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Add Rate'), 'class' => 'fieldset-wide']
            );
            // setting up default values
            $model->setData([
                'vendor_id' => 'admin',
                'shipping_method' => 'standard',
                'shipping_label' => 'Standard',
                'dest_country_id' => 'ID',
                'dest_zip' => '*',
                'weight_from' => '0',
                'weight_to' => '0',
            ]);
        }

        $fieldset->addField('vendor_id', 'hidden', ['name' => 'vendor_id']);
        $fieldset->addField('dest_country_id', 'hidden', ['name' => 'dest_country_id']);

        $fieldset->addField(
            'shipping_method',
            'text',
            [
                'name' => 'shipping_method',
                'label' => 'Shipping Method Code',
                'required' => true,
                'note' => __("It's how it will be proceed, example : jne_regular, jne_oke, tiki_regular"),
            ]
        );

        $fieldset->addField(
            'shipping_label',
            'text',
            [
                'name' => 'shipping_label',
                'label' => 'Shipping Method Label',
                'required' => true,
                'note' => __("It's what customer see, example : JNE Regular, JNE Oke, TIKI Regular"),
            ]
        );

        $fieldset->addField(
            'website_id',
            'select',
            [
                'name'     => 'website_id',
                'label'    => __('Website'),
                'values'   => $this->website->toOptionArray(),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'dest_region_id',
            'select',
            [
                'name'     => 'dest_region_id',
                'label'    => __('Region'),
                'values'   => $this->region->toOptionArray(),
                'required' => true,
            ]
        )->setBeforeElementHtml(
            "<script type='text/javascript'>
                require(['jquery', 'jquery/ui'], function($){ 
                  'use strict';
                    $(document).ready(function(){
                        var city = $('#city').children('option:selected').val();
                        $.ajax({
                            url: '{$this->getUrl('icube_shipmentrate/rate/getcity')}region/' + $('#dest_region_id').children('option:selected').val(),
                            method: 'GET',
                            success: function(result) {
                                $('#city').find('option').remove();
                                $.each(JSON.parse(result), function(key, value) { 
                                     $('#city').append($('<option></option>').attr('value',key).text(value)); 
                                });

                                $('#city').val(city).change();
                            }
                        });

                        $( '#dest_region_id' ).change(function() {
                            $.ajax({
                                url: '{$this->getUrl('icube_shipmentrate/rate/getcity')}region/' + $(this).children('option:selected').val(),
                                method: 'GET',
                                success: function(result) {
                                    $('#city').find('option').remove();
                                    $.each(JSON.parse(result), function(key, value) { 
                                         $('#city').append($('<option></option>').attr('value',key).text(value)); 
                                    });
                                }
                            });
                        });
                    });
                 });
            </script>"
        );

        $fieldset->addField(
            'city',
            'select',
            [
                'name'     => 'city',
                'label'    => __('City'),
                'values'   => $this->city->toOptionArray($model->getDestRegionId()),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'dest_zip',
            'text',
            [
                'name' => 'dest_zip',
                'label' => __('Zip'),
                'id' => 'dest_zip',
                'title' => __('Zip'),
                'class' => 'required-entry',
                'required' => true,
                'note' => __("Set (*) to enable it to all location inside City above."),
            ]
        );

        $fieldset->addField(
            'price',
            'text',
            [
                'name' => 'price',
                'label' => __('Price'),
                'id' => 'price',
                'title' => __('Price'),
                'class' => 'required-entry validate-number',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'weight_from',
            'text',
            [
                'name' => 'weight_from',
                'label' => __('Minimum Weight'),
                'title' => __('Minimum Weight'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'weight_to',
            'text',
            [
                'name' => 'weight_to',
                'label' => __('Maximum Weight'),
                'title' => __('Maximum Weight'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'price_from',
            'text',
            [
                'name' => 'price_from',
                'label' => __('Minimum Price'),
                'title' => __('Minimum total price'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'price_to',
            'text',
            [
                'name' => 'price_to',
                'label' => __('Maximum Price'),
                'title' => __('Maximum total price'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'qty_from',
            'text',
            [
                'name' => 'qty_from',
                'label' => __('Minimum qty'),
                'title' => __('Minimum qty'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'qty_to',
            'text',
            [
                'name' => 'qty_to',
                'label' => __('Maximum qty'),
                'title' => __('Maximum qty'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'etd',
            'text',
            [
                'name' => 'etd',
                'label' => __('ETD'),
                'title' => __('Estimated Time of Delivery'),
                'class' => 'required-entry',
                'required' => false,
            ]
        );

        $fieldset->addField(
            'customer_group',
            'select',
            [
                'name' => 'customer_group',
                'label' => __('Customer Group'),
                'title' => __('Customer Group'),
                'values' => $this->cgroup->toOptionArray(),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
