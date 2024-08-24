<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Images\AvitoProductImage;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductImagesDTO;
use BaksDev\Core\Entity\AbstractHandler;

final class AvitoProductHandler extends AbstractHandler
{
    /** @see */
    public function handle(AvitoProductDTO $command): string|AvitoProduct
    {

        /** Валидация DTO  */
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
//            dd($entity);
        }

        $entity->setEntity($command);
//        dump($command);
//        dd($entity);

        /** @var AvitoProductImage $image */
        foreach($entity->getImages() as $image)
        {

            /** @var AvitoProductImagesDTO $avitoImagesDTO */
            $avitoImagesDTO = $image->getEntityDto();

            if(null !== $avitoImagesDTO->file)
            {
                $this->imageUpload->upload($avitoImagesDTO->file, $image);
            }
        }

        $this->validatorCollection->add($entity);

        /** Валидация всех объектов */
        if ($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }





        $this->entityManager->flush();

        // @TODO без сообщения?
        //
        //        $this->messageDispatch->dispatch(
        //            message: new Message($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
        //            transport: ''
        //        );

        return $entity;
    }
}
