<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\ViewModel\Customer\Form;

use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\Date;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\File;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\Multiline;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\Multiselect;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\Select;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\Text;
use Aheadworks\CustomerAttributes\Block\Attribute\Renderer\TextArea;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;

/**
 * Class AbstractView
 * @package Aheadworks\CustomerAttributes\ViewModel\Customer\Form
 */
abstract class AbstractView implements ArgumentInterface
{
    /**
     * @var ElementFactory
     */
    protected $elementFactory;

    /**
     * @var FormAttributes
     */
    protected $formAttributes;

    /**
     * @var StoreResolver
     */
    protected $storeResolver;

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var string
     */
    protected $formCode = UsedInForms::CUSTOMER_ACCOUNT_CREATE;

    /**
     * @var array
     */
    protected $skipAttributes = [
        'dob',
        'firstname',
        'lastname',
        'prefix',
        'suffix',
        'email',
        'taxvat',
        'gender',
        'middlename'
    ];

    /**
     * @var array
     */
    protected $renderers = [
        InputType::MULTILINE => Multiline::class,
        InputType::DATE => Date::class,
        InputType::MULTISELECT => Multiselect::class,
        InputType::IMAGE => File::class,
        InputType::FILE => File::class,
        InputType::DROPDOWN => Select::class,
        InputType::TEXT => Text::class,
        InputType::TEXTAREA => TextArea::class,
        InputType::BOOL => Select::class
    ];

    /**
     * @param ElementFactory $elementFactory
     * @param FormAttributes $formAttributes
     * @param StoreResolver $storeResolver
     * @param FormFactory $formFactory
     * @param array $skipAttributes
     * @param array $renderers
     */
    public function __construct(
        ElementFactory $elementFactory,
        FormAttributes $formAttributes,
        StoreResolver $storeResolver,
        FormFactory $formFactory,
        array $skipAttributes = [],
        array $renderers = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->formAttributes = $formAttributes;
        $this->storeResolver = $storeResolver;
        $this->form = $formFactory->create();
        $this->skipAttributes = array_merge($this->skipAttributes, $skipAttributes);
        $this->renderers = array_merge($this->renderers, $renderers);
    }

    /**
     * Retrieve form attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $result = [];
        $attributes = $this->formAttributes
            ->setFormCode($this->formCode)
            ->getAllowedAttributes();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if (!$attribute->isStatic()) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Retrieve option values
     *
     * @param Attribute $attribute
     * @return array
     */
    protected function getSourceOptionValues($attribute)
    {
        $prepared = [];
        $default = null;
        foreach ((array)$attribute->getOptions() as $option) {
            if ($option->getValue() || $option->getValue() === 0) {
                $prepared[] = [
                    'label' => $option->getLabel(),
                    'value' => $option->getValue()
                ];
            }
        }

        return $prepared;
    }

    /**
     * Retrieve renderer type
     *
     * @param string $inputType
     * @return string
     */
    protected function getRendererType($inputType)
    {
        return isset($this->renderers[$inputType])
            ? $this->renderers[$inputType]
            : $inputType;
    }

    /**
     * Get field classes
     *
     * @param Attribute $attribute
     * @return string
     */
    protected function getClasses($attribute)
    {
        $fieldClasses = ['field', $attribute->getFrontendInput()];
        if ($attribute->getIsRequired()) {
            $fieldClasses[] = 'required';
        }

        return implode(' ', $fieldClasses);
    }

    /**
     * Retrieve frontend label
     *
     * @param Attribute $attribute
     * @return string
     */
    protected function getFrontendLabel($attribute)
    {
        $label = $attribute->getFrontendLabel();
        $storeId = $this->storeResolver->getCurrentStoreId();

        foreach ((array)$attribute->getFrontendLabels() as $frontendLabel) {
            if ($frontendLabel->getStoreId() == $storeId) {
                $label = $frontendLabel->getLabel();
                break;
            }
        }

        return $label;
    }

    /**
     * Render field for attribute
     *
     * @param Attribute $attribute
     * @return string
     */
    public function render($attribute)
    {
        if (in_array($attribute->getAttributeCode(), $this->skipAttributes) || $attribute->isStatic()) {
            return '';
        }

        $renderer = $this->elementFactory->create($this->getRendererType($attribute->getFrontendInput()));
        $renderer
            ->setForm($this->form)
            ->setType($attribute->getFrontendInput())
            ->setData('required', $attribute->getIsRequired())
            ->setData('name', $attribute->getAttributeCode())
            ->setData('html_id', $attribute->getAttributeCode())
            ->setData('label', $this->getFrontendLabel($attribute))
            ->setData('line_count', $attribute->getMultilineCount())
            ->setData('value', $this->getValue($attribute))
            ->setData('attribute', $attribute)
            ->setData('class', $this->getClasses($attribute));

        if ($attribute->usesSource()) {
            $options = $this->getSourceOptionValues($attribute);
            $renderer->setData('values', $options);
        }

        return $renderer->toHtml();
    }

    /**
     * Retrieve attribute value
     *
     * @param Attribute $attribute
     * @return string
     */
    abstract protected function getValue($attribute);
}
