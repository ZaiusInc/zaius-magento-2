<?php

namespace Zaius\Engage\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Zaius\Engage\Api\ProductRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

/**
 * Class ProductRepository
 * @package Zaius\Engage\Model
 * @api
 */
class ProductRepository
    implements ProductRepositoryInterface
{
    protected static $PRODUCT_ATTRIBUTES_TO_IGNORE = array(
        'entity_id', 'attribute_set_id', 'type_id',
        'entity_type_id', 'category_ids', 'required_options',
        'has_options', 'created_at', 'updated_at', 'media_gallery',
        'image', 'small_image', 'thumbnail', 'quantity_and_stock_status'
    );

    protected $_storeManager;
    protected $_productCollectionFactory;
    protected $_productFactory;
    protected $_stockRegistry;
    protected $_productHelper;
    protected $_helper;
    protected $_logger;
    protected $_extraProductAttributes;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        ProductInterfaceFactory $productFactory,
        StockRegistryInterface $stockRegistry,
        ProductHelper $productHelper,
        Data $helper,
        Logger $logger
    )
    {
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_productHelper = $productHelper;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList($limit = null, $offset = null)
    {
        /** @var ProductCollection $products */
        $products = $this->_productCollectionFactory->create();
        $products->addAttributeToSelect(['name', 'price', 'special_price', 'special_price_from_date', 'special_price_to_date', 'short_description', 'image'])
            ->setOrder('entity_id', 'asc');
        if ($this->_helper->getIsCollectAllProductAttributes($this->_storeManager->getStore())) {
            $products->addAttributeToSelect(array_keys($this->_getExtraProductAttributes()));
        }
        if (isset($limit)) {
            $products->getSelect()->limit($limit, $offset);
        }
        $result = [];
        $suppressions = 0;
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
                $result[] = $this->getProductEventData('product',$product);
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
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductEventData($event, $product)
    {
        $productData = [
            'product_id' => $this->_helper->getProductId($product),
            'name' => $product->getName(),
            'brand' => $product->getData('brand'),
            'sku' => $product->getSku(),
            'upc' => $product->getData('upc'),
            'description' => $product->getData('short_description'),
            'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
            'price' => $product->getPrice(),
            'image_url' => $this->_productHelper->getImageUrl($product)
        ];
        if ($product->getData('special_price')) {
            $productData['special_price'] = $product->getData('special_price');
            $productData['special_price_from_date'] = $product->getData('special_price_from_date');
            $productData['special_price_to_date'] = $product->getData('special_price_to_date');
        }
        $stockItem = $this->_stockRegistry->getStockItem($product->getId());
        if ($stockItem && $stockItem->getId() && $stockItem->getManageStock()) {
            $productData['qty'] = $stockItem->getQty();
            $productData['is_in_stock'] = $stockItem->getIsInStock();
        }
        foreach ($this->_getExtraProductAttributes() as $attributeCode => $attribute) {
            $productData[$attributeCode] = $attribute->getFrontend()->getValue($product);
        }
        $productData['zaius_engage_version'] = $this->_helper->getVersion();
        if (!$product->getImage()) {
            $this->_logger->error('ZAIUS: Unable to retrieve product image_url');
        }
        $this->_logger->info("Event: $event " . json_encode($productData['product_id']));
        return $productData;
    }

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
}
