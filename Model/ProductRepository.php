<?php

namespace Zaius\Engage\Model;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\ProductRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

/**
 * Class ProductRepository
 * @package Zaius\Engage\Model
 * @api
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var array
     */
    protected static $PRODUCT_ATTRIBUTES_TO_IGNORE = [
        // These fields are suppressed because there's no clear path to utility in Zaius.
        'entity_id', 'attribute_set_id', 'type_id',
        'entity_type_id', 'category_ids', 'required_options',
        'has_options', 'created_at', 'updated_at', 'media_gallery',
        'small_image', 'thumbnail', 'quantity_and_stock_status',
        // The next fields are suppressed because they're explicitly mapped.
        'image', 'special_from_date', 'special_to_date',
    ];

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var ProductInterfaceFactory
     */
    protected $_productFactory;
    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;
    /**
     * @var ProductHelper
     */
    protected $_productHelper;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var
     */
    protected $_extraProductAttributes;

    /**
     * @var Configurable
     */
    private $_productConfigurable;
    /**
     * @var TrackScopeManager
     */
    private $trackScopeManager;

    /**
     * ProductRepository constructor.
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductInterfaceFactory $productFactory
     * @param StockRegistryInterface $stockRegistry
     * @param Configurable $productConfigurable
     * @param ProductHelper $productHelper
     * @param Data $helper
     * @param Logger $logger
     * @param TrackScopeManager $trackScopeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        ProductInterfaceFactory $productFactory,
        StockRegistryInterface $stockRegistry,
        Configurable $productConfigurable,
        ProductHelper $productHelper,
        Data $helper,
        Logger $logger,
        TrackScopeManager $trackScopeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_productConfigurable = $productConfigurable;
        $this->_productHelper = $productHelper;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->trackScopeManager = $trackScopeManager;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $trackingID
     * @return mixed
     */
    public function getList($limit = null, $offset = null, $trackingID = null)
    {
        if ($trackingID === null) {
            return [];
        }
            /** @var ProductCollection $products */
        $products = $this->_productCollectionFactory->create();

        try {
            $storeId = $this->trackScopeManager->getStoreIdByConfigValue($trackingID);
            $products->addStoreFilter($storeId);
        } catch (\Exception $e) {
            return [];
        }

        $products->addAttributeToSelect(['name', 'price', 'special_price', 'special_from_date', 'special_to_date', 'short_description', 'image', 'url_key'])
            ->setOrder('entity_id', 'asc');
        if ($this->_helper->getIsCollectAllProductAttributes($this->_storeManager->getStore())) {
            $products->addAttributeToSelect(array_keys($this->_getExtraProductAttributes()));
        }
        if (isset($limit)) {
            $products->getSelect()->limit($limit, $offset);
        }
        $result = [];
        $suppressions = 0;

        $duplicatedTrackingIds = $this->trackScopeManager->getStoresWithDuplicatedTrackingId();
        /** @var Product $product */
        foreach ($products as $product) {
            if (is_null($product->getId())) {
                $suppressions++;
                $this->_logger->warning('ZAIUS: Product information cannot be null');
                // requested operation, time of API call
                $this->_logger->warning("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
                // missing field
                $this->_logger->warning("ZAIUS: Null field: product_id.");
            } else {

                if (sizeof($product->getStoreIds()) > 1 && array_intersect($product->getStoreIds(), array_keys($duplicatedTrackingIds))) {
                    $storeIds = $product->getStoreIds();
                    $baseProduct = $this->getStoreProduct(Store::DEFAULT_STORE_ID, $product);
                    $result[] = $this->getProductEventData('product', $baseProduct);

                    foreach ($storeIds as $productStoreId) {
                        $storeProduct = $this->getStoreProduct($productStoreId, $product);
                        $store = $this->_storeManager->getStore($productStoreId);
                        if (!$store->getIsActive() || !$this->hasVariants($baseProduct, $storeProduct)) {
                            continue;
                        }

                        if ((int)$productStoreId != Store::DEFAULT_STORE_ID) {
                            $newId = $storeProduct->getId() . '-' . $this->trackScopeManager->getStoreCode($productStoreId);
                            $storeProduct->setData('generic_product_id', $storeProduct->getId());
                            $storeProduct->setData('has_view_variants', true);
                            $storeProduct->setId($newId);
                        }
                        $result[] = $this->getProductEventData('product', $storeProduct);
                    }
                    continue;
                }
                $result[] = $this->getProductEventData('product', $product);
            }
        }
        $this->_logger->info('ZAIUS: Product information fully assembled.');
        // requested operation, time of API call
        $this->_logger->info("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
        // length of response
        $this->_logger->info("ZAIUS: Response Length: " . count($result) . ".");
        // supressed fields
        $this->_logger->info("ZAIUS: Number of suppressions: " . $suppressions . ".");
        return $result;
    }

    /**
     * @param string $event
     * @param Product $product
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getProductEventData($event, $product)
    {
        $productId = $this->_helper->getProductId($product);
        $parentIds = $this->_productConfigurable->getParentIdsByChild($productId);
        $parentProductId = isset($parentIds[0]) ? $parentIds[0] : $productId;

        $productData = [
            'product_id' => $productId,
            'parent_product_id' => $parentProductId,
            'name' => $product->getName(),
            'brand' => $product->getData('brand'),
            'sku' => $product->getSku(),
            'upc' => $product->getData('upc'),
            'description' => $product->getData('short_description'),
            'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
            'price' => trim($product->getPrice()),
            'image_url' => $this->_productHelper->getImageUrl($product),
            'url_key' => $product->getData('url_key')
        ];

        if ($product->getData('special_price')) {
            $productData['special_price'] = trim($product->getData('special_price'));
            $productData['special_price_from_date'] = strtotime($product->getData('special_from_date')) ?: null;
            $productData['special_price_to_date'] = strtotime($product->getData('special_to_date')) ?: null;
        }
        $stockItem = $this->_stockRegistry->getStockItem($product->getId());
        if ($stockItem && $stockItem->getId() && $stockItem->getManageStock()) {
            $productData['qty'] = $stockItem->getQty();
            $productData['is_in_stock'] = $stockItem->getIsInStock();
        }
        foreach ($this->_getExtraProductAttributes() as $attributeCode => $attribute) {
            $productData[$attributeCode] = $attribute->getFrontend()->getValue($product);
        }
        $productData['price'] = preg_replace('/\s+/', '', $productData['price']);
        $productData['zaius_engage_version'] = $this->_helper->getVersion();
        if (!$product->getImage()) {
            $this->_logger->error('ZAIUS: Unable to retrieve product image_url');
        }

        if ($genericProductId = $product->getData('generic_product_id')) {
            $productData['generic_product_id'] = $genericProductId;
        }

        if ($hasVariants = $product->getData('has_view_variants')) {
            $productData['has_view_variants'] = $hasVariants;
        }

        $productData += $this->_helper->getDataSourceFields();
        $this->_logger->info("Event: $event");
        $this->_logger->info("Event: $event " . json_encode($productData['product_id']));
        return $productData;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function _getExtraProductAttributes()
    {
        if (!isset($this->_extraProductAttributes)) {
            $this->_extraProductAttributes = [];
            if ($this->_helper->getIsCollectAllProductAttributes($this->_storeManager->getStore())) {
                $product = $this->_productFactory->create();
                foreach ($product->getAttributes() as $attribute) {
                    $attributeCode = $attribute->getAttributeCode();
                    if (!in_array($attributeCode, self::$PRODUCT_ATTRIBUTES_TO_IGNORE)) {
                        $this->_extraProductAttributes[$attributeCode] = $attribute;
                    }
                }
            }
        }
        return $this->_extraProductAttributes;
    }

    /**
     * @param $productBase
     * @param $productStore
     * @return bool
     */
    private function hasVariants($productBase, $productStore)
    {
        $hasVariants = false;
        $productBaseData = $this->getProductEventData('product', $productBase);
        $productStoreData = $this->getProductEventData('product', $productStore);
        foreach ($productBaseData as $k => $value) {
            if ($k === 'store_id') {
                continue;
            }
            if ($productBaseData[$k] != $productStoreData[$k]) {
                return true;
            }
        }
        return $hasVariants;
    }

    /**
     * @param $productStoreId
     * @param $product
     * @return mixed
     */
    private function getStoreProduct($productStoreId, $product)
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->setStoreId($productStoreId);
        $collection->addAttributeToSelect([
            'name',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'short_description',
            'image',
            'url_key'
        ]);
        $collection->addIdFilter($product->getId());
        $storeProduct = $collection->getFirstItem();
        return $storeProduct;
    }
}
