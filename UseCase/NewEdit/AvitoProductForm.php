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

use BaksDev\Avito\Products\Repository\OneProductWithAvitoImages\OneProductWithAvitoImagesInterface;
use BaksDev\Avito\Products\UseCase\NewEdit\Images\AvitoProductsImagesForm;
use BaksDev\Core\Twig\TemplateExtension;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

final class AvitoProductForm extends AbstractType
{
    public function __construct(
        private readonly OneProductWithAvitoImagesInterface $oneProductWithAvitoImages,
        private readonly UserProfileTokenStorageInterface $userProfileTokenStorage,
        private readonly TemplateExtension $templateExtension,
        private readonly Environment $environment,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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

        $builder->add('description', TextareaType::class, [
            'required' => false,
            'label' => false,
        ]);

        /** Рендеринг шаблона, если описание NULL */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {

                /** @var AvitoProductDTO $dto */
                $dto = $event->getData();

                if(null !== $dto->getDescription())
                {
                    return;
                }

                $product = $this->oneProductWithAvitoImages
                    ->product($dto->getProduct())
                    ->offerConst($dto->getOffer())
                    ->variationConst($dto->getVariation())
                    ->modificationConst($dto->getModification())
                    ->execute();

                if(false === $product)
                {
                    throw new Exception('Продукт не найден');
                }

                /** Получаем ID текущего профиля пользователя для составления пути для шаблона */
                $userProfile = $this->userProfileTokenStorage->getProfileCurrent();

                /** Проверка существования шаблона в src - если нет, то дефолтный шаблон из модуля */
                try
                {
                    $path = sprintf('@avito-products:description/%s/%s.html.twig', $userProfile, $product['category_url']);

                    $template = $this->templateExtension->extends($path);
                    $render = $this->environment->render($template);
                }
                catch(Exception)
                {
                    $template = $this->templateExtension->extends('@avito-products:description/default.html.twig');
                    $render = $this->environment->render($template);
                }

                if(is_null($dto->getDescription()))
                {
                    $dto->setDescription($render);
                }
            }
        );

        /** Сохранить */
        $builder->add(
            'avito_product',
            SubmitType::class,
            [
                'label' => 'Save',
                'label_html' => true,
                'attr' => ['class' => 'btn-primary']
            ]
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
