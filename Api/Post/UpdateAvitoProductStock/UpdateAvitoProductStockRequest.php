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

namespace BaksDev\Avito\Products\Api\Post\UpdateAvitoProductStock;

use BaksDev\Avito\Api\AvitoApi;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class UpdateAvitoProductStockRequest extends AvitoApi
{
    private const bool STOP_SALES = false;

    private int|false $itemId = false;

    private int|false $quantity = false;

    private string|false $externalId = false;

    /**
     * @param string $itemId - Идентификатор объявления во внешней системе
     */
    public function externalId(string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @param int $itemId - Идентификатор объявления на сайте
     */
    public function itemId(int $itemId): self
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @param int $quantity - Количество товара (от 0 до 999999)
     */
    public function quantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * С помощью метода API из этого раздела вы можете контролировать количество остатков в объявлениях, размещённых на Авито.
     * Данные синхронизируются с вашей системой учёта.
     *
     * Максимальное количество элементов в одном запросе - 200
     * Максимальное количество запросов в минуту - 500
     *
     * @see https://developers.avito.ru/api-catalog/stock-management/documentation#ApiDescriptionBlock
     */
    public function put(): array|bool
    {
        /** Обрываем в тестовой среде */
        if(false === $this->isExecuteEnvironment())
        {
            return true;
        }

        if(false === $this->itemId)
        {
            throw new InvalidArgumentException('Не передан обязательны параметр запроса: itemId');
        }

        if(false === $this->quantity)
        {
            throw new InvalidArgumentException('Не передан обязательны параметр запроса: quantity');
        }

        if(false === $this->externalId)
        {
            throw new InvalidArgumentException('Не передан параметр запроса: externalId');
        }

        $response = $this->TokenHttpClient()
            ->request(
                'PUT',
                '/stock-management/1/stocks',
                [
                    "json" => [
                        "stocks" => [[
                            'external_id' => $this->externalId,
                            'item_id' => $this->itemId,
                            'quantity' => self::STOP_SALES === true ? 0 : max($this->quantity, 0),
                        ]]
                    ]
                ],
            );

        $result = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('avito-products: Не удалось обновить остатки для объявления %s', $this->itemId),
                [
                    __FILE__.':'.__LINE__,
                    $result,
                ]);

            return false;
        }

        if(false === isset($result['stocks']))
        {
            $this->logger->critical(
                sprintf('avito-products: Не удалось обновить остатки для объявления %s', $this->itemId),
                [__FILE__.':'.__LINE__, $result]
            );

            return false;
        }

        $stocks = current($result['stocks']);

        if(false === $stocks['success'])
        {
            $this->logger->critical(
                sprintf('avito-products: Не удалось обновить остатки для объявления %s', $this->itemId),
                [__FILE__.':'.__LINE__, $result]
            );

            return false;
        }

        return true;
    }
}
