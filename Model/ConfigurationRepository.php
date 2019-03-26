<?php
/**
 * Created by PhpStorm.
 * User: Trellis
 * Date: 3/21/2019
 * Time: 12:12 PM
 */

namespace Zaius\Engage\Model;

use Zaius\Engage\Api\ConfigurationInterface;

class ConfigurationRepository implements ConfigurationInterface
{
    public function getList()
    {
        return 'Zaius Engage ConfigurationRepository';
    }
}