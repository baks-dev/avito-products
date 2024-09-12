<?php

namespace BaksDev\Avito\Products\UseCase\Delete\Tests;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Type\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteDTO;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

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

        if ($product)
        {
            $em->remove($product);

            $em->flush();
            $em->clear();
        }
    }
}
