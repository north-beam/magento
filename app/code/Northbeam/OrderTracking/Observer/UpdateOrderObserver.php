<?php


namespace Northbeam\Integration\Observer;

require __DIR__ . '/../lib/NbOrderObject.php';

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateOrderObserver implements ObserverInterface
{

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    public function getClientID()
    {
        return $this->_scopeConfig->getValue("northbeam/credentials/client_id");
    }

    public function getApiKey()
    {
        return $this->_scopeConfig->getValue("northbeam/credentials/api_key");
    }


    public function execute(Observer $observer)
    {
        $lastOrder = $this->checkoutSession->getLastRealOrder();
        $server_data = setup_northbeam_objects($lastOrder)->server_object;

        $api_key = getClientID();
        $client_id = getApiKey();
    
        $server_data = $api_object->server_object;
    
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.northbeam.io/v1/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $server_data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: $api_key",
                "Content-Type: application/json",
                "Data-Client-ID: $client_id"
            ),
        ));
    
        $response = curl_exec($curl);
    
        curl_close($curl);
    }
}

