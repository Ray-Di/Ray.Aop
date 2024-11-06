# Ray.Aop

## Aspect Oriented Framework

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Aop/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/Ray-Di/Ray.Aop/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Aop/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Aop)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Aop/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Aop)
[![Continuous Integration](https://github.com/ray-di/Ray.Aop/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.Aop/actions/workflows/continuous-integration.yml)
[![Total Downloads](https://poser.pugx.org/ray/aop/downloads)](https://packagist.org/packages/ray/aop)

<img src="https://ray-di.github.io/images/logo.svg" alt="ray-di logo" width="150px;">

[\[Japanese\]](https://github.com/ray-di/Ray.Aop/blob/2.x/README.ja.md)


**Ray.Aop** package provides method interception. This feature enables you to write code that is executed each time a matching method is invoked. It's suited for cross cutting concerns ("aspects"), such as transactions, security and logging. Because interceptors divide a problem into aspects rather than objects, their use is called Aspect Oriented Programming (AOP).


A [Matcher](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MatcherInterface.php) is a simple interface that either accepts or rejects a value. For Ray.AOP, you need two matchers: one that defines which classes participate, and another for the methods of those classes. To make this easy, there's factory class to satisfy the common scenarios.

[MethodInterceptors](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MethodInterceptor.php) are executed whenever a matching method is invoked. They have the opportunity to inspect the call: the method, its arguments, and the receiving instance. They can perform their cross-cutting logic and then delegate to the underlying method. Finally, they may inspect the return value or exception and return. Since interceptors may be applied to many methods and will receive many calls, their implementation should be efficient and unintrusive.

## Example: Forbidding method calls on weekends

To illustrate how method interceptors work with Ray.Aop, we'll forbid calls to our pizza billing system on weekends. The delivery guys only work Monday thru Friday so we'll prevent pizza from being ordered when it can't be delivered! This example is structurally similar to use of AOP for authorization.

To mark select methods as weekdays-only, we define an attribute.

```php
<?php
#[Attribute(Attribute::TARGET_METHOD)]
final class NotOnWeekends
{
}
```

...and apply it to the methods that need to be intercepted:

```php
<?php
class RealBillingService
{
    #[NotOnWeekends] 
    public function chargeOrder(PizzaOrder $order, CreditCard $creditCard)
    {
```

Next, we define the interceptor by implementing the org.aopalliance.intercept.MethodInterceptor interface. When we need to call through to the underlying method, we do so by calling `$invocation->proceed()`:

```php
<?php
class WeekendBlocker implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        $today = getdate();
        if ($today['weekday'][0] === 'S') {
            throw new \RuntimeException(
                $invocation->getMethod()->getName() . " not allowed on weekends!"
            );
        }
        return $invocation->proceed();
    }
}
```

Finally, we configure everything using the `Aspect` class:

```php
use Ray\Aop\Aspect;
use Ray\Aop\Matcher;

$aspect = new Aspect();
$aspect->bind(
    (new Matcher())->any(),
    (new Matcher())->annotatedWith(NotOnWeekends::class),
    [new WeekendBlocker()]
);

$billing = $aspect->newInstance(RealBillingService::class);
try {
    echo $billing->chargeOrder(); // Interceptors applied
} catch (\RuntimeException $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
```

Putting it all together, (and waiting until Saturday), we see the method is intercepted and our order is rejected:

```
chargeOrder not allowed on weekends!
```

## PECL Extension

Ray.Aop also supports a [PECL extension](https://github.com/ray-di/ext-rayaop). When the extension is installed, you can use the `weave` method to apply aspects to all classes in a directory:

```php
$aspect = new Aspect();
$aspect->bind(
    (new Matcher())->any(),
    (new Matcher())->annotatedWith(NotOnWeekends::class),
    [new WeekendBlocker()]
);
// Weave aspects into all matching classes in the source directory
$aspect->weave('/path/to/src');

// Or weave into specific target directory
$aspect->weave('/path/to/target');

$billing = new RealBillingService();
echo $billing->chargeOrder(); // Interceptors applied
```

With the PECL extension:
- You can create new instances anywhere in your code using the normal `new` keyword.
- Interception works even with `final` classes and methods.

To use these features, simply install the PECL extension and Ray.Aop will automatically utilize it when available. PHP 8.1+ is required for the PECL extension.

### Installing the PECL extension

PHP 8.1 or higher is required to use the PECL extension. For more information, see [ext-rayaop](https://github.com/ray-di/ext-rayaop?tab=readme-ov-file#installation).

## Configuration Options

When creating an `Aspect` instance, you can optionally specify a temporary directory:

```php
$aspect = new Aspect('/path/to/tmp/dir');
```

If not specified, the system's default temporary directory will be used.

This concludes the basic usage of Ray.Aop. For more detailed information and advanced usage, please refer to the full documentation.

## Own matcher

You can have your own matcher.
To create `contains` matcher, You need to provide a class which have two method. One is `matchesClass` for class match.
The other one is `matchesMethod` method match. Both return the boolean result of matched.

```php
use Ray\Aop\AbstractMatcher;
use Ray\Aop\Matcher;

class IsContainsMatcher extends AbstractMatcher
{
    /**
     * {@inheritdoc}
     */
    public function matchesClass(\ReflectionClass $class, array $arguments) : bool
    {
        [$contains] = $arguments;

        return (strpos($class->name, $contains) !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function matchesMethod(\ReflectionMethod $method, array $arguments) : bool
    {
        [$contains] = $arguments;

        return (strpos($method->name, $contains) !== false);
    }
}
```

## Interceptor Details

In an interceptor, a `MethodInvocation` object is passed to the `invoke` method:

```php
class MyInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        // Before method invocation
        $result = $invocation->proceed();
        // After method invocation
        return $result;
    }
}
```

$invocation->proceed() invokes the next interceptor in the chain. If no more interceptors are present, it calls the target method. This chaining allows multiple interceptors for a single method, executing in the order bound.

Example execution flow for interceptors A, B, and C:

1. Interceptor A (before)
2. Interceptor B (before)
3. Interceptor C (before)
4. Target method
5. Interceptor C (after)
6. Interceptor B (after)
7. Interceptor A (after)

This chaining mechanism allows you to combine multiple cross-cutting concerns (like logging, security, and performance monitoring) for a single method.

With the `MethodInvocation` object, you can:

* [`$invocation->proceed()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Joinpoint.php#L41) - Invoke method
* [`$invocation->getMethod()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MethodInvocation.php#L30) -  Get method reflection
* [`$invocation->getThis()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Joinpoint.php#L50) - Get object
* [`$invocation->getArguments()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Invocation.php#L25) - Get parameters
* [`$invocation->getNamedArguments()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Invocation.php#L32) - Get named parameters
  An extended `ClassRefletion` and `MethodReflection` holds methos to get PHP 8 attribute and doctrine annotation(s) .

```php
/** @var $method \Ray\Aop\ReflectionMethod */
$method = $invocation->getMethod();
/** @var $class \Ray\Aop\ReflectionClass */
$class = $invocation->getMethod()->getDeclaringClass();
```

* `$method->getAnnotations()`     - Get method attributes/annotations
* `$method->getAnnotation($name)` - Get method attribute/annotation
* `$class->->getAnnotations()`    - Get class attributes/annotations
* `$class->->getAnnotation($name)`     - Get class attributes/annotation

## Annotation/Attribute

Ray.Aop can be used either with [doctrine/annotation](https://github.com/doctrine/annotations) in PHP 7/8 or with an [Attributes](https://www.php.net/manual/en/language.attributes.overview.php) in PHP8.

## AOP Alliance

The method interceptor API implemented by Ray.Aop is a part of a public specification called [AOP Alliance](http://aopalliance.sourceforge.net/doc/org/aopalliance/intercept/MethodInterceptor.html).

## Installation

The recommended way to install Ray.Aop is through [Composer](https://github.com/composer/composer).

```bash
# Add Ray.Aop as a dependency
$ composer require ray/aop ^2.0
```

### PHP8 attributes only (recommended)

```php
SevericeLocator::setReader(new AttributeReader);`
```

## Integrated DI framework

* See also the DI framework [Ray.Di](https://github.com/ray-di/Ray.Di) which integrates DI and AOP.

## Stability

Ray.Aop follows semantic versioning and ensures backward compatibility. Released in 2015, version 2.0 and its successors have maintained compatibility while evolving with PHP, and we remain committed to this stability.

---

* Note: This documentation of the part is taken from [Guice/AOP](https://github.com/google/guice/wiki/AOP).
  z1
