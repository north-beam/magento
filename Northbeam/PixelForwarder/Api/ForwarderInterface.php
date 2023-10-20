<?php

namespace Northbeam\PixelForwarder\Api;

interface ForwarderInterface
{
    /**
     * Proxies the given data to another service.
     * 
     * @api
     * @return mixed The response from the other service.
     */
    public function proxy();
}