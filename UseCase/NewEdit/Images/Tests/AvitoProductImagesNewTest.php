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
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 *  @group avito-products
 *  @group avito-products-usecase
 */
// * @depends BaksDev\Avito\Products\UseCase\NewEdit\Tests::class
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
        /** @var ContainerBagInterface $ContainerBagInterface */
        $ContainerBagInterface = self::getContainer()->get(ContainerBagInterface::class);
        dd(BaksDevAvitoProductsBundle::PATH);
        dd($ContainerBagInterface->get('kernel.project_dir'));

        $image = new AvitoProductImagesDTO();
        $file = new File('/home/kepler.baks.dev/public/assets/img/empty.webp', true);

        dd($file);
        $image->setFile($file);

        $editDTO->getImages()->add($image);

//        dd($image);
        $container = self::getContainer();

        /** @var AvitoProductHandler $handler */
        $handler = $container->get(AvitoProductHandler::class);
        $editAvitoProduct = $handler->handle($editDTO);
    }
}
