<?php

declare(strict_types = 1);

namespace MichalHepner\Collection\Exception;

use RuntimeException;
use Throwable;

class NonUniqueCollectionItemMatchingException extends RuntimeException
{
    /**
     * @var array
     */
    protected $matchedItems;

    public function __construct(array $matchedItems, string $message = "", int $code = 0, Throwable $previous = null)
    {
        $this->matchedItems = $matchedItems;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getMatchedItems(): array
    {
        return $this->matchedItems;
    }
}
