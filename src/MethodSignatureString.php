<?php

declare(strict_types=1);

namespace Ray\Aop;

use Reflection;
use ReflectionMethod;
use ReflectionParameter;
use UnitEnum;

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
    private const PHP_VERSION_8 = 80000;
    private const NULLABLE_PHP8 = 'null|';
    private const NULLABLE_PHP7 = '?';
    private const INDENT = '    ';

    /** @var TypeString */
    private $typeString;

    public function __construct(int $phpVersion)
    {
        $nullableStr = $phpVersion >= self::PHP_VERSION_8 ? self::NULLABLE_PHP8 : self::NULLABLE_PHP7;
        $this->typeString = new TypeString($nullableStr);
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedMethodCall
     */
    public function get(ReflectionMethod $method): string
    {
        $signatureParts = $this->getDocComment($method);
        $this->addAttributes($method, $signatureParts);
        $this->addAccessModifiers($method, $signatureParts);
        $this->addMethodSignature($method, $signatureParts);

        return implode(' ', $signatureParts);
    }

    /** @return array<string> */
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

        /** @psalm-suppress MixedAssignment */
        foreach ($method->getAttributes() as $attribute) {
            $argsList = $attribute->getArguments();
            $formattedArgs = [];
            foreach ($argsList as $name => $value) {
                $formattedArgs[] = $this->representArg($name, $value);
            }

            $signatureParts[] = sprintf('    #[\\%s(%s)]', $attribute->getName(), implode(', ', $formattedArgs)) . PHP_EOL;
        }

        if (empty($signatureParts)) {
            return;
        }

        $signatureParts[] = self::INDENT;
    }

    /** @param array<string> $signatureParts */
    private function addAccessModifiers(ReflectionMethod $method, array &$signatureParts): void
    {
        $modifier = implode(' ', Reflection::getModifierNames($method->getModifiers()));

        $signatureParts[] = $modifier;
    }

    /** @param array<string> $signatureParts */
    private function addMethodSignature(ReflectionMethod $method, array &$signatureParts): void
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = $this->generateParameterCode($param);
        }

        $parmsList = implode(', ', $params);
        $rType = $method->getReturnType();
        $return = $rType ? ': ' . ($this->typeString)($rType) : '';

        $signatureParts[] = sprintf('function %s(%s)%s', $method->getName(), $parmsList, $return);
    }

    /**
     * @param string|int $name
     * @param mixed      $value
     */
    private function representArg($name, $value): string
    {
        $formattedValue = $value instanceof UnitEnum ?
            '\\' . var_export($value, true)
            : preg_replace('/\s+/', ' ', var_export($value, true));

        return is_numeric($name) ? $formattedValue : "{$name}: {$formattedValue}";
    }

    private function generateParameterCode(ReflectionParameter $param): string
    {
        $typeStr = ($this->typeString)($param->getType());
        $typeStrWithSpace = $typeStr ? $typeStr . ' ' : $typeStr;
        $variadicStr = $param->isVariadic() ? '...' : '';
        $referenceStr = $param->isPassedByReference() ? '&' : '';
        $defaultStr = '';
        if ($param->isDefaultValueAvailable()) {
            $default = var_export($param->getDefaultValue(), true);
            $defaultStr = ' = ' . str_replace(["\r", "\n"], '', $default);
        }

        return "{$typeStrWithSpace}{$referenceStr}{$variadicStr}\${$param->getName()}{$defaultStr}";
    }
}
