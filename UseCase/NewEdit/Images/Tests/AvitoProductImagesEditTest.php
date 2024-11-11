<?php

namespace BaksDev\Avito\Products\UseCase\NewEdit\Images\Tests;

use BaksDev\Avito\Products\BaksDevAvitoProductsBundle;
use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Type\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @group avito-products
 * @group avito-products-usecase
 *
 * @depends BaksDev\Avito\Products\UseCase\NewEdit\Images\Tests\AvitoProductImagesNewTest::class
 */
#[When(env: 'test')]
class AvitoProductImagesEditTest extends KernelTestCase
{
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
        if (false === is_dir($testUploadDir))
        {
            $fileSystem->mkdir($testUploadDir);
        }

        /**
         * Новая картинка PNG
         */
        $editImagePNG = new AvitoProductImagesDTO();
        $editImagePNG->setRoot(false);

        /** Файл из пакета для копирования в тестовую директорию */
        $pngFrom = new File(BaksDevAvitoProductsBundle::PATH . 'Resources/tests/PNG.png', true);

        /** Файл для записи в тестовой директории */
        $pngTo = new File($testUploadDir . '/PNG.png', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($pngFrom->getPathname(), $pngTo->getPathname());

        self::assertTrue(is_file($pngTo->getPathname()), 'Не удалось создать файл в тестовой директории по пути:' . $pngTo->getPathname());

        $editImagePNG->setFile($pngTo);

        $editDTO->addImage($editImagePNG);

        /**
         * Новая картинка WEBP
         */
        $editImageWEBP = new AvitoProductImagesDTO();
        $editImageWEBP->setRoot(true);

        /** Файл из пакета для копирования в тестовую директорию */
        $webpFrom = new File(BaksDevAvitoProductsBundle::PATH . 'Resources/tests/WEBP.webp', true);

        /** Файл для записи в тестовой директории */
        $webpTo = new File($testUploadDir . '/WEBP.webp', false);

        /** Копируем файл из пакета для копирования в тестовую директорию */
        $fileSystem->copy($webpFrom->getPathname(), $webpTo->getPathname());

        self::assertTrue(is_file($webpTo->getPathname()), 'Не удалось создать файл в тестовой директории по пути:' . $webpTo->getPathname());

        $editImagePNG->setFile($webpTo);

        $editDTO->addImage($editImageWEBP);

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
        self::assertTrue($editAvitoProduct instanceof AvitoProduct);
    }
}
