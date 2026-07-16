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

namespace BaksDev\Avito\Products\Messenger\Product\SalesMultiselect\Handler\Products;


use BaksDev\Avito\Type\Id\AvitoTokenUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SalesMultiselectAvitoProductForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('token', HiddenType::class, ['required' => false]);

        $builder->get('token')->addModelTransformer(
            new CallbackTransformer(
                function($value) {
                    return $value instanceof AvitoTokenUid ? $value->getValue() : $value;
                },
                function($value) {
                    return $value ? new AvitoTokenUid($value) : null;
                },
            ),
        );


        $builder->add('product', HiddenType::class, ['required' => false]);

        $builder->get('product')->addModelTransformer(
            new CallbackTransformer(
                function($value) {
                    return $value instanceof ProductUid ? $value->getValue() : $value;
                },
                function($value) {
                    return $value ? new ProductUid($value) : null;
                },
            ),
        );


        $builder->add('offer', HiddenType::class, ['required' => false]);

        $builder->get('offer')->addModelTransformer(
            new CallbackTransformer(
                function($value) {
                    return $value instanceof ProductOfferConst ? $value->getValue() : $value;
                },
                function($value) {
                    return $value ? new ProductOfferConst($value) : null;
                },
            ),
        );


        $builder->add('variation', HiddenType::class, ['required' => false]);

        $builder->get('variation')->addModelTransformer(
            new CallbackTransformer(
                function($value) {
                    return $value instanceof ProductVariationConst ? $value->getValue() : $value;
                },
                function($value) {
                    return $value ? new ProductVariationConst($value) : null;
                },
            ),
        );

        $builder->add('modification', HiddenType::class, ['required' => false]);

        $builder->get('modification')->addModelTransformer(
            new CallbackTransformer(
                function($value) {
                    return $value instanceof ProductModificationConst ? $value->getValue() : $value;
                },
                function($value) {
                    return $value ? new ProductModificationConst($value) : null;
                },
            ),
        );

        $builder->add('kit', HiddenType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SalesMultiselectAvitoProductDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}