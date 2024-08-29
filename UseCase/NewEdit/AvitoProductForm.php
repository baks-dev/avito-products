<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\UseCase\NewEdit;

use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductsImagesForm;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AvitoProductForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * ID продукта.
         */
        $builder->add('product', HiddenType::class);

        $builder->get('product')->addModelTransformer(
            new CallbackTransformer(
                function ($product) {
                    return $product instanceof ProductUid ? $product->getValue() : $product;
                },
                function ($product) {
                    return $product ? new ProductUid($product) : null;
                }
            )
        );

        /**
         * Константа ТП
         */
        $builder->add('offer', HiddenType::class);

        $builder->get('offer')->addModelTransformer(
            new CallbackTransformer(
                function ($offer) {
                    return $offer instanceof ProductOfferConst ? $offer->getValue() : $offer;
                },
                function ($offer) {
                    return $offer ? new ProductOfferConst($offer) : null;
                }
            )
        );

        /**
         * Константа варианта
         */
        $builder->add('variation', HiddenType::class, ['required' => false]);

        $builder->get('variation')->addModelTransformer(
            new CallbackTransformer(
                function ($variation) {
                    return $variation instanceof ProductVariationConst ? $variation->getValue() : $variation;
                },
                function ($variation) {
                    return $variation ? new ProductVariationConst($variation) : null;
                }
            )
        );

        /**
         * Константа модификации варианта
         */
        $builder->add('modification', HiddenType::class, ['required' => false]);

        $builder->get('modification')->addModelTransformer(
            new CallbackTransformer(
                function ($modification) {
                    return $modification instanceof ProductModificationConst ? $modification->getValue() : $modification;
                },
                function ($modification) {
                    return $modification ? new ProductModificationConst($modification) : null;
                }
            )
        );

        $builder->add('images', CollectionType::class, [
            'entry_type' => AvitoProductsImagesForm::class,
            'entry_options' => [
                'label' => false,
            ],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__images__',
        ]);

        /** Сохранить */
        $builder->add(
            'avito_product',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvitoProductDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}
