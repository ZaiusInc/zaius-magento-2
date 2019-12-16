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
        foreach ($this->trackScopeManager->getAllTrackingIds() as $trackingId) {
            try {
                $storeId = $this->trackScopeManager->getStoreIdByConfigValue($trackingId);
                $scopeProduct = $this->productRepository->getById($product->getId(), false, $storeId);
                $this->client->postProduct('catalog_product_save_after', $scopeProduct, $storeId);
            } catch (\Exception $e) {
                $this->logger->warning(sprintf("Error trying to load product %s", $e->getMessage()));
            }
        }
    }
}
