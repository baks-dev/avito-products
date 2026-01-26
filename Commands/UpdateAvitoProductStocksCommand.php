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

namespace BaksDev\Avito\Products\Commands;


use BaksDev\Avito\Products\Messenger\ProductStocks\UpdateAvitoProductStockMessage;
use BaksDev\Avito\Products\Repository\AllProductsIdentifierByAvitoMapper\AllProductsWithAvitoMapperInterface;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllProfilesByActiveTokenInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Обновляет остатки продукции в объявлениях на Avito */
#[AsCommand(
    name: 'baks:avito-products:update:stocks',
    description: 'Обновляет остатки продукции в объявлениях на Avito',
    aliases: ['baks:avito:update:stocks']
)]
class UpdateAvitoProductStocksCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly AllProductsWithAvitoMapperInterface $allProductsWithAvitoMapper,
        private readonly AllProfilesByActiveTokenInterface $allProfilesByToken,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('article', 'a', InputOption::VALUE_OPTIONAL, 'Фильтр по артикулу ((--article=... || -a ...))');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** Получаем все активные профили, у которых активный токен Авито */
        $profiles = $this
            ->allProfilesByToken
            ->findAll();

        $profiles = iterator_to_array($profiles);

        $helper = $this->getHelper('question');

        $questions[] = 'Все';

        foreach($profiles as $quest)
        {
            $questions[] = $quest->getAttr();
        }

        $question = new ChoiceQuestion(
            'Профиль пользователя',
            $questions,
            0,
        );

        $profileName = $helper->ask($input, $output, $question);

        if($profileName === 'Все')
        {
            /** @var UserProfileUid $profile */
            foreach($profiles as $profile)
            {
                $this->update($profile, $input->getOption('article'));
            }
        }
        else
        {
            $UserProfileUid = null;

            foreach($profiles as $profile)
            {
                if($profile->getAttr() === $questions[$profileName])
                {
                    /* Присваиваем профиль пользователя */
                    $UserProfileUid = $profile;
                    break;
                }
            }

            if($UserProfileUid)
            {
                $this->update($UserProfileUid, $input->getOption('article'));
            }
        }

        $this->io->success('Авито: Остатки успешно обновлены');
        return Command::SUCCESS;
    }

    private function update(UserProfileUid $profile, ?string $article = null): void
    {
        $this->io->note(sprintf('Обновляем остатки у объявлений на Авито для профиля: %s', $profile->getAttr()));

        /** Ищем соответствие по артикулу или его части */
        if(true === is_string($article))
        {
            $this->allProductsWithAvitoMapper->byArticle($article);
        }

        /** Получаем продукты, на которые есть маппер Avito*/
        $avitoProducts = $this->allProductsWithAvitoMapper
            ->profile($profile)
            ->findAll();

        if(false === $avitoProducts || false === $avitoProducts->valid())
        {
            $this->io->warning('Не найдено продукты для обновления остатков в объявлениях на Авито');
            return;
        }

        foreach($avitoProducts as $product)
        {
            $updateAvitoProductStockMessage = new UpdateAvitoProductStockMessage(
                $profile,
                $product->getProductId(),
                $product->getProductOfferConst(),
                $product->getProductVariationConst(),
                $product->getProductModificationConst(),
            );

            $this->messageDispatch->dispatch($updateAvitoProductStockMessage);
            $this->io->text(sprintf('Обновили остатки у объявления с артикулом %s', $product->getProductArticle()));
        }
    }
}
