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

        /** Задержка перед выполнением запроса на обновление остатков - максимальное количество запросов в минуту: 500 */
        usleep(500000);

        $updateStock = $this->updateAvitoProductStockRequest
            ->profile($message->getProfile())
            ->externalId($article)
            ->itemId($identifier)
            ->quantity($product['product_quantity'])
            ->put();

        if(false !== $updateStock)
        {
            $this->logger->info(
                sprintf('%s: Обновили остаток товара => %s', $article, $product['product_quantity']),
                [__FILE__.':'.__LINE__]
            );

            return;
        }


        $this->logger->critical(
            sprintf('avito-products: Не удалось обновить остатки товара с артикулом %s', $article),
            [__FILE__.':'.__LINE__]
        );

        /*$this->messageDispatch->dispatch(
            message: $message,
            stamps: [new MessageDelay('1 minutes')], // повторение через 1 минуту
            transport: 'avito-products'
        );*/

    }
}
