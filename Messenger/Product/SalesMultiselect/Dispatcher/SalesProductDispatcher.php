<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\Messenger\Product\SalesMultiselect\Dispatcher;


use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Repository\AvitoProductProfile\AvitoProductProfileInterface;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final class SalesProductDispatcher
{
    public function __construct(
        #[Target('avitoProductsLogger')] private LoggerInterface $logger,
        private readonly AvitoProductProfileInterface $AvitoProductProfileInterface,
        private readonly AvitoProductHandler $AvitoProductHandler,
    ) {}

    public function __invoke(SalesProductMessage $message): void
    {
        /** Находим соответствующую настройку */

        $AvitoProductDTO = new AvitoProductDTO();

        $AvitoProductDTO
            ->setProduct($message->getProduct())
            ->setOffer($message->getOfferConst())
            ->setVariation($message->getVariationConst())
            ->setModification($message->getModificationConst());

        $AvitoProductDTO->getToken()->setValue($message->getToken());
        $AvitoProductDTO->getKit()->setValue($message->getKit());


        /**
         * Находим уникальный продукт Авито, делаем его инстанс, передаем в форму
         *
         * @var AvitoProduct|false $avitoProductCard
         */
        $avitoProductCard = $this->AvitoProductProfileInterface
            ->forAvitoToken($message->getToken())
            ->product($message->getProduct())
            ->offerConst($message->getOfferConst())
            ->variationConst($message->getVariationConst())
            ->modificationConst($message->getModificationConst())
            ->kit($message->getKit())
            ->find();

        if(true === ($avitoProductCard instanceof AvitoProduct))
        {
            $avitoProductCard->getDto($AvitoProductDTO);
        }

        $AvitoProductDTO->getSale()->setValue($message->getSale());

        $handle = $this->AvitoProductHandler->handle($AvitoProductDTO);

        if($handle instanceof AvitoProduct)
        {
            $this->logger->debug('Обновили объявление', [self::class.':'.__LINE__, 'message' => $message]);
            return;
        }

        $this->logger->critical(
            'avito-products: Ошибка при обновлении объявления',
            [self::class.':'.__LINE__, 'message' => $message],
        );

    }
}
