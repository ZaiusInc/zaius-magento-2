<?php

namespace Zaius\Engage\Api;


interface SubscriberRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $trackingID
     * @return mixed
     */
    public function getList($limit = null, $offset = null, $trackingID = null);
}
