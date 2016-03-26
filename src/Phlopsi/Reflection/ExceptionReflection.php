<?php
declare(strict_types=1);

namespace Phlopsi\Reflection;

/**
 * @author Patrick Fischer <nbphobos@gmail.com>
 */
class ExceptionReflection extends \ReflectionClass
{
    /**
     * @param mixed $argument
     */
    public function __construct($argument)
    {
        try {
            parent::__construct($argument);
        } catch (\ReflectionException $exception) {
            throw new \DomainException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!(\Exception::class === $this->name || $this->isSubclassOf(\Exception::class))) {
            throw new \DomainException(\sprintf('Class `%s` is not an `Exception`', $this->name));
        }
    }
}
