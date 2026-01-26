<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProductInterface;
use BaksDev\Avito\Products\Type\Id\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\Kit\AvitoProductKitDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\Profile\AvitoProductProfileDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\Token\AvitoProductTokenDTO;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoProduct */
final class AvitoProductDTO implements AvitoProductInterface
{
    #[Assert\Uuid]
    private ?AvitoProductUid $id = null;

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

    /** Идентификатор профиля */
    #[Assert\Valid]
    private AvitoProductTokenDTO $token;

    /** Комплекты */
    #[Assert\Valid]
    private AvitoProductKitDTO $kit;

    /** Шаблон описания */
    private ?string $description = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->token = new AvitoProductTokenDTO();
        $this->kit = new AvitoProductKitDTO();
    }

    public function setId(AvitoProductUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAvitoProductUid(): ?AvitoProductUid
    {
        return $this->id;
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
        if(null === $image->getFile() && null === $image->getName())
        {
            return;
        }

        /** Пропускаем, если форма не содержит изображения, либо изображение уже есть в коллекции */
        $filter = $this->images->filter(function(AvitoProductImagesDTO $current) use ($image) {

            if(null !== $image->getFile())
            {
                return false;
            }

            return $image->getName() === $current->getName();
        });

        if($filter->isEmpty())
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

    public function getToken(): AvitoProductTokenDTO
    {
        return $this->token;
    }

    public function getKit(): AvitoProductKitDTO
    {
        return $this->kit;
    }
}
