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

namespace BaksDev\Avito\Products\UseCase\Delete\Tests;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Type\Id\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteDTO;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group avito-products
 * @group avito-products-usecase
 *
 * @depends BaksDev\Avito\Products\UseCase\NewEdit\Images\Tests\AvitoProductImagesEditTest::class
 */
#[When(env: 'test')]
class AvitoProductDeleteTest extends KernelTestCase
{
    public function testDelete(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $avitoProduct = $em
            ->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        self::assertNotNull($avitoProduct);

        $deleteDTO = new AvitoProductDeleteDTO();

        $avitoProduct->getDto($deleteDTO);

        /** @var AvitoProductDeleteHandler $handler */
        $handler = $container->get(AvitoProductDeleteHandler::class);
        $deletedAvitoProduct = $handler->handle($deleteDTO);
        self::assertTrue($deletedAvitoProduct instanceof AvitoProduct);
    }

    public static function tearDownAfterClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $product = $em->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        if($product)
        {
            $em->remove($product);

        }

        $em->flush();
        $em->clear();

        $fileSystem = $container->get(Filesystem::class);

        /** @var ContainerBagInterface $containerBag */
        $containerBag = $container->get(ContainerBagInterface::class);

        /** Создаем путь к тестовой директории */
        $testUploadDir = implode(DIRECTORY_SEPARATOR, [$containerBag->get('kernel.project_dir'), 'public', 'upload', 'tests']);

        /** Проверяем существование директории для тестовых картинок*/
        if(true === is_dir($testUploadDir))
        {
            $fileSystem->remove($testUploadDir);
        }
    }
}
