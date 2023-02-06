<?php

namespace Northbeam\OrderTracking\Block;

require_once __DIR__ . '/../lib/NbOrderObject.php';

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Csp\Api\InlineUtilInterface
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    public function getLastOrder(){
      $lastOrder = $this->checkoutSession->getLastRealOrder();
      return json_encode($lastOrder->getData());
    }

    public function getLastOrderItems(){
      $lastOrder = $this->checkoutSession->getLastRealOrder();

      $itemsData = array();
      foreach($lastOrder->getAllVisibleItems() as $item) {
        if ($item->getData()){
          $itemsData[] = $item->getData();
        }
      }

      return json_encode($itemsData);
    }

    public function getNbOrderServerObject(){
      $lastOrder = $this->checkoutSession->getLastRealOrder();
      return setup_northbeam_objects($lastOrder)->server_object;
    }

    public function getNbOrderJsObject(){
      $lastOrder = $this->checkoutSession->getLastRealOrder();
      return setup_northbeam_objects($lastOrder)->javascript_object;
    }

    public function getClientID()
    {
        return $this->_scopeConfig->getValue("northbeam/credentials/client_id", \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

}