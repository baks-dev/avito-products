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
        /** Валидация DTO */
        $this->validatorCollection->add($command);

        /** @var AvitoProduct|null $entity */
        $entity = $this->entityManager->getRepository(AvitoProduct::class)
            ->findOneBy([
                'product' => $command->getProduct(),
                'offer' => $command->getOffer(),
                'variation' => $command->getVariation(),
                'modification' => $command->getModification()
            ]);

        if(null === $entity)
        {
            $entity = new AvitoProduct();
            $this->entityManager->persist($entity);
        }

        /** Передаем в статическую сущность EntityManager */
        $entity->setEntityManager($this->entityManager);
        $entity->setEntity($command);

        $this->validatorCollection->add($entity);

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

        $this->entityManager->flush();

        $this->messageDispatch->dispatch(
            message: new AvitoProductMessage($entity->getId()),
            transport: 'avito-products'
        );

        return $entity;
    }
}
