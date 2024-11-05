<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use function array_map;
use function assert;
use function class_exists;
use function implode;
use function sprintf;

/**
 * TypeString converts ReflectionType instances to their corresponding string representations.
 */
final class TypeString
{
    /**
     * @var string
     * @readonly
     */
    private $nullableStr;

    /**
     * @var bool
     * @readonly
     */
    private $hasUnionType;

    public function __construct(string $nullableStr)
    {
        $this->nullableStr = $nullableStr;
        $this->hasUnionType = class_exists('ReflectionUnionType');
    }

    /** @psalm-external-mutation-free */
    public function __invoke(?ReflectionType $type): string
    {
        if (! $type) {
            return '';
        }

        // PHP 8.0+
        if ($this->hasUnionType && $type instanceof ReflectionUnionType) {
            return $this->getUnionType($type);
        }

        if ($type instanceof ReflectionNamedType) {
            $typeStr = self::getFqnType($type);
            // Check for Nullable in single types
            if ($typeStr !== 'mixed' && $type->allowsNull() && $type->getName() !== 'null') {
                $typeStr = $this->nullableStr . $typeStr;
            }

            return $typeStr;
        }

        assert($type instanceof ReflectionIntersectionType);

        return $this->intersectionTypeToString($type);
    }

    /** @psalm-pure */
    private function intersectionTypeToString(ReflectionIntersectionType $intersectionType): string
    {
        $types = $intersectionType->getTypes();
        /** @var array<ReflectionNamedType> $types */
        $typeStrings = array_map(static function (ReflectionNamedType $type): string {
            return '\\' . $type->getName();
        }, $types);

        return implode(' & ', $typeStrings);
    }

    public function getUnionType(ReflectionUnionType $type): string
    {
        $types = array_map(static function ($t) {
            /** @psalm-suppress DocblockTypeContradiction */
            if ($t instanceof ReflectionIntersectionType) {
                $types = $t->getTypes();
                /** @var array<ReflectionNamedType>  $types */
                $intersectionTypes = array_map(static function (ReflectionNamedType $t): string {
                    return self::getFqnType($t);
                }, $types);

                return sprintf('(%s)', implode('&', $intersectionTypes));
            }

            return self::getFqnType($t);
        }, $type->getTypes());

        return implode('|', $types);
    }

    private static function getFqnType(ReflectionNamedType $namedType): string
    {
        $type = $namedType->getName();
        $isBuiltin = $namedType->isBuiltin() || $type === 'static' || $type === 'self';

        return $isBuiltin ? $type : '\\' . $type;
    }
}
