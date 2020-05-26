<?php

namespace Zaius\Engage\Model;

use Zaius\Engage\Helper\Sdk;
use ZaiusSDK\Zaius\Worker;

/**
 * Class Cron
 * @package Zaius\Engage\Model
 */
class Cron
{

    /** @var Sdk */
    protected $sdk;

    /**
     * Cron constructor.
     * @param Sdk $sdk
     */
    public function __construct(Sdk $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
     *
     */
    public function process()
    {
        // $zaiusClient = $this->sdk->getSdkClient();
        // $worker = new Worker();
        // $worker->processAll();
    }
}
