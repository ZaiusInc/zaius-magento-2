<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogSearch\Block\Result;
use Magento\CatalogSearch\Block\Advanced\Result as AdvancedResult;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Zaius\Engage\Helper\Data;

/**
 * Class PageViewObserver
 * @package Zaius\Engage\Observer
 */
class PageViewObserver
    implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var Registry
     */
    protected $_registry;
    /**
     * @var LayoutInterface
     */
    protected $_layout;
    /**
     * @var
     */
    protected $_localeHelper;

    /**
     * PageViewObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param RequestInterface $request
     * @param Registry $registry
     * @param LayoutInterface $layout
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        RequestInterface $request,
        Registry $registry,
        LayoutInterface $layout
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_layout = $layout;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $store = $this->_storeManager->getStore();
        if ($this->_helper->getStatus($store)) {
            switch ($this->_request->getFullActionName()) {
                case 'catalog_category_view':
                    /** @var ListProduct $listBlock */
                    $listBlock = $this->_layout->getBlock('category.products.list');
                    if ($listBlock) {
                        /** @var Category $category */
                        $category = $this->_registry->registry('current_category');
                        $this->_helper->addEventToRegistry([
                            'type' => 'navigation',
                            'data' => [
                                'action' => 'browse',
                                'category' => $this->_helper->getCategoryNamePathAsString($category),
                                'zaius_engage_version' => $this->_helper->getVersion()
                            ]
                        ]);
                    }
                    break;
                case 'catalog_product_view':
                    /** @var Product $product */
                    $product = $this->_registry->registry('product');

                    $this->_helper->addEventToRegistry([
                        'type' => 'product',
                        'data' => [
                            'action' => 'detail',
                            'product_id' => $this->_helper->getProductId($product),
                            'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
                            'zaius_engage_version' => $this->_helper->getVersion(),
                        ]
                    ]);
                    break;
                case 'catalogsearch_result_index':
                case 'catalogsearch_advanced_result':
                    $this->_helper->addEventToRegistry([
                        'type' => 'navigation',
                        'data' => [
                            'action' => 'search',
                            'search_term' => $this->_request->getParam('q'),
                            'zaius_engage_version' => $this->_helper->getVersion()
                        ]
                    ]);
                    break;
            }
        }
        return $this;
    }
}