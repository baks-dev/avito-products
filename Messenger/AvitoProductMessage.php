<?php

declare(strict_types=1);

namespace BaksDev\Avito\Products\Messenger;

final class AvitoProductMessage
{
    /**
     * Идентификатор
     */
    private AvitoProduct $id;

    /**
     * Идентификатор события
     */
    private AvitoProductEventUid $event;

    /**
     * Идентификатор предыдущего события
     */
    private ?AvitoProductEventUid $last;


    public function __construct(AvitoProduct $id, AvitoProductEventUid $event, ?AvitoProductEventUid $last = null)
    {
        $this->id = $id;
        $this->event = $event;
        $this->last = $last;
    }


    /**
     * Идентификатор
     */
    public function getId(): AvitoProduct
    {
        return $this->id;
    }


    /**
     * Идентификатор события
     */
    public function getEvent(): AvitoProductEventUid
    {
        return $this->event;
    }


    /**
     * Идентификатор предыдущего события
     */
    public function getLast(): ?AvitoProductEventUid
    {
        return $this->last;
    }

}
