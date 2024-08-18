<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\Repository\ProductImages;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Product;

final class AllProductImages implements AllProductImagesInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function findAll(): array|bool
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Product::class, 'product');

        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

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

        /** Здесь артикул товара */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id'
            );


        /** Торговое предложение */
        $dbal
//            ->addSelect('product_offer.value as product_offer_value')
//            ->addSelect('product_offer.const as product_offer_const')
//            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id'
            );

        /** Цена торгового предложения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /** Тип торгового предложения */
        ////        $dbal
        ////            ->addSelect('category_offer.reference as product_offer_reference')
        ////            ->leftJoin(
        ////                'product_offer',
        ////                CategoryProductOffers::class,
        ////                'category_offer',
        ////                'category_offer.id = product_offer.category_offer'
        ////            );

        /** Множественные варианты торгового предложения */
        $dbal
//            ->addSelect('product_offer_variation.value as product_variation_value')
//            ->addSelect('product_offer_variation.const as product_variation_const')
//            ->addSelect('product_offer_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_offer_variation',
                'product_offer_variation.offer = product_offer.id'
            );

        /** Цена множественного варианта */
        $dbal->leftJoin(
            'product_offer_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_offer_variation.id'
        );

        /** Тип множественного варианта торгового предложения */
        //        $dbal->addSelect('category_offer_variation.reference as product_variation_reference');
        //        $dbal->leftJoin(
        //            'product_offer_variation',
        //            CategoryProductVariation::TABLE,
        //            'category_offer_variation',
        //            'category_offer_variation.id = product_offer_variation.category_variation'
        //        );

        /** Модификация множественного варианта */
        $dbal
            //            ->addSelect('product_offer_modification.value as product_modification_value')
            //            ->addSelect('product_offer_modification.const as product_modification_const')
            //            ->addSelect('product_offer_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_offer_variation',
                ProductModification::class,
                'product_offer_modification',
                'product_offer_modification.variation = product_offer_variation.id '
            );


        /** Артикул продукта */
        $dbal->addSelect(
            '
        	    CASE
        		    WHEN product_offer_modification.article IS NOT NULL THEN product_offer_modification.article
        			WHEN product_offer_variation.article IS NOT NULL THEN product_offer_variation.article
        			WHEN product_offer.article IS NOT NULL THEN product_offer.article
        			WHEN product_info.article IS NOT NULL THEN product_info.article
        			ELSE NULL
        		END AS product_article'
        );

        /** Фото продукции*/
        /** Фото продукта */
        $dbal->leftJoin(
            'product',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product.event'
        );

        /** Фото торговых предложений */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id'
        );

        /** Фото вариантов */
        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_offer_variation.id'
        );

        /** Фото модификаций */
        $dbal
            ->leftJoin(
                'product_offer_modification',
                ProductModificationImage::class,
                'product_modification_image',
                'product_modification_image.modification = product_offer_modification.id'
            );

        $dbal
            ->addSelect('product_photo.id as product_id')
            ->addSelect('product_offer_images.id as offer_id')
            ->addSelect('product_variation_image.id as variation_id')
            ->addSelect('product_modification_image.id as modification_id');

        /** Артикул продукта */
        $dbal->addSelect(
            '
                CASE
        		    WHEN product_offer_modification.const IS NOT NULL THEN product_offer_modification.const
        		    WHEN product_offer_variation.const IS NOT NULL THEN product_offer_variation.const
                    WHEN product_offer.const IS NOT NULL THEN product_offer.const
        			ELSE NULL
        		END AS product_offer'
        );

        dd($dbal->fetchAllAssociative());


        return $dbal
            // ->enableCache('Namespace', 3600)
            ->fetchAllAssociative();
    }
}
