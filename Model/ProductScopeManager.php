<?php

namespace Zaius\Engage\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Zaius\Engage\Logger\Logger;

class ProductScopeManager
{
    /**
     * @var TrackScopeManager
     */
    private $trackScopeManager;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        TrackScopeManager $trackScopeManager,
        ProductRepositoryInterface $productRepository,
        Client $client,
        Logger $logger
    ) {
        $this->trackScopeManager = $trackScopeManager;
        $this->productRepository = $productRepository;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param Product $product
     */
    public function sync($product)
    {
        $duplicatedTrackingIds = $this->trackScopeManager->getStoriesWithDuplicatedTrackingId();
        foreach ($this->trackScopeManager->getAllTrackingIds() as $trackingId) {
            try {
                $storeId = $this->trackScopeManager->getStoreIdByConfigValue($trackingId);
                $scopeProduct = $this->productRepository->getById($product->getId(), false, $storeId);

                if (sizeof($product->getStoreIds()) > 1 && in_array($trackingId, $duplicatedTrackingIds)) {
                    foreach ($duplicatedTrackingIds as $storeId => $duplicatedTrackingId) {
                        $scopeProduct->setData('has_view_variants', true);
                        $scopeProduct->setData('generic_product_id', $scopeProduct->getId() . '-' . $this->trackScopeManager->getStoreCode($storeId));
                        $this->client->postProduct('catalog_product_save_after', $scopeProduct, $storeId);
                    }

                    $scopeProduct->setData('has_view_variants', true);
                    $scopeProduct->setData('generic_product_id', $scopeProduct->getId());
                    $this->client->postProduct('catalog_product_save_after', $scopeProduct, $storeId);
                    continue;
                }
                $this->client->postProduct('catalog_product_save_after', $scopeProduct, $storeId);
            } catch (\Exception $e) {
                $this->logger->warning(sprintf("Error trying to load product %s", $e->getMessage()));
            }
        }
    }
}
