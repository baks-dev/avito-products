<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\Messenger\DeleteAvitoProduct;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Repository\AvitoProducts\AvitoProductsInterface;
use BaksDev\Products\Product\Messenger\ProductMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteAvitoProductHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvitoProductsInterface $avitoProductsRepository,
    ) {}

    /** Удаляем Авито продукт */
    public function __invoke(ProductMessage $message): void
    {
        /** Находим все карточки продуктов Авито */
        $avitoProducts = $this->avitoProductsRepository->findAllByProduct($message->getId());

        foreach ($avitoProducts as $avitoProduct)
        {
            /** Если по данным карточки продукта Авито продукт НЕ НАЙДЕН - удаляем соответствующую карточку Авито и все ее связи */
            if (false === $this->avitoProductsRepository->productExist($avitoProduct))
            {
                $avitoRepo = $this->entityManager->getRepository(AvitoProduct::class);
                $avito = $avitoRepo->find($avitoProduct['id']);

                $this->entityManager->remove($avito);
            }
        }

        $this->entityManager->flush();
    }
}
