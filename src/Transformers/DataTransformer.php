<?php

namespace IBroStudio\PipedTasks\Transformers;

use Attribute;
use Spatie\LaravelData\Data;

#[Attribute]
final class DataTransformer implements TransformerContract
{
    public function __construct(
        public string $class,
        public mixed $value
    ) {
        if (! is_subclass_of($this->class, Data::class)) {
            throw new \InvalidArgumentException("Class {$this->class} is not a data class");
        }
    }

    public function transform(): ?Data
    {
        if (is_subclass_of($this->value, Data::class) || is_null($this->value)) {
            return $this->value;
        }

        return $this->class::from($this->value);
    }
}
