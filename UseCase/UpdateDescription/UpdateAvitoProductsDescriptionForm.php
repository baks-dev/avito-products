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

namespace BaksDev\Avito\Products\UseCase\UpdateDescription;

use BaksDev\Avito\Products\UseCase\UpdateDescription\Profile\UpdateAvitoProductsDescriptionProfileForm;
use BaksDev\Avito\Products\UseCase\UpdateDescription\Token\UpdateAvitoProductsDescriptionTokenForm;
use BaksDev\Core\Twig\TemplateExtension;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

final class UpdateAvitoProductsDescriptionForm extends AbstractType
{
    public function __construct(
        private readonly UserProfileTokenStorageInterface $userProfileTokenStorage,
        private readonly TemplateExtension $templateExtension,
        private readonly Environment $environment,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('description', TextareaType::class, ['required' => false, 'label' => false]);

        $builder->add('token', UpdateAvitoProductsDescriptionTokenForm::class, ['label' => false]);


        /** Рендеринг шаблона, если описание NULL */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event): void {

                /** @var UpdateAvitoProductsDescriptionDTO $UpdateAvitoProductsDescriptionDTO */
                $UpdateAvitoProductsDescriptionDTO = $event->getData();

                /** Получаем ID текущего профиля пользователя для составления пути для шаблона */
                $userProfile = $this->userProfileTokenStorage->getProfile();

                /** Проверка существования шаблона в src - если нет, то дефолтный шаблон из модуля */
                try
                {
                    $path = sprintf('@avito-products:description/%s/default.html.twig', $userProfile);

                    $template = $this->templateExtension->extends($path);
                    $render = $this->environment->render($template);
                }
                catch(Exception)
                {
                    $template = $this->templateExtension->extends('@avito-products:description/default.html.twig');
                    $render = $this->environment->render($template);
                }

                if(is_null($UpdateAvitoProductsDescriptionDTO->getDescription()))
                {
                    $UpdateAvitoProductsDescriptionDTO->setDescription($render);
                }

            });


        /* Сохранить ******************************************************/
        $builder->add(
            'avito_products_description_update',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateAvitoProductsDescriptionDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}