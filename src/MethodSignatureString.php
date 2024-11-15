<?php

declare(strict_types=1);

namespace Ray\Aop;

use Reflection;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionParameter;
use UnitEnum;

use function array_merge;
use function implode;
use function is_numeric;
use function is_string;
use function preg_replace;
use function sprintf;
use function str_replace;
use function var_export;

use const PHP_EOL;
use const PHP_MAJOR_VERSION;

final class MethodSignatureString
{
    private const NULLABLE_PHP8 = 'null|';
    private const NULLABLE_PHP7 = '?';
    private const INDENT = '    ';

    /** @var TypeString  */
    private $typeString;

    public function __construct(int $phpVersion)
    {
        $nullableStr = $phpVersion >= 80000 ? self::NULLABLE_PHP8 : self::NULLABLE_PHP7;
        $this->typeString = new TypeString($nullableStr);
    }

    /** @psalm-external-mutation-free  */
    public function get(ReflectionMethod $method): string
    {
        $signatureParts = $this->getDocComment($method);
        $this->addAttributes($method, $signatureParts);
        $modiferedSignatureParts = $this->addAccessModifiers($method, $signatureParts);
        $methodSignatureParts = $this->addMethodSignature($method, $modiferedSignatureParts);

        return implode(' ', $methodSignatureParts);
    }

    /**
     * @return array<string>
     *
     * @psalm-pure
     */
    private function getDocComment(ReflectionMethod $method): array
    {
        $docComment = $method->getDocComment();

        return is_string($docComment) ? [$docComment . PHP_EOL] : [];
    }

    /** @param array<string> $signatureParts */
    private function addAttributes(ReflectionMethod $method, array &$signatureParts): void
    {
        if (PHP_MAJOR_VERSION < 8) {
            return;
        }

        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            $signatureParts[] = sprintf('    #[%s]', $this->formatAttributeStr($attribute)) . PHP_EOL;
        }

        if (empty($signatureParts)) {
            return;
        }

        $signatureParts[] = self::INDENT;
    }

    /** @param ReflectionAttribute<object> $attribute */
    private function formatAttributeStr(ReflectionAttribute $attribute): string
    {
        $argsList = $attribute->getArguments();
        $formattedArgs = [];
        /** @var scalar $value */
        foreach ($argsList as $name => $value) {
            $formattedArgs[] = $this->formatArg($name, $value);
        }

        return sprintf('\\%s(%s)', $attribute->getName(), implode(', ', $formattedArgs));
    }

    /**
     * @param array<string> $signatureParts
     *
     * @return array<string>
     *
     * @psalm-pure
     */
    private function addAccessModifiers(ReflectionMethod $method, array $signatureParts): array
    {
        $modifier = implode(' ', Reflection::getModifierNames($method->getModifiers()));

        return array_merge($signatureParts, [$modifier]);
    }

    /**
     * @param array<string> $signatureParts
     *
     * @return array<string>
     */
    private function addMethodSignature(ReflectionMethod $method, array $signatureParts): array
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = $this->generateParameterCode($param);
        }

        $parmsList = implode(', ', $params);
        $rType = $method->getReturnType();
        $return = $rType ? ': ' . ($this->typeString)($rType) : '';

        $signatureParts[] = sprintf('function %s(%s)%s', $method->getName(), $parmsList, $return);

        return $signatureParts;
    }

    /**
     * @param string|int $name
     * @param mixed      $value
     *
     * @psalm-external-mutation-free
     * @psalm-pure
     */
    private function formatArg($name, $value): string
    {
        $formattedValue = $value instanceof UnitEnum ?
            '\\' . var_export($value, true)
            : preg_replace('/\s+/', '', var_export($value, true));

        return is_numeric($name) ? (string) $formattedValue : "{$name}: {$formattedValue}";
    }

    private function generateParameterCode(ReflectionParameter $param): string
    {
        $attributesStr = $this->getAttributeStr($param);
        $typeStr = ($this->typeString)($param->getType());
        $typeStrWithSpace = $typeStr ? $typeStr . ' ' : $typeStr;
        $variadicStr = $param->isVariadic() ? '...' : '';
        $referenceStr = $param->isPassedByReference() ? '&' : '';
        $defaultStr = '';
        if ($param->isDefaultValueAvailable()) {
            $default = var_export($param->getDefaultValue(), true);
            $defaultStr = ' = ' . str_replace(["\r", "\n"], '', $default);
        }

        return "{$attributesStr}{$typeStrWithSpace}{$referenceStr}{$variadicStr}\${$param->getName()}{$defaultStr}";
    }

    public function getAttributeStr(ReflectionParameter $param): string
    {
        if (PHP_MAJOR_VERSION < 8) {
            return '';
        }

        $attributesStr = '';
        $attributes = $param->getAttributes();
        if (! empty($attributes)) {
            $attributeStrings = [];
            foreach ($attributes as $attribute) {
                $attributeStrings[] = sprintf('#[%s]', $this->formatAttributeStr($attribute));
            }

            $attributesStr = implode(' ', $attributeStrings) . ' ';
        }

        return $attributesStr;
    }
}
