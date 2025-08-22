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

namespace BaksDev\Avito\Products\Repository\AllProductsIdentifierByAvitoMapper;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


class AllProductsWithAvitoMapperResult
{
    public function __construct(
        private readonly string $product_id, //   "01876b34-ed23-7c18-ba48-9071e8646a08"
        private readonly string $product_event, //   "01963548-294f-71a6-b4b5-705cc4c470bd"
        private readonly string $product_offer_const, //   "01876b34-eccb-7188-887f-0738cae05232"
        private readonly string $product_variation_const, //   "01876b34-ecce-7c46-9f63-fc184b6527ee"
        private readonly string $product_modification_const, //   "018b1f16-28e6-7ce1-aae8-c980d5c30568"
        private readonly string $product_article, //   "TC101-15-185-65-88H"
        private readonly string $product_category, //   "Triangle"
        private readonly ?int $product_price, //   400000
        private readonly ?int $product_old_price, //   0
        private readonly int $product_quantity, //   0
    ) {}

    public function getProductId(): ProductUid
    {
        return new ProductUid($this->product_id);
    }

    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->product_event);
    }

    public function getProductOfferConst(): ProductOfferConst
    {
        return new ProductOfferConst($this->product_offer_const);
    }

    public function getProductVariationConst(): ProductVariationConst
    {
        return new ProductVariationConst($this->product_variation_const);
    }

    public function getProductModificationConst(): ProductModificationConst
    {
        return new ProductModificationConst($this->product_modification_const);
    }

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getProductCategory(): string
    {
        return $this->product_category;
    }

    public function getProductPrice(): Money
    {
        return new Money($this->product_price, true);
    }

    public function getProductOldPrice(): Money
    {
        return new Money($this->product_old_price, true);
    }

    public function getProductQuantity(): int
    {
        return $this->product_quantity ?: 0;
    }
}