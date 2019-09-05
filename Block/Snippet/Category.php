<?php

namespace Zaius\Engage\Block\Snippet;

use Magento\Framework\Exception\NoSuchEntityException;
use Zaius\Engage\Block\Snippet;

/**
 * Class Category
 *
 * @package Zaius\Engage\Block\Snippet
 */
class Category extends Snippet
{

    /**
     * Get the event to the Category pages
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getEvents()
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->_registry->registry('current_category');
        $pageViewEvents = parent::getEvents();
        $events = [
            [
                'type' => 'navigation',
                'data' => [
                    'action'               => 'browse',
                    'category'             => $this->_helper->getCategoryNamePathAsString($category),
                    'zaius_engage_version' => $this->_helper->getVersion()
                ]
            ]
        ];

        return array_merge($pageViewEvents, $events);
    }
}
