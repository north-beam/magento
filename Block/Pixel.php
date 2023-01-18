<?php

namespace Northbeam\OrderTracking\Block;


class Pixel extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    public function getClientID()
    {
        return $this->_scopeConfig->getValue("northbeam/credentials/client_id");
    }
}
