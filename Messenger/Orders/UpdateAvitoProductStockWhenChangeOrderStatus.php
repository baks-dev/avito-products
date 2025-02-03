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

namespace BaksDev\Avito\Products\Messenger\Orders;

use BaksDev\Avito\Products\Messenger\ProductStocks\UpdateAvitoProductStockMessage;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllUserProfilesByActiveTokenInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierInterface;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAvitoProductStockWhenChangeOrderStatus
{
    public function __construct(
        private MessageDispatchInterface $messageDispatch,
        private AllUserProfilesByActiveTokenInterface $allUserProfilesByActiveToken,
        private CurrentOrderEventInterface $currentOrderEvent,
        private CurrentProductIdentifierInterface $currentProductIdentifier,
    ) {}

    /**
     * При изменении статуса заказа - обновляем остатки товара в объявлении на Авито
     */
    public function __invoke(OrderMessage $message): void
    {
        /** Получаем все активные профили, у которых активный токен Авито */
        $profiles = $this->allUserProfilesByActiveToken->findProfilesByActiveToken();

        if($profiles->valid() === false)
        {
            return;
        }

        /** Получаем активное событие заказа */
        $orderEvent = $this->currentOrderEvent
            ->forOrder($message->getId())
            ->execute();

        if($orderEvent === false)
        {
            return;
        }

        $editOrderDTO = new EditOrderDTO();
        $orderEvent->getDto($editOrderDTO);

        foreach($profiles as $profile)
        {
            /** @var OrderProductDTO $product */
            foreach($editOrderDTO->getProduct() as $product)
            {
                /** Получаем идентификаторы продуктов, на которые поступил заказ  */
                $CurrentProductIdentifier = $this->currentProductIdentifier
                    ->forEvent($product->getProduct())
                    ->forOffer($product->getOffer())
                    ->forVariation($product->getVariation())
                    ->forModification($product->getModification())
                    ->find();

                if($CurrentProductIdentifier === false)
                {
                    continue;
                }

                $updateAvitoProductStockMessage = new UpdateAvitoProductStockMessage(
                    $profile,
                    $CurrentProductIdentifier->getProduct(),
                    $CurrentProductIdentifier->getOfferConst(),
                    $CurrentProductIdentifier->getVariationConst(),
                    $CurrentProductIdentifier->getModificationConst(),
                );

                $this->messageDispatch->dispatch(
                    message: $updateAvitoProductStockMessage,
                    stamps: [new MessageDelay('5 seconds')], // задержка 5 сек для обновления остатков в объявлении на Авито
                    transport: (string) $profile
                );
            }
        }
    }
}
