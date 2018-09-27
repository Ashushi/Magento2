<?php

namespace TPB\Accessories\Model\Catalog\Product\Link;

class Proxy extends \Magento\Catalog\Model\Product\Link\Proxy
{
    /**
     * {@inheritdoc}
     */
    public function useAccessoriesLinks()
    {
        return $this->_getSubject()->useAccessoriesLinks();
    }
}