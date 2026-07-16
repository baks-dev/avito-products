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

namespace BaksDev\Avito\Products\Messenger\Product\SalesMultiselect\Handler\Products;

use BaksDev\Avito\Products\Entity\AvitoProductInterface;
use BaksDev\Avito\Type\Id\AvitoTokenUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoProduct */
final class SalesMultiselectAvitoProductDTO implements AvitoProductInterface
{
    /** Значение свойства */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?AvitoTokenUid $token = null;

    /** ID продукта (не уникальный) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?ProductUid $product = null;

    /** Константа ТП */
    #[Assert\Uuid]
    private ?ProductOfferConst $offer = null;

    /** Константа множественного варианта */
    #[Assert\Uuid]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[Assert\Uuid]
    private ?ProductModificationConst $modification = null;

    /** Значение свойства */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private ?int $kit = 1;

    public function getToken(): ?AvitoTokenUid
    {
        return $this->token;
    }

    public function setToken(?AvitoTokenUid $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getProduct(): ?ProductUid
    {
        return $this->product;
    }

    public function setProduct(?ProductUid $product): self
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

    public function getKit(): ?int
    {
        return $this->kit;
    }

    public function setKit(?int $kit): self
    {
        $this->kit = $kit;
        return $this;
    }
}
