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

namespace BaksDev\Avito\Products\Messenger\ProductStocks;

use BaksDev\Avito\Board\Api\GetIdByArticleRequest;
use BaksDev\Avito\Products\Api\Post\UpdateAvitoProductStock\UpdateAvitoProductStockRequest;
use BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\ProductInfoByIdentifierInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Repository\ProductTotalInOrders\ProductTotalInOrdersInterface;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Repository\ProductWarehouseTotal\ProductWarehouseTotalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAvitoProductStockHandler
{

    public function __construct(
        #[Target('avitoProductsLogger')] private LoggerInterface $logger,
        private ProductInfoByIdentifierInterface $productInfoByIdentifier,
        private GetIdByArticleRequest $getIdByArticleRequest,
        private UpdateAvitoProductStockRequest $updateAvitoProductStockRequest,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $dispatcher,
        private ProductTotalInOrdersInterface $ProductTotalInOrders,
        private ?ProductWarehouseTotalInterface $ProductWarehouseTotal = null,
    ) {}

    /**
     * Метод отправляет запрос Avito API на обновление остатков у объявления
     */
    public function __invoke(UpdateAvitoProductStockMessage $message): void
    {

        /** Находим уникальный продукт: его количество и артикул */
        $product = $this->productInfoByIdentifier
            ->forProduct($message->getProduct())
            ->forOfferConst($message->getOfferConst())
            ->forVariationConst($message->getVariationConst())
            ->forModificationConst($message->getModificationConst())
            ->find();

        /** Не обновляем остатки продукции без цены */
        if(empty($product['product_price']))
        {
            return;
        }

        $article = $product['product_article'];

        /** Получаем идентификатор объявления по артикулу */
        $identifier = $this->getIdByArticleRequest
            ->profile($message->getProfile())
            ->find($article);

        if(false === $identifier)
        {
            $this->logger->critical(
                sprintf('avito-products: Не найден идентификатор объявления по артикулу %s', $article),
                [__FILE__.':'.__LINE__]
            );

            return;
        }

        $Deduplicator = $this->deduplicator
            ->namespace('avito-products')
            ->expiresAfter('1 seconds')
            ->deduplication([$message->getProfile(), self::class]);

        if($Deduplicator->isExecuted())
        {
            $MessageDelay = new MessageDelay($Deduplicator->getAndSaveNextTime('1 seconds'));

            $this->dispatcher->dispatch(
                message: $message,
                stamps: [$MessageDelay],
                transport: 'avito-products',
            );

            return;
        }

        /** Остаток товара в карточке (по умолчанию) */
        $ProductQuantity = $product['product_quantity'];

        /** Если подключен модуль складского учета - расчет согласно остаткам склада */
        if(class_exists(BaksDevProductsStocksBundle::class))
        {
            /** Получаем остаток на складе с учетом резерва */
            $stocksTotal = $this->ProductWarehouseTotal->getProductProfileTotal(
                $message->getProfile(),
                $message->getProduct(),
                $message->getOfferConst(),
                $message->getVariationConst(),
                $message->getModificationConst(),
            );

            /** Получаем количество необработанных заказов */
            $unprocessed = $this->ProductTotalInOrders
                ->onProfile($message->getProfile())
                ->onProduct($message->getProduct())
                ->onOfferConst($message->getOfferConst())
                ->onVariationConst($message->getVariationConst())
                ->onModificationConst($message->getModificationConst())
                ->findTotal();


            $ProductQuantity = ($stocksTotal - $unprocessed);
        }

        /** Обновляем остаток товара в объявлении */

        $updateStock = $this->updateAvitoProductStockRequest
            ->profile($message->getProfile())
            ->externalId($article)
            ->itemId($identifier)
            ->quantity($ProductQuantity)
            ->put();

        if(false === $updateStock)
        {
            $this->logger->critical(
                sprintf('avito-products: Не удалось обновить остатки товара с артикулом %s', $article),
                [__FILE__.':'.__LINE__],
            );

            return;
        }


        $this->logger->info(
            sprintf('%s: Обновили остаток товара => %s', $article, $ProductQuantity),
            [__FILE__.':'.__LINE__],
        );


        $Deduplicator->save();


        /*$this->messageDispatch->dispatch(
            message: $message,
            stamps: [new MessageDelay('1 minutes')], // повторение через 1 минуту
            transport: 'avito-products'
        );*/
    }
}
