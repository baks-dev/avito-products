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

namespace BaksDev\Avito\Products\Messenger;

use BaksDev\Avito\Products\Messenger\ProductStocks\UpdateAvitoProductStockMessage;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllProfilesByActiveTokenInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обновляем остатки Авито при изменении статусов заказов
 */
#[Autoconfigure(public: true)]
#[AsMessageHandler(priority: 90)]
final readonly class UpdateStocksAvitoWhenChangeOrderStatusDispatcher
{
    public function __construct(
        private MessageDispatchInterface $messageDispatch,
        private AllProfilesByActiveTokenInterface $AllProfilesByActiveTokenRepository,
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private CurrentProductIdentifierByEventInterface $currentProductIdentifier,
        private DeduplicatorInterface $Deduplicator,
        #[Target('avitoProductsLogger')] private LoggerInterface $Logger,
        #[Autowire(env: 'PROJECT_PROFILE')] private ?string $PROJECT_PROFILE = null,
    ) {}


    public function __invoke(OrderMessage $message): void
    {

        /**  Получаем активные токены профилей пользователя */

        $profiles = $this->AllProfilesByActiveTokenRepository
            ->onlyActiveToken()
            ->findAll();

        if(false === $profiles || false === $profiles->valid())
        {
            return;
        }


        /** Получаем событие заказа */
        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->Logger->critical(
                'products-sign: Не найдено событие OrderEvent',
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        /** Дедубликатор изменения статусов (обновляем только один раз в сутки на статус) */

        $Deduplicator = $this->Deduplicator
            ->namespace('ozon-products')
            ->expiresAfter('1 day')
            ->deduplication([
                (string) $message->getId(),
                $OrderEvent->getStatus()->getOrderStatusValue(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $Deduplicator->save();


        /**
         * Обновляем остатки
         */

        $EditOrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($EditOrderDTO);


        foreach($profiles as $UserProfileUid)
        {
            /** Если указан профиль проекта - пропускаем остальные профили */
            if(false === empty($this->PROJECT_PROFILE) && false === $UserProfileUid->equals($this->PROJECT_PROFILE))
            {
                continue;
            }

            /** @var OrderProductDTO $product */
            foreach($EditOrderDTO->getProduct() as $product)
            {
                /** Получаем идентификаторы продуктов, на которые поступил заказ  */
                $CurrentProductIdentifier = $this->currentProductIdentifier
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
                {
                    continue;
                }

                $updateAvitoProductStockMessage = new UpdateAvitoProductStockMessage(
                    $UserProfileUid,
                    $CurrentProductIdentifier->getProduct(),
                    $CurrentProductIdentifier->getOfferConst(),
                    $CurrentProductIdentifier->getVariationConst(),
                    $CurrentProductIdentifier->getModificationConst(),
                );

                $this->messageDispatch->dispatch(
                    message: $updateAvitoProductStockMessage,
                    stamps: [new MessageDelay('5 seconds')], // задержка 5 сек для обновления остатков в объявлении на Авито
                    transport: (string) $UserProfileUid,
                );

                /** Дополнительно пробуем обновить (на случай если остатки еще не успели пересчитаться) */

                $this->messageDispatch->dispatch(
                    message: $updateAvitoProductStockMessage,
                    stamps: [new MessageDelay('15 seconds')], // задержка 5 сек для обновления остатков в объявлении на Авито
                    transport: (string) $UserProfileUid,
                );

            }
        }
    }
}
