<?php

namespace TPB\Accessories\Model;

class Product extends \Magento\Catalog\Model\Product
{
    /**
     * Retrieve array of custom type products
     *
     * @return array
     */
    public function getAccessoriesProducts() 
    {
        if (!$this->hasAccessoriesProducts()) {
            $products = [];
            foreach ($this->getAccessoriesProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setAccessoriesProducts($products);
        }
        return $this->getData('accessories_products');
    }
    /**
     * Retrieve custom type products identifiers
     *
     * @return array
     */
    public function getAccessoriesIds() 
    {
        if (!$this->hasAccessoriesProductIds()) {
            $ids = [];
            foreach ($this->getAccessoriesProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setAccessoriesProductIds($ids);
        }
        return $this->getData('accessories_product_ids');
    }
    /**
     * Retrieve collection custom type product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getAccessoriesProductCollection() 
    {
        $collection = $this->getLinkInstance()->useAccessoriesLinks()->getProductCollection()->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }
    /**
     * Retrieve collection custom type link
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Collection
     */
    public function getAccessoriesLinkCollection() 
    {
        $collection = $this->getLinkInstance()->useAccessoriesLinks()->getLinkCollection();
        $collection->setProduct($this);
        $collection->addLinkTypeIdFilter();
        $collection->addProductIdFilter();
        $collection->joinAttributes();
        return $collection;
    }
    
}