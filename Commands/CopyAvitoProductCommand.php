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

namespace BaksDev\Avito\Products\Commands;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Avito\Products\Entity\Profile\AvitoProductProfile;
use BaksDev\Avito\Products\Repository\AvitoProductProfile\AvitoProductProfileInterface;
use BaksDev\Avito\Products\Type\Id\AvitoProductUid;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductDTO;
use BaksDev\Avito\Products\UseCase\NewEdit\AvitoProductHandler;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Files\Resources\Messenger\Request\Images\CDNUploadImageMessage;
use BaksDev\Ozon\Products\Entity\Custom\Images\OzonProductCustomImage;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Yandex\Market\Products\Entity\Custom\Images\YandexMarketProductCustomImage;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'baks:avito-products:copy',
    description: 'Комманда отправляет на CDN файлы изображений '
)]
class CopyAvitoProductCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $upload,
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly AvitoProductProfileInterface $AvitoProductProfileRepository,
        private readonly AvitoProductHandler $AvitoProductHandler
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $progressBar = new ProgressBar($output);
        $progressBar->start();

        /**
         * Обрабатываем файлы по базе данных
         */

        $OriginalUserProfileUid = new UserProfileUid('018e9e8f-9a83-7af7-a904-f34b393d69bf');
        $CopyUserProfileUid = new UserProfileUid('0197a337-ebc0-767d-9a41-d90ef0845695');

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('main')
            ->from(AvitoProduct::class, 'main');

        $orm
            ->join(
                AvitoProductProfile::class,
                'profile',
                'WITH',
                'profile.avito = main.id AND profile.value = :original',
            )
            ->setParameter(
                key: 'original',
                value: $OriginalUserProfileUid,
                type: UserProfileUid::TYPE,
            );


        $AvitoProducts = $orm->getResult();


        /** @var AvitoProduct $AvitoProduct */
        foreach($AvitoProducts as $AvitoProduct)
        {

            $AvitoProductDTO = new AvitoProductDTO();
            $AvitoProduct->getDto($AvitoProductDTO);

            /** Определяем имеющуюся настройку  */

            $AvitoProduct = $this->AvitoProductProfileRepository
                ->product($AvitoProductDTO->getProduct())
                ->offerConst($AvitoProductDTO->getOffer())
                ->variationConst($AvitoProductDTO->getVariation())
                ->modificationConst($AvitoProductDTO->getModification())
                ->kit($AvitoProductDTO->getKit()->getValue())
                ->forProfile($CopyUserProfileUid)
                ->find();

            if($AvitoProduct instanceof AvitoProduct)
            {
                $AvitoProductDTO->setId($AvitoProduct->getId());
            }
            else
            {
                $AvitoProductDTO->setId(new AvitoProductUid());
            }

            /** Присваиваем идентификатор профиля */
            $AvitoProductDTO->getProfile()->setValue($CopyUserProfileUid);

            $this->AvitoProductHandler->handle($AvitoProductDTO);

            $progressBar->advance();

        }

        $progressBar->finish();

        $io->success('Команда успешно завершена');

        return Command::SUCCESS;
    }
}
