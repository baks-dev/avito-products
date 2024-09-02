<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\Security;

use BaksDev\Menu\Admin\Command\Upgrade\MenuAdminInterface;
use BaksDev\Menu\Admin\Type\SectionGroup\Group\Collection\MenuAdminSectionGroupCollectionInterface;
use BaksDev\Orders\Order\Security\MenuGroupMarketplace;
use BaksDev\Users\Profile\Group\Security\RoleInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('baks.security.role')]
#[AutoconfigureTag('baks.menu.admin')]
final class Role implements RoleInterface, MenuAdminInterface
{
    /** Транспорт доставки заказов */
    public const string ROLE = 'ROLE_AVITO_PRODUCTS';

    public function getRole(): string
    {
        return self::ROLE;
    }
    /**
     * Добавляем раздел в меню администрирования.
     */

    /** Метод возвращает PATH раздела */
    public function getPath(): string
    {
        return 'avito-products:admin.products.index';
    }

    /**
     * Метод возвращает секцию, в которую помещается ссылка на раздел.
     */
    public function getGroupMenu(): MenuAdminSectionGroupCollectionInterface|bool
    {
        return new MenuGroupMarketplace();
    }

    /**
     * Метод возвращает позицию, в которую располагается ссылка в секции меню.
     */
    public function getSortMenu(): int
    {
        return 441;
    }

    /**
     * Метод возвращает флаг "Показать в выпадающем меню".
     */
    public function getDropdownMenu(): bool
    {
        return true;
    }

    /**
     * Метод возвращает флаг "Модальное окно".
     */
    public function getModal(): bool
    {
        return false;
    }
}
