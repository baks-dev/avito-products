<?php

namespace BaksDev\Avito\Products\UseCase\NewEdit\Tests;

use BaksDev\Avito\Products\BaksDevAvitoProductsBundle;
use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\Type\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @group avito-products
 * @group avito-products-usecase
 *
 * @depends BaksDev\Avito\Products\UseCase\NewEdit\Tests\AvitoProductNewTest::class
 */
#[When(env: 'test')]
class AvitoProductEditTest extends KernelTestCase
{
    public function testEdit(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        /** @var AvitoProduct $product */
        $product = $em
            ->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        self::assertNotNull($product);

        $editDTO = new AvitoProductDTO();

        $product->getDto($editDTO);

        self::assertTrue($editDTO->getProduct()->equals(ProductUid::TEST));
        self::assertTrue($editDTO->getOffer()->equals(ProductOfferConst::TEST));
        self::assertTrue($editDTO->getVariation()->equals(ProductVariationConst::TEST));
        self::assertTrue($editDTO->getModification()->equals(ProductModificationConst::TEST));

        self::assertEquals('new_description', $editDTO->getDescription());

        $editDTO->setDescription('edit_description');
        self::assertSame('edit_description', $editDTO->getDescription());

        self::assertEmpty($editDTO->getImages());

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
        self::assertTrue($editAvitoProduct instanceof AvitoProduct);
    }
}
