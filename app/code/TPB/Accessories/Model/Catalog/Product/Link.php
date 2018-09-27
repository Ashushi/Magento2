<?php

namespace TPB\Accessories\Model\Catalog\Product;

class Link extends \Magento\Catalog\Model\Product\Link
{
    const LINK_TYPE_ACCESSORIES = 7;

    /**
     * @return \Magento\Catalog\Model\Product\Link $this
     */
    public function useAccessoriesLinks()
    {
        $this->setLinkTypeId(self::LINK_TYPE_ACCESSORIES);
        return $this;
    }

    /**
     * Save data for product relations
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product\Link
     */
    public function saveProductRelations($product)
    {
        parent::saveProductRelations($product);

        $data = $product->getAccessoriesData();
        if (!is_null($data)) {
            $this->_getResource()->saveProductLinks($product->getId(), $data, self::LINK_TYPE_ACCESSORIES);
        }
    }
}
