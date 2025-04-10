<?php

namespace IBroStudio\PipedTasks\Transformers;

use Attribute;
use IBroStudio\DataRepository\ValueObjects\ValueObject;

#[Attribute]
final class ValueObjectTransformer implements TransformerContract
{
    public function __construct(
        public string $class,
        public mixed $value
    ) {
        if (! is_subclass_of($this->class, ValueObject::class)) {
            throw new \InvalidArgumentException("Class {$this->class} is not a value object class");
        }
    }

    public function transform(): ?ValueObject
    {
        if (is_subclass_of($this->value, ValueObject::class) || is_null($this->value)) {
            return $this->value;
        }

        return $this->class::from($this->value);
    }
}
