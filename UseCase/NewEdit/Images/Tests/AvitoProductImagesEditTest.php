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

        $editImagePNG = new AvitoProductImagesDTO();
        $editImagePNG->setRoot(false);

        $png = new File(BaksDevAvitoProductsBundle::PATH . 'Resources/tests/PNG.png', true);
        $editImagePNG->setFile($png);

        $editDTO->addImage($editImagePNG);

        $editImageWEBP = new AvitoProductImagesDTO();
        $editImageWEBP->setRoot(true);

        $webp = new File(BaksDevAvitoProductsBundle::PATH . 'Resources/tests/WEBP.webp', true);
        $editImageWEBP->setFile($webp);

        $editDTO->addImage($editImageWEBP);

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
        self::assertTrue($editAvitoProduct instanceof AvitoProduct);
    }
}
