<?php

namespace BaksDev\Avito\Products\Repository\ProductImages;

interface AllProductImagesInterface
{
    public function findAll(): array|bool;
}
