<?php
namespace Phlopsi\ExceptionTranslator\Reflection;

/**
 * @author Patrick Fischer <nbphobos@gmail.com>
 */
class ExceptionReflection extends \ReflectionClass
{
    /**
     * @param mixed $argument
     *
     * @throws \DomainException
     */
    public function __construct($argument)
    {
        try {
            parent::__construct($argument);
        } catch (\ReflectionException $exception) {
            throw new \DomainException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!(\Exception::class === $this->name || $this->isSubclassOf(\Exception::class))) {
            throw new \DomainException(\sprintf('Class %s is not an Exception', $this->name));
        }
    }
}
