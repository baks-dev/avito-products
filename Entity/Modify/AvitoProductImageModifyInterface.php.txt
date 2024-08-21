<?php

namespace BaksDev\Avito\Products\Entity\Modify;

use BaksDev\Core\Type\Modify\ModifyAction;

interface AvitoProductImageModifyInterface
{
    public function getAction(): ModifyAction;
}
