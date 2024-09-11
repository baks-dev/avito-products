<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\Repository\AvitoProducts;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;

final class AvitoProductsRepository implements AvitoProductsInterface
{
    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /** Возвращает данные карточки продукта Авито */
    public function findAllByProduct(ProductUid $product): array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal
            ->from(AvitoProduct::class, 'avito_product')
            ->where('avito_product.product = :product')
            ->setParameter('product', $product);

        $dbal->addSelect('avito_product.id as id');
        $dbal->addSelect('avito_product.product as product');
        $dbal->addSelect('avito_product.offer as offer');
        $dbal->addSelect('avito_product.variation as variation');
        $dbal->addSelect('avito_product.modification as modification');

        return $dbal->fetchAllAssociative();
    }

    /**
     * @return true - если по данным карточки продукта Авито продукт НАЙДЕН
     * @return false - если по данным карточки продукта Авито продукт НЕ НАЙДЕН
     */
    public function productExist(array $avitoProduct): bool
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal->from(Product::class, 'product');

        $dbal
            ->where('product.id = :product')
            ->setParameter('product', $avitoProduct['product']);

        /** Получаем активное событие */
        $dbal->join(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

        /**
         * ТОРГОВОЕ ПРЕДЛОЖЕНИЕ
         */
        $dbal
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                "
                    product_offer.event = product_event.id AND
                    product_offer.const = :offer"
            )
            ->setParameter('offer', $avitoProduct['offer']);

        /**
         * ВАРИАНТЫ торгового предложения
         */
        if ($avitoProduct['variation'])
        {
            $dbal
                ->join(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    "
                    product_variation.offer = product_offer.id AND
                    product_variation.const = :variation"
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    "
                    product_variation.offer = product_offer.id AND
                    product_variation.const = :variation"
                );
        }

        $dbal->setParameter('variation', $avitoProduct['variation']);

        /**
         * МОДИФИКАЦИИ множественного варианта
         */
        if ($avitoProduct['modification'])
        {
            $dbal
                ->join(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                    product_modification.variation = product_variation.id AND
                    product_modification.const = :modification                        '
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                    product_modification.variation = product_variation.id AND
                    product_modification.const = :modification                        '
                );
        }

        $dbal->setParameter('modification', $avitoProduct['modification']);

        return $dbal->fetchExist();
    }

    /**
     * Возвращает массив с данными о продукте, если по данным карточки продукта Авито продукт НАЙДЕН
     * Возвращает пустой массив, если по данным карточки продукта Авито продукт НЕ НАЙДЕН
     */
    public function findProductExist(array $avitoProduct): array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal->from(Product::class, 'product');

        $dbal
            ->where('product.id = :product')
            ->setParameter('product', $avitoProduct['product']);

        /** Получаем активное событие */
        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

        /**
         * ТОРГОВОЕ ПРЕДЛОЖЕНИЕ
         */
        $dbal
            ->join(
                'product_event',
                ProductOffer::class,
                'product_offer',
                "
                    product_offer.event = product_event.id AND
                    product_offer.const = :offer"
            )
            ->setParameter('offer', $avitoProduct['offer']);

        /**
         * ВАРИАНТЫ торгового предложения
         */
        if ($avitoProduct['variation'])
        {
            $dbal
                ->join(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    "
                    product_variation.offer = product_offer.id AND
                    product_variation.const = :variation"
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    "
                    product_variation.offer = product_offer.id AND
                    product_variation.const = :variation"
                );
        }

        $dbal->setParameter('variation', $avitoProduct['variation']);

        /**
         * МОДИФИКАЦИИ множественного варианта
         */
        if ($avitoProduct['modification'])
        {
            $dbal
                ->join(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                    product_modification.variation = product_variation.id AND
                    product_modification.const = :modification                        '
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                    product_modification.variation = product_variation.id AND
                    product_modification.const = :modification                        '
                );
        }

        $dbal->setParameter('modification', $avitoProduct['modification']);

        return
            $dbal
                ->select('*')
                ->fetchAllAssociative();
    }
}
