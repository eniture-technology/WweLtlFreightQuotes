<?php

namespace Eniture\WweLtlFreightQuotes\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

/**
 * Class LimitedAccessFee
 * @package Eniture\WweLtlFreightQuotes\Block\System\Config
 */
class LimitedAccessFee extends Field
{
    const CONFIG_PATH_FEE = 'WweLtQuoteSetting/third/limitedAccessFee';
    const TEMPLATE = 'system/config/limitedaccessfee.phtml';

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * LimitedAccessFee constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getFeeValue()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_FEE, ScopeInterface::SCOPE_STORE);
    }
}
