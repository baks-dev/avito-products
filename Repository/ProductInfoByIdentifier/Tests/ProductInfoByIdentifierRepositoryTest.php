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

namespace BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\Tests;

use BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\ProductInfoByIdentifierInterface;
use BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\ProductInfoByIdentifierResult;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group avito-products
 */
#[Group('avito-products')]
#[When(env: 'test')]
class ProductInfoByIdentifierRepositoryTest extends KernelTestCase
{
    public function testEnv()
    {
        if(!isset($_SERVER['TEST_PRODUCT']))
        {
            echo PHP_EOL.'В .env.test не определены параметры тестового продукта : '.self::class.':'.__LINE__.PHP_EOL;

            /**
             * TEST_PRODUCT=018954cb-0a6e-744a-97f0-128e7f05d76d
             * TEST_OFFER_CONST=018db273-839d-7f69-8b4b-228aac5934f1
             * TEST_VARIATION_CONST=018db273-839c-72dd-bb36-de5c52445d28
             * TEST_MODIFICATION_CONST=018db273-839c-72dd-bb36-de5c523881be
             */
        }

        self::assertTrue(true);
    }

    public function testUseCase(): void
    {
        /** @var ProductInfoByIdentifierInterface $ProductInfoByIdentifierRepository */
        $ProductInfoByIdentifierRepository = self::getContainer()->get(ProductInfoByIdentifierInterface::class);

        $ProductInfoByIdentifierResult = $ProductInfoByIdentifierRepository
            ->forProduct(new ProductUid($_SERVER['TEST_PRODUCT']))
            ->forOfferConst(new ProductOfferConst($_SERVER['TEST_OFFER_CONST']))
            ->forVariationConst(new ProductVariationConst($_SERVER['TEST_VARIATION_CONST']))
            ->forModificationConst(new ProductModificationConst($_SERVER['TEST_MODIFICATION_CONST']))
            ->find();

        if($ProductInfoByIdentifierResult instanceof ProductInfoByIdentifierResult)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(ProductInfoByIdentifierResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($ProductInfoByIdentifierResult);
                    // dump($data);
                }
            }
        }


        self::assertTrue(true);
    }

}