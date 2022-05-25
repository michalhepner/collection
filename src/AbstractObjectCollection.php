<?php
declare(strict_types=1);

namespace MichalHepner\Collection;

use InvalidArgumentException;

class AbstractObjectCollection extends AbstractCollection
{
    protected ?string $class;

    public function __construct(iterable $items = [], ?string $class = null)
    {
        $this->class = $class;

        parent::__construct($items);
    }

    protected function validateItem($item): void
    {
        if (!is_object($item) || ($this->class !== null && !$item instanceof $this->class)) {
            throw new InvalidArgumentException('Invalid item provided to collection');
        }
    }
}
