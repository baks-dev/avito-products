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
 * @depends BaksDev\Avito\Products\UseCase\NewEdit\Tests\AvitoProductEditTest::class
 */
#[When(env: 'test')]
class AvitoProductImagesNewTest extends KernelTestCase
{
    public function testNew(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $avitoProduct = $em
            ->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        self::assertNotNull($avitoProduct);

        $editDTO = new AvitoProductDTO();

        $avitoProduct->getDto($editDTO);

        self::assertEquals('edit_description', $editDTO->getDescription());

        $image = new AvitoProductImagesDTO();
        $image->setRoot(true);

        $jpeg = new File(BaksDevAvitoProductsBundle::PATH . 'Resources/tests/JPEG.jpg', true);
        $image->setFile($jpeg);

        $editDTO->addImage($image);

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
        self::assertTrue($editAvitoProduct instanceof AvitoProduct);
    }
}
