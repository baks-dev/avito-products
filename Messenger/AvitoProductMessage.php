<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\Messenger;

use BaksDev\Avito\Products\Type\AvitoProductUid;

final class AvitoProductMessage
{
    /**
     * Внутренний (системный) идентификатор продукта Avito
     */
    private AvitoProductUid $id;

    public function __construct(AvitoProductUid $id)
    {
        $this->id = $id;
    }

    public function getId(): AvitoProductUid
    {
        return $this->id;
    }
}
