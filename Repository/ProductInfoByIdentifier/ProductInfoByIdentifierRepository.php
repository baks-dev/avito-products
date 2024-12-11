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

namespace BaksDev\Avito\Products\Repository\ProductInfoByIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use InvalidArgumentException;

final class ProductInfoByIdentifierRepository implements ProductInfoByIdentifierInterface
{
    private ProductUid|false $product = false;

    private ProductOfferConst|false $offerConst = false;

    private ProductVariationConst|false $variationConst = false;

    private ProductModificationConst|false $modificationConst = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /** Идентификатор продукта */
    public function forProduct(Product|ProductUid|string $product): self
    {
        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    /** Уникальная константа Offer */
    public function forOfferConst(ProductOfferConst|string|null $offerConst): self
    {
        if(is_null($offerConst))
        {
            $this->offerConst = false;
            return $this;
        }

        if(is_string($offerConst))
        {
            $offerConst = new ProductOfferConst($offerConst);
        }

        $this->offerConst = $offerConst;

        return $this;
    }

    /** Уникальная константа Variation */
    public function forVariationConst(ProductVariationConst|string|null $variationConst): self
    {
        if(is_null($variationConst))
        {
            $this->variationConst = false;
            return $this;
        }

        if(is_string($variationConst))
        {
            $variationConst = new ProductVariationConst($variationConst);
        }

        $this->variationConst = $variationConst;

        return $this;
    }

    /** Уникальная константа Modification */
    public function forModificationConst(ProductModificationConst|string|null $modificationConst): self
    {
        if(is_null($modificationConst))
        {
            $this->modificationConst = false;
            return $this;
        }

        if(is_string($modificationConst))
        {
            $modificationConst = new ProductModificationConst($modificationConst);
        }

        $this->modificationConst = $modificationConst;

        return $this;
    }

    /**
     * Метод возвращает информацию о продукте по его уникальным идентификаторам:
     * - остаток
     * - артикул
     *
     * @return array{
     *     'product_quantity': int,
     *     'product_article': string
     * }| false
     */
    public function find(): array|false
    {
        if($this->product === false)
        {
            throw new InvalidArgumentException('Не передан обязательный аргумент запроса: product');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(Product::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $this->product, ProductUid::TYPE);

        /** Получаем название с учетом настроек локализации */
        $dbal
            ->leftJoin(
                'product',
                ProductTrans::class,
                'product_trans',
                '
                    product_trans.event = product.event AND
                    product_trans.local = :local',
            );

        /** Основной артикул товара */
        $dbal->join(
            'product',
            ProductInfo::class,
            'product_info',
            'product_info.product = product.id',
        );

        /** Offer */
        if($this->offerConst instanceof ProductOfferConst)
        {
            $dbal
                ->join(
                    'product',
                    ProductOffer::class,
                    'product_offer',
                    '
                        product_offer.event = product.event AND
                        product_offer.const = :offerConst',
                )
                ->setParameter(
                    'offerConst',
                    $this->offerConst,
                    ProductOfferConst::TYPE
                );
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

        /** Variation */
        if($this->variationConst instanceof ProductVariationConst)
        {
            $dbal
                ->join(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    '
                            product_variation.offer = product_offer.id AND
                            product_variation.const = :variationConst',
                )
                ->setParameter(
                    'variationConst',
                    $this->variationConst,
                    ProductVariationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_offer',
                    ProductVariation::class,
                    'product_variation',
                    'product_variation.offer = product_offer.id'
                );
        }

        /** Modification */
        if($this->modificationConst instanceof ProductModificationConst)
        {
            $dbal
                ->join(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    '
                        product_modification.variation = product_variation.id AND 
                        product_modification.const = :modificationConst',
                )
                ->setParameter(
                    'modificationConst',
                    $this->modificationConst,
                    ProductModificationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'product_variation',
                    ProductModification::class,
                    'product_modification',
                    'product_modification.variation = product_variation.id'
                );
        }


        /**
         * Базовая Цена товара
         */
        $dbal
            ->leftJoin(
                'product',
                ProductPrice::class,
                'product_price',
                'product_price.event = product.event'
            )
            ->addGroupBy('product_price.reserve');

        /**
         * Цена торгового предо жения
         */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /**
         * Цена множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );

        /**
         * Цена модификации множественного варианта
         */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id'
        );


        /* Стоимость продукта */

        $dbal->addSelect('
			COALESCE(
                NULLIF(product_modification_price.price, 0), 
                NULLIF(product_variation_price.price, 0), 
                NULLIF(product_offer_price.price, 0), 
                NULLIF(product_price.price, 0),
                0
            ) AS product_price
		');

        /* Предыдущая стоимость продукта */

        $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_price.old, 0),
                NULLIF(product_variation_price.old, 0),
                NULLIF(product_offer_price.old, 0),
                NULLIF(product_price.old, 0),
                0
            ) AS product_old_price
		");


        /**
         * Наличие продукта
         */


        /** Наличие и резерв торгового предложения */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            )
            ->addGroupBy('product_offer_quantity.reserve');

        /** Наличие и резерв множественного варианта */
        $dbal
            ->leftJoin(
                'product_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_variation.id'
            )
            ->addGroupBy('product_variation_quantity.reserve');

        $dbal
            ->leftJoin(
                'product_modification',
                ProductModificationQuantity::class,
                'product_modification_quantity',
                'product_modification_quantity.modification = product_modification.id'
            )
            ->addGroupBy('product_modification_quantity.reserve');

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
			END AS product_quantity'
        );

        /**
         * Артикул продукта
         */
        $dbal->addSelect('
                COALESCE(
                    product_modification.article,
                    product_variation.article,
                    product_offer.article,
                    product_info.article
                ) AS product_article
            ');


        $dbal->allGroupByExclude();

        /** DEBUG */
        //        dd($dbal->fetchAllAssociative());
        //        dd($dbal->analyze());

        $result = $dbal->fetchAssociative();

        return empty($result) ? false : $result;
    }
}
