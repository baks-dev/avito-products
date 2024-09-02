<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProductInterface;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoProduct */
final class AvitoProductDTO implements AvitoProductInterface
{
    /** ID продукта (не уникальный) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductUid $product;

    /** Константа ТП */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductOfferConst $offer;

    /** Константа множественного варианта */
    #[Assert\Uuid]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[Assert\Uuid]
    private ?ProductModificationConst $modification = null;

    /**
     * Коллекция "живых" изображений продукта
     *
     * @var ArrayCollection<int, AvitoProductImagesDTO> $images
     */
    #[Assert\Valid]
    private ArrayCollection $images;

    /** Шаблон описания */
    private ?string $description = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(ProductUid $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getOffer(): ?ProductOfferConst
    {
        return $this->offer;
    }

    public function setOffer(?ProductOfferConst $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function getVariation(): ?ProductVariationConst
    {
        return $this->variation;
    }

    public function setVariation(?ProductVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function getModification(): ?ProductModificationConst
    {
        return $this->modification;
    }

    public function setModification(?ProductModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

    /**
     * @return ArrayCollection<int, AvitoProductImagesDTO>
     */
    public function getImages(): ArrayCollection
    {
        return $this->images;
    }

    public function addImage(AvitoProductImagesDTO $image): void
    {

        /** Пропускаем, если форма не содержит изображения и изображение изображению не присвоено имя */
        if (null === $image->getFile() && null === $image->getName())
        {
            return;
        }

        /** Пропускаем, если форма не содержит изображения, либо изображение уже есть в коллекции */
        $filter = $this->images->filter(function (AvitoProductImagesDTO $current) use ($image) {

            if (null !== $image->getFile())
            {
                return false;
            }

            return $image->getName() === $current->getName();
        });

        if ($filter->isEmpty())
        {
            $this->images->add($image);
        }
    }

    public function removeImage(AvitoProductImagesDTO $image): void
    {
        $this->images->removeElement($image);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
