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

namespace BaksDev\Avito\Products\Messenger\Product;

use BaksDev\Avito\Products\Repository\AllAvitoProducts\AllAvitoProductsInterface;
use BaksDev\Avito\Products\Repository\ExistProductByAvito\ExistProductByAvitoProductInterface;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteDTO;
use BaksDev\Avito\Products\UseCase\Delete\AvitoProductDeleteHandler;
use BaksDev\Products\Product\Messenger\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteAvitoProductWhereNotFoundProduct
{
    public function __construct(
        private AvitoProductDeleteHandler $deleteHandler,
        private AllAvitoProductsInterface $avitoProductsRepository,
        private ExistProductByAvitoProductInterface $existProductByAvitoProduct,
    ) {}

    /**
     * Удаляем Авито продукт если не найден в продукции
     */
    public function __invoke(ProductMessage $message): void
    {
        /** Находим все карточки продуктов Авито по продукту */
        $avitoProducts = $this->avitoProductsRepository
            ->product($message->getId())
            ->execute();

        if(false === $avitoProducts)
        {
            return;
        }

        /**
         * @var array{
         *     id: string,
         *     product: string,
         *     offer: string|null,
         *     variation: string|null,
         *     modification: string|null } $avitoProduct
         */
        foreach($avitoProducts as $avitoProduct)
        {
            /** Если по данным карточки продукта Авито продукт НЕ НАЙДЕН - удаляем соответствующую карточку Авито и все ее связи */
            $exist = $this->existProductByAvitoProduct
                ->product($avitoProduct['product'])
                ->offerConst($avitoProduct['offer'])
                ->variationConst($avitoProduct['variation'])
                ->modificationConst($avitoProduct['modification'])
                ->execute();

            if(false === $exist)
            {
                $dto = new AvitoProductDeleteDTO();
                $dto->setId($avitoProduct['id']);

                $this->deleteHandler->handle($dto);
            }
        }
    }
}
