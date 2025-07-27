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

namespace BaksDev\Avito\Products\Repository\ExistProductByAvito;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use InvalidArgumentException;

final class ExistProductByAvitoRepository implements ExistProductByAvitoProductInterface
{
    private ProductUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

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

    public function offerConst(ProductOffer|ProductOfferConst|string|null $offer): self
    {
        if(is_null($offer))
        {
            return $this;
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getConst();
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(ProductVariation|ProductVariationConst|string|null $variation): self
    {
        if(is_null($variation))
        {
            return $this;
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getConst();
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }

        $this->variation = $variation;

        return $this;
    }

    public function modificationConst(ProductModification|ProductModificationConst|string|null $modification): self
    {
        if(is_null($modification))
        {
            return $this;
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getConst();
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->modification = $modification;

        return $this;
    }

    /**
     * @return true - если по данным карточки продукта Авито продукт НАЙДЕН
     * @return false - если по данным карточки продукта Авито продукт НЕ НАЙДЕН
     */
    public function execute(): bool
    {
        if($this->product === false)
        {
            throw new InvalidArgumentException('Invalid Argument product');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        $dbal->from(Product::class, 'product');

        $dbal
            ->where('product.id = :product')
            ->setParameter('product', $this->product, ProductUid::TYPE);


        if(false === $this->offer)
        {
            return $dbal->fetchExist();
        }

        /**
         * ТОРГОВОЕ предложение
         */
        $dbal
            ->join(
                'product',
                ProductOffer::class,
                'product_offer',
                '
                        product_offer.event = product.event AND
                        product_offer.const = :offer'
            );

        $dbal->setParameter('offer', $this->offer, ProductOfferConst::TYPE);


        if(false === $this->variation)
        {
            return $dbal->fetchExist();
        }

        /**
         * ВАРИАНТЫ торгового предложения
         */

        $dbal
            ->join(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                '
                            product_variation.offer = product_offer.id AND
                            product_variation.const = :variation'
            );

        $dbal->setParameter('variation', $this->variation, ProductVariationConst::TYPE);


        if(false === $this->modification)
        {
            return $dbal->fetchExist();
        }


        /**
         * МОДИФИКАЦИИ множественного варианта торгового предложения
         */
        $dbal
            ->join(
                'product_variation',
                ProductModification::class,
                'product_modification',
                '
                                product_modification.variation = product_variation.id AND
                                product_modification.const = :modification                        '
            );

        $dbal->setParameter('modification', $this->modification, ProductModificationConst::TYPE);

        return $dbal->fetchExist();
    }
}
