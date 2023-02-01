<?php


namespace Northbeam\OrderTracking\Observer;

require_once __DIR__ . '/../lib/NbOrderObject.php';


use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CancelOrder implements ObserverInterface
{

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;  
        $this->logger = $logger;
    }

    public function getClientID()
    {
        return $this->scopeConfig->getValue("northbeam/credentials/client_id", \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    public function getApiKey()
    {
        return $this->scopeConfig->getValue("northbeam/credentials/api_key", \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    public function execute(Observer $observer)
    {

        $api_key = $this->getApiKey();

        $client_id = $this->getClientID();

        if (!$api_key || !$client_id) {
            $this->logger->info("Northbeam S2S: Please include the client ID and API key in the Magento configuration!");
            return;
        }

        $order = $observer->getEvent()->getOrder();

        $server_data = setup_northbeam_objects($order)->server_object_cancelled;

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

        $this->logger->info("Sending order cancelation to NB S2S API");
        $this->logger->info($response);

    }
}