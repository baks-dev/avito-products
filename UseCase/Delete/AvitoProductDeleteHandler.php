<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\UseCase\Delete;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Messenger\AvitoProductMessage;
use BaksDev\Core\Entity\AbstractHandler;

final class AvitoProductDeleteHandler extends AbstractHandler
{
    public function handle(AvitoProductDeleteDTO $command): AvitoProduct|string|null
    {
        /** Валидация DTO */
        $this->validatorCollection->add($command);

        /** @var AvitoProduct|null $entity */
        $entity = $this->entityManager->getRepository(AvitoProduct::class)
            ->find($command->getId());

        if (is_null($entity))
        {
            return null;
        }

        /** Валидация всех объектов */
        if ($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->remove($entity);

        $this->entityManager->flush();

        /** Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new AvitoProductMessage($entity->getId()),
            transport: 'avito-products'
        );

        return $entity;
    }
}
