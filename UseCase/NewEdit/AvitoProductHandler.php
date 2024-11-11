<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\Messenger\AvitoProductMessage;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Core\Entity\AbstractHandler;
use Doctrine\Common\Collections\ArrayCollection;

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
                    'product' => $command->getProduct(),
                    'offer' => $command->getOffer(),
                    'variation' => $command->getVariation(),
                    'modification' => $command->getModification()
                ]
            );

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
            transport: 'avito-products'
        );

        return $entity;
    }
}
