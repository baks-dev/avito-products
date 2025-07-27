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

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\Messenger\AvitoProductMessage;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Core\Entity\AbstractHandler;

final class AvitoProductHandler extends AbstractHandler
{
    public function handle(AvitoProductDTO $command): string|AvitoProduct
    {
        /** Добавляем command для валидации и гидрации */
        $this->setCommand($command);

        /** @var AvitoProduct $entity */
        $entity = $this
            ->prePersistOrUpdate(
                AvitoProduct::class,
                [
                    'id' => $command->getAvitoProductUid(),
                ],
            );

        //        else
        //        {
        //            /** @var AvitoProduct $entity */
        //            $entity = $this
        //                ->prePersistOrUpdate(
        //                    AvitoProduct::class,
        //                    [
        //                        'product' => $command->getProduct(),
        //                        'offer' => $command->getOffer(),
        //                        'variation' => $command->getVariation(),
        //                        'modification' => $command->getModification(),
        //                    ]
        //                );
        //
        //        }


        /**
         * Загружаем изображения
         * @var AvitoProductImage $image
         */
        foreach($entity->getImages() as $image)
        {
            /** @var AvitoProductImagesDTO $avitoImagesDTO */
            if($avitoImagesDTO = $image->getEntityDto())
            {
                if(null !== $avitoImagesDTO->getFile())
                {
                    $this->imageUpload->upload($avitoImagesDTO->getFile(), $image);
                }
            }
        }

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        $this->messageDispatch->dispatch(
            message: new AvitoProductMessage($entity->getId()),
            transport: 'avito-products',
        );

        return $entity;
    }
}
