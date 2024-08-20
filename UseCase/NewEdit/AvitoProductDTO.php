<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProductInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class AvitoProductDTO implements AvitoProductInterface
{
    /** ID продукта (не уникальный) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductUid $product;

    /** Константа ТП */
    #[Assert\Uuid]
    private ?ProductOfferConst $offer = null;

    /** Константа множественного варианта */
    #[Assert\Uuid]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[Assert\Uuid]
    private ?ProductModificationConst $modification = null;

    #[Assert\Valid]
    private ArrayCollection $images;

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(ProductUid $product): void
    {
        $this->product = $product;
    }

    public function getOffer(): ?ProductOfferConst
    {
        return $this->offer;
    }

    public function getVariation(): ?ProductVariationConst
    {
        return $this->variation;
    }

    public function getModification(): ?ProductModificationConst
    {
        return $this->modification;
    }

    public function setOffer(?ProductOfferConst $offer): void
    {
        $this->offer = $offer;
    }

    public function setVariation(?ProductVariationConst $variation): void
    {
        $this->variation = $variation;
    }

    public function setModification(?ProductModificationConst $modification): void
    {
        $this->modification = $modification;
    }

}
