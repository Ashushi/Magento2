<?php

namespace TPB\Accessories\Ui\DataProvider\Product\Related;

use Magento\Catalog\Ui\DataProvider\Product\Related\AbstractDataProvider;

/**
 * Class AccessoriesDataProvider
 */
class AccessoriesDataProvider extends AbstractDataProvider
{
    /**
     * {@inheritdoc
     */
    protected function getLinkType()
    {
        return 'accessories';
    }
}
