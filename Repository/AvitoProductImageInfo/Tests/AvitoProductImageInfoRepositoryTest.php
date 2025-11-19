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

namespace BaksDev\Avito\Products\Repository\AvitoProductImageInfo\Tests;

use BaksDev\Avito\Products\Repository\AvitoProductImageInfo\AvitoProductImageInfoInterface;
use BaksDev\Avito\Products\Repository\AvitoProductImageInfo\AvitoProductImageInfoResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('avito-products')]
#[When(env: 'test')]
class AvitoProductImageInfoRepositoryTest extends KernelTestCase
{
    public function testRepository(): void
    {

        /** @var AvitoProductImageInfoInterface $AvitoProductImageInfoInterface */
        $AvitoProductImageInfoInterface = self::getContainer()->get(AvitoProductImageInfoInterface::class);

        $AvitoProductImageInfoResult = $AvitoProductImageInfoInterface
            ->forProfile(new UserProfileUid('019469c3-700f-76a9-9b34-ccde7b4e6f49'))
            ->find();

        if ($AvitoProductImageInfoResult instanceof AvitoProductImageInfoResult)
        {
            $reflectionClass = new \ReflectionClass(AvitoProductImageInfoResult::class);
            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                /* Методы без аргументов */
                if($method->getNumberOfParameters() === 0)
                {
                    /* Вызываем метод */
                    $data = $method->invoke($AvitoProductImageInfoResult);
                    dump($data);
                }
            }
        }

        //        dump($product_info);

        self::assertTrue(true);
    }
}