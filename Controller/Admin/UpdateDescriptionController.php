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

use BaksDev\Avito\Products\UseCase\UpdateDescription\Profile\UpdateAvitoProductsDescriptionProfileDTO;
use BaksDev\Avito\Products\UseCase\UpdateDescription\UpdateAvitoProductsDescriptionDTO;
use BaksDev\Avito\Products\UseCase\UpdateDescription\UpdateAvitoProductsDescriptionForm;
use BaksDev\Avito\Products\UseCase\UpdateDescription\UpdateAvitoProductsDescriptionHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_AVITO_PRODUCTS_EDIT')]
final class UpdateDescriptionController extends AbstractController
{
    #[Route('/admin/avito/products/description/edit', name: 'admin.description.edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        UpdateAvitoProductsDescriptionHandler $UpdateAvitoProductsDescriptionHandler,
    ): Response
    {
        /** Добавляем текущий профиль */
        $updateAvitoProductsDescriptionProfileDTO = new UpdateAvitoProductsDescriptionProfileDTO($this->getProfileUid());

        $updateAvitoProductsDescriptionDTO = new UpdateAvitoProductsDescriptionDTO()
            ->setProfile($updateAvitoProductsDescriptionProfileDTO);

        // Форма
        $form = $this
            ->createForm(
                UpdateAvitoProductsDescriptionForm::class,
                $updateAvitoProductsDescriptionDTO,
                ['action' => $this->generateUrl('avito-products:admin.description.edit')]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('avito_products_description_update'))
        {
            $handle = $UpdateAvitoProductsDescriptionHandler->handle($updateAvitoProductsDescriptionDTO);

            $this->addFlash
            (
                'page.edit',
                true === $handle ? 'success.edit' : 'danger.edit',
                'avito-products.admin'
            );

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);
    }
}