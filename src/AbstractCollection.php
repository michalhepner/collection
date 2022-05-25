<?php

declare(strict_types = 1);

namespace MichalHepner\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use MichalHepner\Collection\Exception\CollectionItemMatchingException;
use MichalHepner\Collection\Exception\NonUniqueCollectionItemMatchingException;
use RuntimeException;
use Traversable;

/**
 * @template T
 */
abstract class AbstractCollection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var T[]
     */
    protected array $items = [];

    public function __construct(iterable $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add($item): self
    {
        $this->validateItem($item);

        $this->items[] = $item;

        return $this;
    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->items)) {
            throw new RuntimeException(sprintf('Element with offset %s was not found in collection', $key));
        }

        return $this->items[$key];
    }

    public function set($offset, $value): self
    {
        $this->validateItem($value);

        $this->items[$offset] = $value;

        return $this;
    }

    public function exists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function has($item): bool
    {
        foreach ($this->items as $tmpItem) {
            if ($tmpItem === $item) {
                return true;
            }
        }

        return false;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function map(callable $callback): array
    {
        $ret = [];
        foreach ($this->items as $key => $value) {
            $ret[$key] = $callback($value, $key);
        }

        return $ret;
    }

    public function reduce(callable $callback)
    {
        $accumulator = null;
        foreach ($this->items as $item) {
            $accumulator = $callback($accumulator, $item);
        }

        return $accumulator;
    }

    public function walk(callable $callable): self
    {
        array_walk($this->items, $callable);

        return $this;
    }

    public function split(callable $callback): self
    {
        $newCollection = $this->filter($callback);
        foreach ($newCollection as $newCollectionItem) {
            foreach ($this as $key => $currentCollectionItem) {
                if ($newCollectionItem === $currentCollectionItem) {
                    unset($currentCollectionItem[$key]);
                }
            }
        }

        !$newCollection->isEmpty() && $this->items = array_values($this->items);

        return $newCollection;
    }

    public function filter(?callable $callback = null): self
    {
        $clone = $this->getCleanClone();
        if ($callback) {
            foreach (array_filter($this->items, $callback) as $item) {
                $clone->add($item);
            }
        } else {
            foreach (array_filter($this->items) as $item) {
                $clone->add($item);
            }
        }

        return $clone;
    }

    public function join(self ...$collections): self
    {
        foreach ($collections as $collection) {
            foreach ($collection as $item) {
                $this->add($item);
            }
        }

        return $this;
    }

    public function first()
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return null;
    }

    public function last()
    {
        $tmpItem = null;
        foreach ($this->items as $item) {
            $tmpItem = $item;
        }

        return $tmpItem;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function group(callable $callback): self
    {
        $ret = new AbstractObjectCollection([], get_class($this));
        foreach ($this as $item) {
            $value = $callback($item);
            if (!is_int($value) && !is_string($value) && !is_float($value)) {
                throw new LogicException(sprintf('%s callback function must return a value that is either an int, string or float', __METHOD__));
            }

            if (!$ret->exists($value)) {
                $ret->set($value, $this->getCleanClone());
            }

            $ret->get($value)->add($item);
        }

        return $ret;
    }

    public function match(callable $callback): self
    {
        return $this->filter($callback);
    }

    public function matchOne(callable $callback)
    {
        $matches = $this->match($callback);

        if ($matches->isEmpty()) {
            throw new CollectionItemMatchingException('Unable to match object based on provided callback');
        } elseif ($matches->count() > 1) {
            throw new NonUniqueCollectionItemMatchingException($matches->getIterator()->getArrayCopy(), 'Matched more than one object based on provided callback');
        }

        return $matches->first();
    }

    public function remove($key): self
    {
        if (!array_key_exists($key, $this->items)) {
            throw new RuntimeException(sprintf('Element with offset %s was not found in collection', $key));
        }

        unset($this->items[$key]);

        return $this;
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function unshift($item): self
    {
        array_unshift($this->items, $item);

        return $this;
    }

    public function intersect(self $collection): self
    {
        return $this->filter(function ($item) use ($collection) {
            return $collection->has($item);
        });
    }

    public function diff(self $collection): self
    {
        return $this->filter(function ($item) use ($collection) {
            return !$collection->has($item);
        });
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function values(): array
    {
        return array_values($this->items);
    }

    public function unique(): self
    {
        $clone = $this->getCleanClone();
        foreach (array_unique($this->items) as $item) {
            $clone->add($item);
        }

        return $clone;
    }

    public function implode($separator): string
    {
        return implode($separator, $this->items);
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->exists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function usort(callable $callback): self
    {
        usort($this->items, $callback);

        return $this;
    }

    public function ksort(): self
    {
        ksort($this->items);

        return $this;
    }

    public function clear(): self
    {
        $this->items = [];

        return $this;
    }

    public function limit(int $count, int $offset = 0): self
    {
        $clone = $this->getCleanClone();

        $i = 0;
        foreach ($this as $item) {
            $i >= $offset && $i < $offset + $count && $clone->add($item);
            $i++;
        }

        return $clone;
    }

    public function each(callable $callable): self
    {
        foreach ($this->items as $key => $value) {
            $callable($value, $key);
        }

        return $this;
    }

    public function some(callable $callback): bool
    {
        foreach ($this as $item) {
            if ($callback($item)) {
                return true;
            }
        }

        return false;
    }

    public function every(callable $callback): bool
    {
        foreach ($this as $item) {
            if (!$callback($item)) {
                return false;
            }
        }

        return true;
    }

    protected function getCleanClone(): self
    {
        $clone = clone $this;
        $clone->clear();

        return $clone;
    }

    abstract protected function validateItem($item): void;
}
