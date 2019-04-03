<?php

namespace Zaius\Engage\Api;

/**
 * Interface ConfigurationInterface
 * @package Zaius\Engage\Api
 */
interface ConfigurationInterface
{
    /**
     * @param $jsonOpts
     * @return string[]
     */
    public function getList($jsonOpts = null);
}