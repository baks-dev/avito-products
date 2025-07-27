<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Avito\Products\Repository\AllAvitoProducts;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;

final class AllAvitoProductsRepository implements AllAvitoProductsInterface
{
    private ProductUid|false $product = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function product(Product|ProductUid|string $product): self
    {
        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        $this->product = $product;

        return $this;
    }

    /**
     * Возвращает массив с данными карточек продукта Авито
     */
    public function execute(): array|false
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal->from(AvitoProduct::class, 'avito_product');

        if($this->product !== false)
        {
            $dbal
                ->where('avito_product.product = :product')
                ->setParameter('product', $this->product, ProductUid::TYPE);
        }

        $dbal->addSelect('avito_product.id as id');
        $dbal->addSelect('avito_product.product as product');
        $dbal->addSelect('avito_product.offer as offer');
        $dbal->addSelect('avito_product.variation as variation');
        $dbal->addSelect('avito_product.modification as modification');

        $result = $dbal->fetchAllAssociative();

        if(empty($result))
        {
            return false;
        }

        return $result;
    }
}
