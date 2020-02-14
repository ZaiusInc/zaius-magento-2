<?php

namespace Zaius\Engage\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Paypal\Model\Pro;
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
        try {
            $trackingIds = $this->getStoreByTrackingId($product);
            $genericProductId = $product->getId();
            foreach ($trackingIds as $trackingId => $storeIds) {
                if (sizeof($storeIds) > 1) {
                    foreach ($storeIds as $storeId) {
                        $scopeProduct = $this->productRepository->getById($product->getId(), false, $storeId);
                        $productId = $product->getId() . '-' . $this->trackScopeManager->getStoreCode($storeId);

                        $scopeProduct->setData('has_view_variants', true);
                        $scopeProduct->setId($productId);
                        $scopeProduct->setData('generic_product_id', $genericProductId);
                        $this->client->postProduct('catalog_product_save_after', $scopeProduct, $storeId);
                    }
                    //main product
                    $scopeProduct = $this->productRepository->getById($product->getId(), false);
                    $scopeProduct->setData('has_view_variants', true);
                    $scopeProduct->setData('generic_product_id', $scopeProduct->getId());
                    $this->client->postProduct('catalog_product_save_after', $scopeProduct, current($storeIds));
                    continue;
                }
                $scopeProduct = $this->productRepository->getById($product->getId(), false, current($storeIds));
                $this->client->postProduct('catalog_product_save_after', $scopeProduct, current($storeIds));
            }
        } catch (\Exception $e) {
            $this->logger->warning(sprintf("Error trying to load product %s", $e->getMessage()));
        }
    }

    /**
     * @param $product Product
     * @return array
     */
    private function getStoreByTrackingId($product)
    {
        $tracks = [];
        foreach ($product->getStoreIds() as $storeId) {
            $trackId = $this->trackScopeManager->getConfig($storeId);
            $tracks[$trackId][] = $storeId;
        }
        return $tracks;
    }
}
