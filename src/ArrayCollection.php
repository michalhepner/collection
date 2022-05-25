<?php

declare(strict_types = 1);

namespace MichalHepner\Collection;

use InvalidArgumentException;

class ArrayCollection extends AbstractCollection
{
    protected function validateItem($item): void
    {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Invalid item provided to collection');
        }
    }
}
