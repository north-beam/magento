<?php

declare(strict_types=1);

namespace Northbeam\PixelForwarder\Controller;

use Magento\Webapi\Controller\Rest\RequestProcessorInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use \Magento\Webapi\Controller\Rest\InputParamsResolver;
use \Magento\Framework\Webapi\ServiceOutputProcessor;
use \Magento\Framework\Webapi\Rest\Response\FieldsFilter;
use \Magento\Framework\App\DeploymentConfig;
use \Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;

class PixelRequestProcessor implements RequestProcessorInterface
{
    const PROCESSOR_PATH = "nb-collector";

    /**
     * @var RestResponse
     */
    private $response;

    /**
     * @var InputParamsResolver
     */
    private $inputParamsResolver;

    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var FieldsFilter
     */
    private $fieldsFilter;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initial dependencies
     *
     * @param FrameworkResponse $response
     * @param WebapiInputParamsResolver $inputParamsResolver
     * @param MagentoServiceOutputProcessor $serviceOutputProcessor
     * @param WebapiFieldsFilter $fieldsFilter
     * @param MagentoDeploymentConfig $deploymentConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        RestResponse $response,
        InputParamsResolver $inputParamsResolver,
        ServiceOutputProcessor $serviceOutputProcessor,
        FieldsFilter $fieldsFilter,
        DeploymentConfig $deploymentConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->response = $response;
        $this->inputParamsResolver = $inputParamsResolver;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->fieldsFilter = $fieldsFilter;
        $this->deploymentConfig = $deploymentConfig;
        $this->objectManager = $objectManager;
        $this->logger = $objectManager->get(LoggerInterface::class);
    }

    /**
     *  {@inheritdoc}
     */
    public function process(Request $request)
    {
        // $inputParams = $this->inputParamsResolver->resolve();
        // $inputParams = $this->inputParamsResolver->getInputData();
        $route = $this->inputParamsResolver->getRoute();
        $serviceMethodName = $route->getServiceMethod();
        $serviceClassName = $route->getServiceClass();
        $service = $this->objectManager->get($serviceClassName);

        /**
         * @var \Magento\Framework\Api\AbstractExtensibleObject $outputData
         */
        $outputData = call_user_func_array([$service, $serviceMethodName], []);

        $response_code = $outputData['response_code'];
        $response_data = $outputData['response_data'];
        $response_headers = $outputData['response_headers'];

        $this->setHeaders($response_headers);
        $this->response->setHttpResponseCode($response_code);
        $this->response->setMimeType('text/plain');
        $this->response->setBody($response_data);
        $this->response->sendResponse();
    }

    /**
     *  {@inheritdoc}
     */
    public function canProcess(Request $request)
    {
        if (strpos(ltrim($request->getPathInfo(), '/'), self::PROCESSOR_PATH) === 0) {
            // if request's path starts with 'nb-collector' then we can process it
            return true;
        }
        return false;
    }

    protected function setHeaders($headers)
    {
        foreach($headers as $key => $value) {
            $this->response->setHeader($key, $value, true);
        }
    }
}
