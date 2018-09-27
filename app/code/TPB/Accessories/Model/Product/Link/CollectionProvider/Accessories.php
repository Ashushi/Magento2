<?php

namespace TPB\Accessories\Model\Product\Link\CollectionProvider;

class Accessories implements \Magento\Catalog\Model\ProductLink\CollectionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLinkedProducts(\Magento\Catalog\Model\Product $product)
    {
        $products = $product->getAccessoriesProducts();

        if (!isset($products)) {
            return [];
        }

        return $products;
    }
}