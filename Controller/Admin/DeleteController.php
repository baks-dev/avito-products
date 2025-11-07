<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteDTO;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteForm;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_AVITO_PRODUCTS_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/avito/product/delete/{id}', name: 'admin.products.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] AvitoProduct $avitoProduct,
        AvitoProductDeleteHandler $AvitoProductDeleteHandler,
    ): Response
    {
        $avitoProductDeleteDTO = new AvitoProductDeleteDTO();
        $avitoProduct->getDto($avitoProductDeleteDTO);
        $form = $this
            ->createForm(AvitoProductDeleteForm::class, $avitoProductDeleteDTO, ['action' => $this->generateUrl(
                'avito-products:admin.products.delete',
                ['id' => $avitoProductDeleteDTO->getId()]
            )])
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('avito_product_delete'))
        {
            $handle = $AvitoProductDeleteHandler->handle($avitoProductDeleteDTO);

            $this->addFlash
            (
                'page.delete',
                $handle instanceof AvitoProduct ? 'success.delete' : 'danger.delete',
                'avito-products.admin',
                $handle
            );

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);
    }
}