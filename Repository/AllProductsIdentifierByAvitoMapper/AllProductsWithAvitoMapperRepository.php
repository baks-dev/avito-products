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

namespace BaksDev\Avito\Products\Repository\AllProductsIdentifierByAvitoMapper;

use BaksDev\Avito\Board\Entity\AvitoBoard;
use BaksDev\Avito\Board\Entity\Element\AvitoBoardMapperElement;
use BaksDev\Avito\Board\Entity\Event\AvitoBoardEvent;
use BaksDev\Avito\Entity\AvitoToken;
use BaksDev\Avito\Entity\Event\AvitoTokenEvent;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Active\ProductActive;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use Generator;
use InvalidArgumentException;

final class AllProductsWithAvitoMapperRepository implements AllProductsWithAvitoMapperInterface
{
    /** Для фильтрации по артикулу или его части */
    private string|false $article = false;

    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}


    public function byArticle(string $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function profile(UserProfile|UserProfileUid|string $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Метод получает продукт для которого есть маппер Авито
     *
     * @return Generator<int, array{
     *      id: string,
     *      event: string,
     *      product_offer_const: string,
     *      product_variation_const: string,
     *      product_modification_const: string,
     *      product_article: string,
     *      category_active: bool,
     *      product_category: string,
     *      product_price: int,
     *      product_currency: string,
     *      product_quantity: int
     *  }>| false
     */
    public function findAll(): Generator|false
    {
        if($this->profile === false)
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id AS product_id')
            ->addSelect('product.event AS product_event')
            ->from(Product::class, 'product');

        /** Проверяю, есть ли соответствующий профиль */
        $dbal
            ->join(
                'product',
                AvitoToken::class,
                'avito_token',
                'avito_token.id = :profile',
            )
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE,
            );

        $dbal
            ->join(
                'avito_token',
                AvitoTokenEvent::class,
                'avito_token_event',
                '
                        avito_token_event.id = avito_token.event AND
                        avito_token_event.active = TRUE',
            );

        $dbal->join(
            'avito_token',
            UserProfileInfo::class,
            'info',
            '
                info.profile = avito_token.id AND
                info.status = :status',
        );

        $dbal->setParameter(
            key: 'status',
            value: UserProfileStatusActive::class,
            type: UserProfileStatus::TYPE,
        );

        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event',
        );

        /** Получаем только на активные продукты */
        $dbal
            ->join(
                'product',
                ProductActive::class,
                'product_active',
                '
                    product_active.event = product.event AND 
                    product_active.active IS TRUE',
            );

        /** Здесь находится артикул продукта */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id',
            );

        /**
         * OFFER
         */
        $dbal
            ->addSelect('product_offer.const as product_offer_const')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id',
            );

        /**
         * VARIATION
         */
        $dbal
            ->addSelect('product_variation.const as product_variation_const')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id',
            );

        /**
         * MODIFICATION
         */
        $dbal
            ->addSelect('product_modification.const as product_modification_const')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id',
            );

        /**
         * Артикул продукта
         */
        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');

        /**
         * Категория
         */
        $dbal
            ->join(
                'product_event',
                ProductCategory::class,
                'product_category',
                'product_category.event = product_event.id AND product_category.root = true',
            );

        $dbal->join(
            'product_category',
            CategoryProduct::class,
            'category',
            'category.id = product_category.category',
        );

        /** Получаем только на активные категории */
        $dbal
            ->join(
                'product_category',
                CategoryProductInfo::class,
                'category_info',
                '
                    category.event = category_info.event AND
                    category_info.active IS TRUE',
            );

        $dbal
            ->addSelect('category_trans.name AS product_category')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local',
            );

        /**
         * Базовая Цена товара
         */
        $dbal->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event',
        )
            ->addGroupBy('product_price.reserve');

        /**
         * Цена торгового предо жения
         */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id',
        );

        /**
         * Цена множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id',
        );

        /**
         * Цена модификации множественного варианта
         */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id',
        );

        /**
         * Стоимость продукта
         */
        $dbal->addSelect(
            '
			CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.price
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.price
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.price
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 
			   THEN product_price.price
			   
			   ELSE NULL
			END AS product_price',
        );

        /* Предыдущая стоимость продукта */

        $dbal->addSelect("
			COALESCE(
                NULLIF(product_modification_price.old, 0),
                NULLIF(product_variation_price.old, 0),
                NULLIF(product_offer_price.old, 0),
                NULLIF(product_price.old, 0),
                0
            ) AS product_old_price
		");


        /** Наличие продукта */

        /**
         * Наличие и резерв торгового предложения
         */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id',
        )
            ->addGroupBy('product_offer_quantity.reserve');

        /**
         * Наличие и резерв множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id',
        )
            ->addGroupBy('product_variation_quantity.reserve');

        $dbal->leftJoin(
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id',
        )
            ->addGroupBy('product_modification_quantity.reserve');

        $dbal->addSelect(
            '
            CASE
			   WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve 
			   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
			
			   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve 
			   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
			
			   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve 
			   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
			  
			   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve 
			   THEN (product_price.quantity - product_price.reserve)
			 
			   ELSE 0
			END AS product_quantity',
        );

        /** Avito mapper */
        /**
         * Категория, для которой создан маппер. Для каждой карточки
         */
        $dbal
            ->leftJoin(
                'product_category',
                AvitoBoard::class,
                'avito_board',
                'avito_board.id = product_category.category',
            );

        /**
         * Название категории в Авито из активного события маппера. Для каждой карточки
         */
        $dbal
            ->leftJoin(
                'avito_board',
                AvitoBoardEvent::class,
                'avito_board_event',
                'avito_board_event.id = avito_board.event',
            );

        $dbal
            ->leftJoin(
                'avito_board',
                AvitoBoardMapperElement::class,
                'avito_mapper',
                'avito_mapper.event = avito_board.event',
            );

        $dbal->allGroupByExclude();

        $dbal->where('avito_board.id IS NOT NULL AND avito_board_event.category IS NOT NULL');

        /**  */
        if(is_string($this->article))
        {
            $dbal->andWhere('
            (
                 CASE
				   WHEN product_modification.article LIKE :modification_article
				   THEN product_modification.article
				   
				   WHEN product_modification.article LIKE :variation_article
				   THEN product_variation.article
				   
				   WHEN product_modification.article LIKE :offer_article
				   THEN product_offer.article
				   
				   WHEN product_modification.article LIKE :product_article
				   THEN product_info.article
				   
				   ELSE NULL
                END
            ) IS NOT NULL
        ')
                ->setParameter('modification_article', "%".$this->article."%")
                ->setParameter('variation_article', "%".$this->article."%")
                ->setParameter('offer_article', "%".$this->article."%")
                ->setParameter('product_article', "%".$this->article."%");
        }

        return $dbal
            ->enableCache('orders-order', 3600)
            ->fetchAllGenerator();
    }
}
