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

namespace BaksDev\Avito\Products\Repository\AllAvitoProductsByProfile\Tests;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Repository\AllAvitoProductsByProfile\AllAvitoProductsByProfileInterface;
use BaksDev\Avito\Products\Repository\AllAvitoProductsByProfile\AllAvitoProductsByProfileRepository;
use BaksDev\Avito\Products\UseCase\NewEdit\Tests\AvitoProductNewTest;
use BaksDev\Avito\Type\Id\AvitoTokenUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-products')]
#[Group('avito-products-repository')]
final class AllAvitoProductsByProfileTest extends KernelTestCase
{
    #[DependsOnClass(AvitoProductNewTest::class)]
    public function testRepository(): void
    {
        self::assertTrue(true);

        /** @var AllAvitoProductsByProfileRepository $AllAvitoProductsByProfileRepository */
        $AllAvitoProductsByProfileRepository = self::getContainer()->get(AllAvitoProductsByProfileInterface::class);

        $result = $AllAvitoProductsByProfileRepository
            ->findAll(new AvitoTokenUid(AvitoTokenUid::TEST));

        if(empty($result))
        {
            return;
        }

        foreach($result as $AvitoProduct)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AvitoProduct::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($AvitoProduct);
                    // dump($data);
                }
            }
        }
    }
}