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

namespace BaksDev\Avito\Products\Repository\AllProductsWithAvitoImage;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;

final class AllProductsWithAvitoImagesRepository implements AllProductsWithAvitoImagesInterface
{
    private ?ProductFilterDTO $filter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function filter(ProductFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function findAll(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Product::class, 'product');

        $dbal->join(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

        // @TODO нужен ли join на активные продукты?
        /** Только активные продукты */
        $dbal
            ->join(
                'product',
                ProductActive::class,
                'product_active',
                '
                    product_active.event = product.event AND
                    product_active.active IS TRUE'
            );

        /** Название категории */
        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                '
                    product_trans.event = product_event.id AND 
                    product_trans.local = :local'
            );

        /** Здесь основной артикул товара */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id'
            );

        /**
         * ТОРГОВОЕ ПРЕДЛОЖЕНИЕ
         */
        $dbal
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.const as product_offer_const')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id'
            );

        /** ФИЛЬТР по торговому предложения */
        if ($this->filter?->getOffer())
        {
            $dbal->andWhere('product_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }

        /** ЦЕНА торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /** ТИП торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /**
         * МНОЖЕСТВЕННЫЕ ВАРИАНТЫ торгового предложения
         */
        $dbal
            ->addSelect('product_variation.id as product_variation_id')
            ->addSelect('product_variation.const as product_variation_const')
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id'
            );

        /** ФИЛЬТР по множественным вариантам */
        if ($this->filter?->getVariation())
        {
            $dbal->andWhere('product_offer_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }

        /** ЦЕНА множественных вариантов */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );

        /** ТИП множественного варианта торгового предложения */
        $dbal
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_variation.category_variation'
            );

        /**
         * МОДИФИКАЦИИ множественного варианта
         */
        $dbal
            ->addSelect('product_modification.id as product_modification_id')
            ->addSelect('product_modification.const as product_modification_const')
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id '
            );

        /** ФИЛЬТР по модификациям множественного варианта */
        if ($this->filter?->getModification())
        {
            $dbal->andWhere('product_modification.value = :modification');
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        /** ЦЕНА модификации множественного варианта */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id'
        );

        /** ТИП модификации множественного варианта */
        $dbal->addSelect('category_offer_modification.reference as product_modification_reference');
        $dbal->leftJoin(
            'product_modification',
            CategoryProductModification::class,
            'category_offer_modification',
            'category_offer_modification.id = product_modification.category_modification'
        );

        /**
         * @TODO подумать над ее использованием
         * Уникальная константа
         */
        $dbal->addSelect(
            '
                CASE
                    WHEN product_modification.const IS NOT NULL THEN product_modification.const
                    WHEN product_variation.const IS NOT NULL THEN product_variation.const
                    WHEN product_offer.const IS NOT NULL THEN product_offer.const
                    ELSE NULL
                END AS product_const'
        );

        /**
         * Артикул продукта
         */
        $dbal->addSelect(
            '
        	    CASE
        		    WHEN product_modification.article IS NOT NULL THEN product_modification.article
        			WHEN product_variation.article IS NOT NULL THEN product_variation.article
        			WHEN product_offer.article IS NOT NULL THEN product_offer.article
        			WHEN product_info.article IS NOT NULL THEN product_info.article
        			ELSE NULL
        		END AS product_article'
        );

        /**
         * Категория
         */
        $dbal
            ->join(
                'product_event',
                ProductCategory::class,
                'product_category',
                '
                    product_category.event = product_event.id AND 
                    product_category.root = true'
            );

        $dbal->join(
            'product_category',
            CategoryProduct::class,
            'category',
            'category.id = product_category.category'
        );

        // @TODO нужен ли join на активные разделы?
        /** Только активные разделы */
        $dbal
            ->addSelect('category_info.active as category_active')
            ->join(
                'product_category',
                CategoryProductInfo::class,
                'category_info',
                '
                category.event = category_info.event AND
                category_info.active IS TRUE'
            );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                '
                    category_trans.event = category.event AND 
                    category_trans.local = :local'
            );

              /**
         * Все фото
         */
        /** Фото продукта */
        //        $dbal->leftJoin(
        //            'product',
        //            ProductPhoto::class,
        //            'product_photo',
        //            'product_photo.event = product.event'
        //        );

        /** Фото торговых предложений */
        //        $dbal->leftJoin(
        //            'product_offer',
        //            ProductOfferImage::class,
        //            'product_offer_images',
        //            'product_offer_images.offer = product_offer.id'
        //        );

        /** Фото вариантов */
        //        $dbal->leftJoin(
        //            'product_offer',
        //            ProductVariationImage::class,
        //            'product_variation_image',
        //            'product_variation_image.variation = product_offer_variation.id'
        //        );

        /** Фото модификаций */
        //        $dbal
        //            ->leftJoin(
        //                'product_modification',
        //                ProductModificationImage::class,
        //                'product_modification_image',
        //                'product_modification_image.modification = product_modification.id'
        //            );

        /** Идентификатор фото */
        //        $dbal
        //            ->addSelect('product_photo.id as product_photo')
        //            ->addSelect('product_offer_images.id as offer_photo')
        //            ->addSelect('product_variation_image.id as variation_photo')
        //            ->addSelect('product_modification_image.id as modification_photo');

//                dd($dbal
//                    ->setMaxResults(50)
//                    ->fetchAllAssociative());


        return $this->paginator->fetchAllAssociative($dbal);
    }
}
