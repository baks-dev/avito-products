<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Products\Messenger\Schedules;

use BaksDev\Avito\Board\Repository\AllProductsWithMapper\AllProductsWithMapperInterface;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Twig\TemplateExtension;
use DateInterval;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
final readonly class RefreshFeedHandler
{
    public function __construct(
        private AllProductsWithMapperInterface $allProductsWithMapping,
        private AppCacheInterface $cache,
        private TemplateExtension $templateExtension,
        private Environment $environment,
    ) {}

    public function __invoke(RefreshFeedMessage $message): void
    {
        $profile = $message->getProfile();

        $products = $this->allProductsWithMapping->findAll($profile);

        $cache = $this->cache->init('avito-board');

        $cachePool = $cache->getItem('feed-' . $profile);

        if (false === $cachePool->isHit())
        {
            $template = $this->templateExtension->extends('@avito-board:public/export/feed/export.html.twig');

            $feed = $this->environment->render($template, [$products]);

            $cachePool->expiresAfter(DateInterval::createFromDateString('1 day'));

            $cachePool->set($feed);
            $cache->save($cachePool);
        }
    }
}
