<?php

namespace Zaius\Engage\Block\Snippet;

use Zaius\Engage\Block\Snippet;

/**
 * Class Search
 *
 * @package Zaius\Engage\Block\Snippet
 */
class Search extends Snippet
{

    /**
     * Get the event to the Search pages
     *
     * @return array
     */
    public function getEvents()
    {
        $pageViewEvents = parent::getEvents();
        $events = [
            [
                'type' => 'navigation',
                'data' => [
                    'action'               => 'search',
                    'search_term'          => $this->_request->getParam('q'),
                    'zaius_engage_version' => $this->_helper->getVersion()
                ]
            ]
        ];

        return array_merge($pageViewEvents, $events);
    }
}
