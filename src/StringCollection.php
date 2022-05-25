<?php

declare(strict_types = 1);

namespace MichalHepner\Collection;

use InvalidArgumentException;

class StringCollection extends AbstractCollection
{
    protected function validateItem($item): void
    {
        if (is_null($item) && !is_string($item)) {
            throw new InvalidArgumentException('Item provided to %s must be a string or null', __CLASS__);
        }
    }

    public function toLower(): StringCollection
    {
        $clone = $this->getCleanClone();
        foreach ($this->items as $item) {
            $clone->add(strtolower($item));
        }

        return $this;
    }

    public function sort(): StringCollection
    {
        sort($this->items);

        return $this;
    }
}
