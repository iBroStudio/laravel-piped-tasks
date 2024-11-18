<?php

namespace IBroStudio\PipedTasks\Transformers;

use Attribute;
use Spatie\LaravelData\Data;

#[Attribute]
final class DataTransformer
{
    public function __construct(
        public string $class,
        public Data|array $value
    ) {
        if (! is_subclass_of($this->class, Data::class)) {
            throw new \InvalidArgumentException("Class {$this->class} is not a data class");
        }
    }

    public function transform(): Data
    {
        if (is_subclass_of($this->value, Data::class)) {
            return $this->value;
        }

        return $this->class::from($this->value);
    }
}
