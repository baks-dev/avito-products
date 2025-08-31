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

namespace BaksDev\Avito\Products\UseCase\NewEdit\Images\Tests;

use BaksDev\Avito\Products\BaksDevAvitoProductsBundle;
use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Type\Id\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

#[When(env: 'test')]
#[Group('avito-products')]
class AvitoProductImagesEditTest extends KernelTestCase
{
    #[DependsOnClass(AvitoProductImagesNewTest::class)]
    public function testEdit(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $avitoProduct = $em
            ->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        self::assertNotNull($avitoProduct);

        $editDTO = new AvitoProductDTO();

        $avitoProduct->getDto($editDTO);

        /** @var AvitoProductImagesDTO $newImage */
        $newImage = $editDTO->getImages()->current();

        self::assertTrue($newImage->getRoot());
        self::assertSame($newImage->getExt(), 'jpg');

        $editDTO->removeImage($newImage);
        self::assertEmpty($editDTO->getImages());


        $fileSystem = $container->get(Filesystem::class);

        /** @var ContainerBagInterface $containerBag */
        $containerBag = $container->get(ContainerBagInterface::class);

        /** Создаем путь к тестовой директории */
        $testUploadDir = implode(DIRECTORY_SEPARATOR, [$containerBag->get('kernel.project_dir'), 'public', 'upload', 'tests']);

        /** Проверяем существование директории для тестовых картинок */
        if(false === is_dir($testUploadDir))
        {
            $fileSystem->mkdir($testUploadDir);
        }

        /**
         * Новая картинка PNG
         */
        $editImagePNG = new AvitoProductImagesDTO();
        $editImagePNG->setRoot(false);

        /** Файл из пакета для копирования в тестовую директорию */
        $pngFrom = new File(BaksDevAvitoProductsBundle::PATH.'Resources/tests/PNG.png', true);

        /** Файл для записи в тестовой директории */
        $pngTo = new File($testUploadDir.'/PNG.png', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($pngFrom->getPathname(), $pngTo->getPathname());

        self::assertTrue(is_file($pngTo->getPathname()), 'Не удалось создать файл в тестовой директории по пути:'.$pngTo->getPathname());

        $editImagePNG->setFile($pngTo);

        $editDTO->addImage($editImagePNG);

        /**
         * Новая картинка WEBP
         */
        $editImageWEBP = new AvitoProductImagesDTO();
        $editImageWEBP->setRoot(true);

        /** Файл из пакета для копирования в тестовую директорию */
        $webpFrom = new File(BaksDevAvitoProductsBundle::PATH.'Resources/tests/WEBP.webp', true);

        /** Файл для записи в тестовой директории */
        $webpTo = new File($testUploadDir.'/WEBP.webp', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($webpFrom->getPathname(), $webpTo->getPathname());

        self::assertTrue(is_file($webpTo->getPathname()), 'Не удалось создать файл в тестовой директории по пути:'.$webpTo->getPathname());

        $editImagePNG->setFile($webpTo);

        $editDTO->addImage($editImageWEBP);

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
        self::assertTrue($editAvitoProduct instanceof AvitoProduct);
    }
}
