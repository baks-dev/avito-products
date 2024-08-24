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

namespace BaksDev\Avito\Products\Repository\OneProductWithAvitoImages;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Category\Entity\Section\CategoryProductSection;
use BaksDev\Products\Category\Entity\Section\Field\CategoryProductSectionField;
use BaksDev\Products\Category\Entity\Section\Field\Trans\CategoryProductSectionFieldTrans;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;

final class OneProductWithAvitoImagesRepository implements OneProductWithAvitoImagesInterface
{
    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /**
     * Метод возвращает детальную информацию о продукте по его неизменяемым идентификаторам Const ТП, вариантов и модификаций.
     *
     * @param ProductOfferConst $offer - значение торгового предложения
     * @param ProductVariationConst|null $variation - значение множественного варианта ТП
     * @param ProductModificationConst|null $modification - значение модификации множественного варианта ТП
     */
    public function findBy(
        ProductUid $product,
        ProductOfferConst $offer,
        ?ProductVariationConst $variation = null,
        ?ProductModificationConst $modification = null,
    ): array|bool {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Product::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $product, ProductUid::TYPE);

        $dbal
            ->addSelect('product_active.active')
//            ->addSelect('product_active.active_from')
//            ->addSelect('product_active.active_to')
            ->leftJoin(
                'product',
                ProductActive::class,
                'product_active',
                'product_active.event = product.event'
            );

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product.event AND product_trans.local = :local'
            );

        /* Базовая Цена товара */
        $dbal->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event'
        )
            ->addGroupBy('product_price.price')
            ->addGroupBy('product_price.currency')
            ->addGroupBy('product_price.quantity')
            ->addGroupBy('product_price.reserve');

        /* Базовый артикул продукта */
        $dbal
            ->addSelect('product_info.url AS product_url')
            ->leftJoin(
                'product',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id '
            )
            ->addGroupBy('product_info.article');

        /* Торговое предложение */
        if ($offer)
        {
            $dbal
                ->join(
                    'product',
                    ProductOffer::class,
                    'product_offer',
                    '
                        product_offer.event = product.event AND 
                        product_offer.const = :product_offer_const'
                )
                ->setParameter('product_offer_const', $offer);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product',
                    ProductOffer::class,
                    'product_offer',
                    'product_offer.event = product.event'
                );
        }

        $dbal
            ->addSelect('product_offer.id as product_offer_uid')
            ->addSelect('product_offer.const as product_offer_const')
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->addGroupBy('product_offer.article');

        /* Цена торгового предположения */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferPrice::class,
                'product_offer_price',
                'product_offer_price.offer = product_offer.id'
            )
            ->addGroupBy('product_offer_price.price')
            ->addGroupBy('product_offer_price.currency');

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->addSelect('category_offer_trans.postfix as product_offer_name_postfix')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /* Наличие и резерв торгового предложения */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            )
            ->addGroupBy('product_offer_quantity.quantity')
            ->addGroupBy('product_offer_quantity.reserve');

        /* Множественные варианты торгового предложения */
        if ($variation)
        {
            $dbal
                ->join(
                    'product_offer',
                    ProductVariation::class,
                    'product_offer_variation',
                    '
                        product_offer_variation.offer = product_offer.id AND 
                        product_offer_variation.const = :product_variation_const'
                )
                ->setParameter('product_variation_const', $variation);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_offer_variation',
                    'product_offer_variation.offer = product_offer.id'
                );
        }

        $dbal
            ->addSelect('product_offer_variation.id as product_variation_uid')
            ->addSelect('product_offer_variation.const as product_variation_const')
            ->addSelect('product_offer_variation.value as product_variation_value')
            ->addSelect('product_offer_variation.postfix as product_variation_postfix')
            ->addGroupBy('product_offer_variation.article');

        /* Цена множественного варианта */
        $dbal
            ->leftJoin(
                'product_offer_variation',
                ProductVariationPrice::class,
                'product_variation_price',
                'product_variation_price.variation = product_offer_variation.id'
            )
            ->addGroupBy('product_variation_price.price')
            ->addGroupBy('product_variation_price.currency');

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_offer_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_offer_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_offer_variation_trans.name as product_variation_name')
            ->addSelect('category_offer_variation_trans.postfix as product_variation_name_postfix')
            ->leftJoin(
                'category_offer_variation',
                CategoryProductVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );

        /* Наличие и резерв множественного варианта */
        $dbal
            ->leftJoin(
                'category_offer_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_offer_variation.id'
            )
            ->addGroupBy('product_variation_quantity.quantity')
            ->addGroupBy('product_variation_quantity.reserve');

        /* Модификация множественного варианта торгового предложения */
        if ($modification)
        {
            $dbal
                ->join(
                    'product_offer_variation',
                    ProductModification::class,
                    'product_offer_modification',
                    '   
                        product_offer_modification.variation = product_offer_variation.id AND 
                        product_offer_modification.const = :product_modification_const'
                )->setParameter('product_modification_const', $modification);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer_variation',
                    ProductModification::class,
                    'product_offer_modification',
                    'product_offer_modification.variation = product_offer_variation.id'
                );
        }

        $dbal
            ->addSelect('product_offer_modification.id as product_modification_uid')
            ->addSelect('product_offer_modification.const as product_modification_conts')
            ->addSelect('product_offer_modification.value as product_modification_value')
            ->addSelect('product_offer_modification.postfix as product_modification_postfix')
            ->addGroupBy('product_offer_modification.article');

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'product_offer_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_offer_modification.id'
        )
            ->addGroupBy('product_modification_price.price')
            ->addGroupBy('product_modification_price.currency');

        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_offer_modification',
                CategoryProductModification::class,
                'category_offer_modification',
                'category_offer_modification.id = product_offer_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_offer_modification_trans.name as product_modification_name')
            ->addSelect('category_offer_modification_trans.postfix as product_modification_name_postfix')
            ->leftJoin(
                'category_offer_modification',
                CategoryProductModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
            );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_offer_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_offer_modification.id'
        )
            ->addGroupBy('product_modification_quantity.quantity')
            ->addGroupBy('product_modification_quantity.reserve');

        /* Артикул продукта */
        $dbal->addSelect(
            '
			CASE
			   WHEN product_offer_modification.article IS NOT NULL 
			   THEN product_offer_modification.article
			   
			   WHEN product_offer_variation.article IS NOT NULL 
			   THEN product_offer_variation.article
			   
			   WHEN product_offer.article IS NOT NULL 
			   THEN product_offer.article
			   
			   WHEN product_info.article IS NOT NULL 
			   THEN product_info.article
			   
			   ELSE NULL
			END AS product_article
		'
        );

        /* Фото продукта */
        $dbal->leftJoin(
            'product',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product.event AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_offer_variation_image',
            'product_offer_variation_image.variation = product_offer_variation.id AND product_offer_variation_image.root = true'
        )
            ->addGroupBy('product_offer_variation_image.name')
            ->addGroupBy('product_offer_variation_image.ext')
            ->addGroupBy('product_offer_variation_image.cdn')
            ->addGroupBy('product_offer_images.name')
            ->addGroupBy('product_offer_images.ext')
            ->addGroupBy('product_offer_images.cdn')
            ->addGroupBy('product_photo.name')
            ->addGroupBy('product_photo.ext')
            ->addGroupBy('product_photo.cdn');

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/" . $dbal->table(ProductVariationImage::class) . "' , '/', product_offer_variation_image.name)
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/" . $dbal->table(ProductOfferImage::class) . "' , '/', product_offer_images.name)
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/" . $dbal->table(ProductPhoto::class) . "' , '/', product_photo.name)
					
			   ELSE NULL
			END AS product_image
		"
        );

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL 
			   THEN product_offer_variation_image.ext
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
					
			   ELSE NULL
			END AS product_image_ext
		');

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL 
			   THEN product_offer_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
					
			   ELSE NULL
			END AS product_image_cdn
		');

        /* Наличие продукта */

        $dbal->addSelect(
            '
			CASE
			
			   WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve 
			   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)

			   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve  
			   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
			
			   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve 
			   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)

			   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve 
			   THEN (product_price.quantity - product_price.reserve)

			   ELSE 0
			   
			END AS product_quantity
		'
        );

        /* Категория */
        $dbal->join(
            'product',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product.event AND product_event_category.root = true'
        );

        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal->leftJoin(
            'category',
            CategoryProductSection::class,
            'category_section',
            'category_section.event = category.event'
        );

        /* Свойства, участвующие в карточке */
        $dbal->leftJoin(
            'category_section',
            CategoryProductSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.public = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryProductSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        $dbal->leftJoin(
            'category_section_field',
            ProductProperty::class,
            'product_property',
            'product_property.event = product.event AND product_property.field = category_section_field.const'
        );

        $dbal->addSelect(
            "JSON_AGG (DISTINCT
              
                    JSONB_BUILD_OBJECT
                    (
                        '0', category_section_field.sort, /* сортировка  */
                    
                        'field_uid', category_section_field.id,
                        'field_const', category_section_field.const,
                        'field_name', category_section_field.name,
                        'field_alternative', category_section_field.alternative,
                        'field_public', category_section_field.public,
                        'field_card', category_section_field.card,
                        'field_type', category_section_field.type,
                        'field_trans', category_section_field_trans.name,
                        'field_value', product_property.value
                    )
            )
			AS category_section_field"
        );

        $dbal->addSelect(
            '
                CASE
                    WHEN product_offer_modification.id IS NOT NULL THEN product_offer_modification.id
                    WHEN product_offer_variation.const IS NOT NULL THEN product_offer_variation.const
                    WHEN product_offer.const IS NOT NULL THEN product_offer.const
                    ELSE NULL
                END AS avito_product_const'
        );

        $from = null;
        $condition = null;

        if (isset($offer))
        {
            $from = 'product_offer';
            $condition = 'avito_product.offer = product_offer.const';
        }

        if (isset($variation))
        {
            $from = 'product_offer_variation';
            $condition = 'avito_product.variation = product_offer_variation.const';
        }

        if (isset($modification))
        {
            $from = 'product_offer_modification';
            $condition = 'avito_product.modification = product_offer_modification.const';
        }

        $dbal->leftJoin(
            $from,
            AvitoProduct::class,
            'avito_product',
            $condition
        );

        $dbal->leftJoin(
            'avito_product',
            AvitoProductImage::class,
            'avito_product_images',
            'avito_product_images.avito = avito_product.id'
        );

        $dbal->addSelect(
            "
            JSON_AGG
                (DISTINCT
                    CASE
                        WHEN avito_product_images.ext IS NOT NULL 
                        THEN JSONB_BUILD_OBJECT
                        (
                            'avito_img_root', avito_product_images.root,
                            'avito_img_root', avito_product_images.root,
                            'avito_img', CONCAT ( '/upload/" . $dbal->table(AvitoProductImage::class) . "' , '/', avito_product_images.name),
                            'avito_img_ext', avito_product_images.ext,
                            'avito_img_cdn', avito_product_images.cdn
                        )
                    END)
                AS avito_product_images"
        );

        $dbal->allGroupByExclude();

        return $dbal
//            ->enableCache('avito-products')
            ->fetchAssociative();
    }
}
