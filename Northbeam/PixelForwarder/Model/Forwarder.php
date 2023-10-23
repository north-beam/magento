<?php

namespace Northbeam\PixelForwarder\Model;

use Magento\Framework\Webapi\Rest\Request;
use Northbeam\PixelForwarder\Api\ForwarderInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class Forwarder implements ForwarderInterface
{
    protected Request $request;
    protected LoggerInterface $logger;
    protected const COLLECTOR_URL = 'https://i.northbeam.io/nb-collector';
    protected const REQUEST_HEADERS = [
        'x-forwarded-proto',
        'x-forwarded-host',
        'x-forwarded-for',
        'sec-fetch-site',
        'sec-fetch-mode',
        'sec-fetch-dest',
        'sec-ch-ua-platform',
        'sec-ch-ua-mobile',
        'sec-ch-ua',
        'referer',
        'origin',
        'cookie',
        'content-type',
        'accept-language',
        'accept-encoding',
        'accept',
        'user-agent',
        'authorization'
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $object_manager = ObjectManager::getInstance();
        $this->logger = $object_manager->get(LoggerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function proxy()
    {
        $request = $this->request;
        $body = $request->getRequestData();
        $encoded_body = json_encode($body);
        $content_length = strlen($encoded_body); // somehow the content length from the body and the content length from the headers are different, we need to calculate it ourselves
        $headers = $this->mapHeaderKeysCurl($this->getRequestHeaders($content_length));

        $response_headers = [];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::COLLECTOR_URL,
            CURLOPT_RETURNTRANSFER => true, // return response as string
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $encoded_body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true, // include headers in response
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$response_headers) { // callback for response headers
                return $this->processResponseHeader($curl, $header, $response_headers);
            }
        ));
        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $response_body = substr($response, $header_size); // remove headers from response
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Sending this back to the request processor to be sent back to the client
        return [
            "response_code" => $response_code,
            "response_data" => $response_body,
            "response_headers" => $response_headers,
        ];
    }

    /**
     * Returns an array of request headers.
     *
     * Magento's Request object has a 'getHeaders' method that, in theory, is supposed to return all the headers, but it always returns null.
     * On the other hand, the 'getHeader' method works fine, but it only returns one header at a time, and you have to pass the header name as a parameter.
     *
     * @return array An array of request headers in the format (key, value).
     */
    protected function getRequestHeaders(string $content_length)
    {
        $headers = []; // (key, value) array
        $headers['content-length'] = $content_length;
        foreach (self::REQUEST_HEADERS as $header) {
            $value = $this->request->getHeader($header, false);
            if ($value !== false) { // if header exists
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Returns an array of request headers in the format expected by cURL.
     *
     * cURL expects headers to be a list of strings in the format 'header-name: header-value, header-value, ...'
     *
     * @param array $headers An array of request headers in the format (key, value).
     * @return array An array of request headers in the format expected by cURL.
     */
    protected function mapHeaderKeysCurl(array $headers)
    {
        return array_map(function ($key, $value) {
            return $key . ': ' . $value;
        }, array_keys($headers), $headers);
    }


    /**
     * Processes a single response header from a cURL request and adds it to an array of response headers.
     *
     * This function is called by cURL for each header received in the response. It parses the header string into a key-value pair and adds it to the $response_headers array.
     *
     * @param mixed $curl The cURL handle.
     * @param string $header The header string received from the server.
     * @param array $response_headers An array of response headers in the format (key, value).
     * @return int The number of bytes read from the header string (used by cURL as a pointer).
     */
    function processResponseHeader(\CurlHandle $curl, string $header, array &$response_headers)
    {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) // ignore invalid headers
            return $len; // number of bytes read (used by curl as a pointer)
        if (isset($response_headers[trim($header[0])])) {
            $response_headers[trim($header[0])] = $response_headers[trim($header[0])] . ', ' . trim($header[1]);
        } else {
            $response_headers[trim($header[0])] = trim($header[1]);
        }
        return $len; // number of bytes read
    }
}
