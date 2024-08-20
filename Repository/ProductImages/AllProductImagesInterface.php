<?php

namespace BaksDev\Avito\Products\Repository\ProductImages;

use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;

interface AllProductImagesInterface
{
    public function filter(ProductFilterDTO $filter): self;

    public function findAll(): PaginatorInterface;
}
