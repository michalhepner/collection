<?php
declare(strict_types=1);

namespace MichalHepner\Collection;

class ObjectCollection extends AbstractObjectCollection
{
    public function __construct(iterable $items = [])
    {
        parent::__construct($items, preg_replace('/Collection$/', '', get_class($this)));
    }
}
