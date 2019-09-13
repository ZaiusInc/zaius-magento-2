<?php

namespace Zaius\Engage\Api;

/**
 * Interface ConfigurationInterface
 * @package Zaius\Engage\Api
 */
interface ConfigurationInterface
{
    /**
     * @param string $trackingID
     * @return mixed[]
     */
    public function getList($trackingID = null);
}
