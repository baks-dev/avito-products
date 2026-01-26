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

namespace BaksDev\Avito\Products\UseCase\NewEdit\Tests;

use BaksDev\Avito\Products\Controller\Admin\Tests\AvitoProductIndexAdminControllerTest;
use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\Type\Id\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Avito\Type\Id\AvitoTokenUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-products')]
#[Group('avito-products-controller')]
#[Group('avito-products-repository')]
#[Group('avito-products-handler')]
class AvitoProductNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $avitoProduct = $em
            ->getRepository(AvitoProduct::class)
            ->find(AvitoProductUid::TEST);

        if($avitoProduct)
        {
            $em->remove($avitoProduct);
        }

        $avitoProductImages = $em->getRepository(AvitoProductImage::class)
            ->findBy(['avito' => AvitoProductUid::TEST]);

        foreach($avitoProductImages as $image)
        {
            $em->remove($image);
        }

        $em->flush();
        $em->clear();
    }

    #[DependsOnClass(AvitoProductIndexAdminControllerTest::class)]
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

        $avitoProductDTO->getToken()->setValue(new AvitoTokenUid(AvitoTokenUid::TEST));
        self::assertTrue($avitoProductDTO->getToken()->getValue()->equals(AvitoTokenUid::TEST));

        $image = new AvitoProductImagesDTO();
        $avitoProductDTO->getImages()->add($image);

        $container = self::getContainer();

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $newAvitoProduct = $handler->handle($avitoProductDTO);
        self::assertTrue($newAvitoProduct instanceof AvitoProduct, message: $newAvitoProduct);
    }
}
