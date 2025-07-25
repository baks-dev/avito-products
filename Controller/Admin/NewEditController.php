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

declare(strict_types=1);

namespace BaksDev\Avito\Products\Controller\Admin;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Repository\AvitoProductProfile\AvitoProductProfileInterface;
use BaksDev\Avito\Products\Repository\OneProductWithAvitoImages\OneProductWithAvitoImagesInterface;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductForm;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_AVITO_PRODUCTS_EDIT')]
final class NewEditController extends AbstractController
{
    /**
     * @throws JsonException
     */
    #[Route(
        '/admin/avito/product/{product}/{offer}/{variation}/{modification}',
        name: 'admin.products.edit',
        methods: ['GET', 'POST']
    )]
    public function index(
        Request $request,
        AvitoProductProfileInterface $AvitoProductProfileInterface,
        AvitoProductHandler $handler,
        OneProductWithAvitoImagesInterface $oneProductWithAvitoImages,
        #[ParamConverter(ProductUid::class)] $product,
        #[ParamConverter(ProductOfferConst::class)] ?ProductOfferConst $offer = null,
        #[ParamConverter(ProductVariationConst::class)] ?ProductVariationConst $variation = null,
        #[ParamConverter(ProductModificationConst::class)] ?ProductModificationConst $modification = null,
    ): Response
    {

        $AvitoProductDTO = new AvitoProductDTO();

        $AvitoProductDTO
            ->setProduct($product)
            ->setOffer($offer)
            ->setVariation($variation)
            ->setModification($modification);

        $AvitoProductDTO->getProfile()->setValue($this->getProfileUid());

        /**
         * Находим уникальный продукт Авито, делаем его инстанс, передаем в форму
         *
         * @var AvitoProduct|false $avitoProductCard
         */
        $avitoProductCard = $AvitoProductProfileInterface
            ->product($AvitoProductDTO->getProduct())
            ->offerConst($AvitoProductDTO->getOffer())
            ->variationConst($AvitoProductDTO->getVariation())
            ->modificationConst($AvitoProductDTO->getModification())
            ->find();

        if(true === ($avitoProductCard instanceof AvitoProduct))
        {
            $avitoProductCard->getDto($AvitoProductDTO);
        }

        $form = $this->createForm(
            AvitoProductForm::class,
            $AvitoProductDTO,
            ['action' => $this->generateUrl(
                'avito-products:admin.products.edit',
                [
                    'product' => $AvitoProductDTO->getProduct(),
                    'offer' => $AvitoProductDTO->getOffer(),
                    'variation' => $AvitoProductDTO->getVariation(),
                    'modification' => $AvitoProductDTO->getModification(),
                ]
            )]
        );

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('avito_product'))
        {
            $this->refreshTokenForm($form);

            $handle = $handler->handle($AvitoProductDTO);

            $this->addFlash(
                'page.edit',
                $handle instanceof AvitoProduct ? 'success.edit' : 'danger.edit',
                'avito-products.admin',
                $handle
            );

            return $this->redirectToRoute('avito-products:admin.products.index');
        }

        $product = $oneProductWithAvitoImages
            ->product($AvitoProductDTO->getProduct())
            ->offerConst($AvitoProductDTO->getOffer())
            ->variationConst($AvitoProductDTO->getVariation())
            ->modificationConst($AvitoProductDTO->getModification())
            ->find();

        if(false === $product)
        {
            throw new Exception('Продукт не найден ');
        }

        return $this->render(['form' => $form->createView(), 'product' => $product]);
    }
}
