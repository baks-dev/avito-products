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

namespace BaksDev\Avito\Products\Repository\AvitoProductProfile;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Kit\AvitoProductKit;
use BaksDev\Avito\Products\Entity\Profile\AvitoProductProfile;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;


final class AvitoProductProfileRepository implements AvitoProductProfileInterface
{
    private ProductUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

    private UserProfileUid|false $profile;

    private int $kit = 1;

    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    public function product(ProductUid|Product $product): self
    {

        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function offerConst(ProductOfferConst|ProductOffer|false|null $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getConst();
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(ProductVariationConst|ProductVariation|false|null $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getConst();
        }

        $this->variation = $variation;

        return $this;
    }

    public function modificationConst(ProductModificationConst|ProductModification|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getConst();
        }

        $this->modification = $modification;

        return $this;
    }

    public function kit(int|string|null|false $kit): self
    {
        if(empty($kit))
        {
            $this->kit = 1;
            return $this;
        }

        $this->kit = (int) $kit;

        return $this;
    }

    public function forProfile(UserProfileUid|UserProfile $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }


    /**
     * Метод возвращает объект сущности AvitoProduct
     */
    public function find(): AvitoProduct|false
    {
        if(false === ($this->product instanceof ProductUid))
        {
            throw new InvalidArgumentException('Invalid Argument Product');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('avito')
            ->from(AvitoProduct::class, 'avito')
            ->where('avito.product = :product')
            ->setParameter(
                key: 'product',
                value: $this->product,
                type: ProductUid::TYPE,
            );

        $orm
            ->join(
                AvitoProductKit::class,
                'kit',
                'WITH',
                'kit.avito = avito.id AND kit.value = :kit',
            )
            ->setParameter(
                key: 'kit',
                value: $this->kit,
            );

        $orm
            ->join(
                AvitoProductProfile::class,
                'profile',
                'WITH',
                'profile.avito = avito.id AND profile.value = :profile',
            )
            ->setParameter(
                key: 'profile',
                value: $this->profile ?: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE,
            );


        if(true === ($this->offer instanceof ProductOfferConst))
        {
            $orm
                ->andWhere('avito.offer = :offer')
                ->setParameter(
                    key: 'offer',
                    value: $this->offer,
                    type: ProductOfferConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('avito.offer IS NULL');
        }


        if(true === ($this->variation instanceof ProductVariationConst))
        {
            $orm
                ->andWhere('avito.variation = :variation')
                ->setParameter(
                    key: 'variation',
                    value: $this->variation,
                    type: ProductVariationConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('avito.variation IS NULL');
        }

        if(true === ($this->modification instanceof ProductModificationConst))
        {
            $orm
                ->andWhere('avito.modification = :modification')
                ->setParameter(
                    key: 'modification',
                    value: $this->modification,
                    type: ProductModificationConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('avito.modification IS NULL');
        }


        return $orm->getOneOrNullResult() ?: false;
    }
}