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

namespace BaksDev\Avito\Products\Messenger\ProductStocks;

use BaksDev\Avito\Board\Api\GetIdByArticleRequest;
use BaksDev\Avito\Products\Api\Post\UpdateAvitoProductStock\UpdateAvitoProductStockRequest;
use BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\ProductInfoByIdentifierInterface;
use BaksDev\Avito\Products\Repository\ProductInfoByIdentifier\ProductInfoByIdentifierResult;
use BaksDev\Avito\Repository\AllTokensByProfile\AvitoTokensByProfileInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Repository\ProductTotalInOrders\ProductTotalInOrdersInterface;
use BaksDev\Products\Stocks\BaksDevProductsStocksBundle;
use BaksDev\Products\Stocks\Repository\ProductWarehouseTotal\ProductWarehouseTotalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Метод отправляет запрос Avito API на обновление остатков у объявления
 */
#[AsMessageHandler]
final readonly class UpdateAvitoProductStockDispatcher
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
        private AvitoTokensByProfileInterface $AvitoTokensByProfileRepository,
        #[Autowire(env: 'PROJECT_PROFILE')] private ?string $PROJECT_PROFILE = null,
    ) {}

    /**
     * Метод отправляет запрос Avito API на обновление остатков у объявления
     */
    public function __invoke(UpdateAvitoProductStockMessage $message): void
    {

        /** Если указан профиль проекта - пропускаем остальные профили */
        if(false === empty($this->PROJECT_PROFILE) && false === $message->getProfile()->equals($this->PROJECT_PROFILE))
        {
            return;
        }

        /** Находим уникальный продукт: его количество и артикул */
        $ProductInfoByIdentifierResult = $this->productInfoByIdentifier
            ->forProduct($message->getProduct())
            ->forOfferConst($message->getOfferConst())
            ->forVariationConst($message->getVariationConst())
            ->forModificationConst($message->getModificationConst())
            ->find();

        if(false === ($ProductInfoByIdentifierResult instanceof ProductInfoByIdentifierResult))
        {
            return;
        }

        /**  Получаем активные токены профилей пользователя */

        $tokens = $this->AvitoTokensByProfileRepository
            ->forProfile($message->getProfile())
            ->onlyActive()
            ->findAll();

        if(false === $tokens || false === $tokens->valid())
        {
            return;
        }

        foreach($tokens as $AvitoTokenUid)
        {
            /** Получаем идентификатор объявления по артикулу */
            $identifier = $this->getIdByArticleRequest
                ->forTokenIdentifier($AvitoTokenUid)
                ->find($ProductInfoByIdentifierResult->getProductArticle());

            if(false === $identifier)
            {
                $this->logger->critical(
                    sprintf(
                        'avito-products: Не найден идентификатор объявления по артикулу %s',
                        $ProductInfoByIdentifierResult->getProductArticle(),
                    ),
                    [self::class.':'.__LINE__],
                );

                return;
            }

            $Deduplicator = $this->deduplicator
                ->namespace('avito-products')
                ->expiresAfter('1 seconds')
                ->deduplication([$AvitoTokenUid, self::class]);

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
            $ProductQuantity = $ProductInfoByIdentifierResult->getProductQuantity();

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
                ->forTokenIdentifier($AvitoTokenUid)
                ->externalId($ProductInfoByIdentifierResult->getProductArticle())
                ->itemId($identifier)
                ->quantity($ProductQuantity)
                ->put();

            if(false === $updateStock)
            {
                $this->logger->critical(
                    sprintf(
                        'avito-products: Не удалось обновить остатки товара с артикулом %s',
                        $ProductInfoByIdentifierResult->getProductArticle(),
                    ),
                    [self::class.':'.__LINE__],
                );

                return;
            }

            $this->logger->info(
                sprintf('%s: Обновили остаток товара => %s', $ProductInfoByIdentifierResult->getProductArticle(), $ProductQuantity),
                [self::class.':'.__LINE__],
            );

            $Deduplicator->save();
        }
    }
}
