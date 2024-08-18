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

namespace BaksDev\Avito\Products\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Files\Resources\Upload\UploadEntityInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* ProductLiveImage */

#[ORM\Entity]
#[ORM\Table(name: 'product_live_image')]
#[ORM\Index(columns: ['root'])]
class ProductLiveImage extends EntityEvent implements UploadEntityInterface
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductLiveImageUid::TYPE)]
    private ProductLiveImageUid $id;

    /**
     * Идентификатор ProductLiveImage
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductLiveImageUid::TYPE, nullable: false)]
    private ?ProductLiveImageUid $main = null;

    /** ID продукта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductUid::TYPE)]
    private ProductUid $product;

    /** Постоянный уникальный идентификатор ТП */
    #[ORM\Column(type: ProductOfferConst::TYPE, nullable: true)]
    private ?ProductOfferConst $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[ORM\Column(type: ProductVariationConst::TYPE, nullable: true)]
    private ?ProductVariationConst $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[ORM\Column(type: ProductModificationConst::TYPE, nullable: true)]
    private ?ProductModificationConst $modification;

    /** One To One */
    //#[ORM\OneToOne(mappedBy: 'event', targetEntity: ProductLiveImageLogo::class, cascade: ['all'])]
    //private ?ProductLiveImageOne $one = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: ProductLiveImageModify::class, cascade: ['all'])]
    private ProductLiveImageModify $modify;

    /**
     * Переводы
     */
    //#[ORM\OneToMany(mappedBy: 'event', targetEntity: ProductLiveImageTrans::class, cascade: ['all'])]
    //private Collection $translate;


    public function __construct()
    {
        $this->id = new ProductLiveImageUid();
        $this->modify = new ProductLiveImageModify($this);

    }

    /**
     * Идентификатор события
     */

    public function __clone()
    {
        $this->id = clone new ProductLiveImageUid();
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getId(): ProductLiveImageUid
    {
        return $this->id;
    }

    /**
     * Идентификатор ProductLiveImage
     */
    public function setMain(ProductLiveImageUid|ProductLiveImage $main): void
    {
        $this->main = $main instanceof ProductLiveImage ? $main->getId() : $main;
    }

    public function getMain(): ?ProductLiveImageUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof ProductLiveImageInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof ProductLiveImageInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}
