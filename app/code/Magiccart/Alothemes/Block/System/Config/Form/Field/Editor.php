<?php
namespace Magiccart\Alothemes\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Config\Block\System\Config\Form\Field as FormField;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Editor extends FormField
{
    /**
    * @var WysiwygConfig
    */
    protected $wysiwygConfig;
    /**
    * @param Context $context
    * @param WysiwygConfig $wysiwygConfig
    * @param array $data
    */
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setWysiwyg(true);
        $element->setConfig($this->_wysiwygConfig->getConfig($element)); // If you want to remove specific button then use this below code in setConfig()
        /** * $this->_wysiwygConfig->getConfig(['add_variables' => true,'add_widgets' => false,'add_images' => true,]) */
        return parent::_getElementHtml($element);
    }
}