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

namespace BaksDev\Avito\Products\Repository\ProductInfoByIdentifier;

use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductInfoByIdentifierResult */
final readonly class ProductInfoByIdentifierResult
{
    public function __construct(
        private string $product_article, // " => "Test New Modification Article"
        private ?int $product_price, // " => 6500
        private ?int $product_old_price, // " => 0
        private ?int $product_quantity, // " => 0
    ) {}

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getProductPrice(): Money|false
    {
        return empty($this->product_price) ? false : new Money($this->product_price, true);
    }

    public function getProductOldPrice(): Money|false
    {
        return empty($this->product_old_price) ? false : new Money($this->product_price, true);
    }

    public function getProductQuantity(): int
    {
        return empty($this->product_quantity) ? 0 : max($this->product_quantity, 0);
    }
}