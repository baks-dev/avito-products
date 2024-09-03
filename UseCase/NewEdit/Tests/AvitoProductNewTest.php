<?php

namespace BaksDev\Avito\Products\UseCase\NewEdit\Tests;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\Type\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-products
 * @group avito-products-usecase
 *
 * @depends BaksDev\Avito\Products\Controller\Admin\Tests\AvitoProductIndexControllerTest::class
 */
#[When(env: 'test')]
class AvitoProductNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $avitoProduct = $em->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        if ($avitoProduct)
        {
            $em->remove($avitoProduct);
        }

        $avitoProductImages = $em->getRepository(AvitoProductImage::class)
            ->findBy(['avito' => AvitoProductUid::TEST]);

        foreach ($avitoProductImages as $image)
        {
            $em->remove($image);
        }

        $em->flush();
        $em->clear();
    }

    public function testNew(): void
    {
        $avitoProductDTO = new AvitoProductDTO();

        $avitoProductDTO->setProduct(new ProductUid(ProductUid::TEST));
        self::assertTrue($avitoProductDTO->getProduct()->equals(ProductUid::TEST));

        $avitoProductDTO->setOffer(new ProductOfferConst(ProductOfferConst::TEST));
        self::assertTrue($avitoProductDTO->getOffer()->equals(ProductOfferConst::TEST));

        $avitoProductDTO->setVariation(new ProductVariationConst(ProductVariationConst::TEST));
        self::assertTrue($avitoProductDTO->getVariation()->equals(ProductVariationConst::TEST));

        $avitoProductDTO->setModification(new ProductModificationConst(ProductModificationConst::TEST));
        self::assertTrue($avitoProductDTO->getModification()->equals(ProductModificationConst::TEST));

        $avitoProductDTO->setDescription('new_description');
        self::assertSame('new_description', $avitoProductDTO->getDescription());

        $image = new AvitoProductImagesDTO();
        $avitoProductDTO->getImages()->add($image);

        $container = self::getContainer();

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $newAvitoProduct = $handler->handle($avitoProductDTO);
        self::assertTrue($newAvitoProduct instanceof AvitoProduct);
    }
}
