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

namespace BaksDev\Avito\Products\Messenger\ProductStocks;

use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final readonly class UpdateAvitoProductStockMessage
{
    private string $profile;

    private string $product;

    private string|null $offer;

    private string|null $variation;

    private string|null $modification;

    public function __construct(
        UserProfileUid|string $profile,
        ProductUid|string $product,
        ProductOfferConst|string|null $offer,
        ProductVariationConst|string|null $variation,
        ProductModificationConst|string|null $modification,
    )
    {
        $this->profile = (string) $profile;
        $this->product = (string) $product;
        $this->offer = $offer ? (string) $offer : null;
        $this->variation = $variation ? (string) $variation : null;
        $this->modification = $modification ? (string) $modification : null;
    }

    public function getProfile(): UserProfileUid
    {
        return new UserProfileUid($this->profile);
    }

    public function getProduct(): ProductUid
    {
        return new ProductUid($this->product);
    }

    public function getOfferConst(): ?ProductOfferConst
    {
        return $this->offer ? new ProductOfferConst($this->offer) : null;
    }

    public function getVariationConst(): ?ProductVariationConst
    {
        return $this->offer ? new ProductVariationConst($this->variation) : null;
    }

    public function getModificationConst(): ?ProductModificationConst
    {
        return $this->offer ? new ProductModificationConst($this->modification) : null;
    }
}
