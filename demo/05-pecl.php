<?php

declare(strict_types=1);

namespace Ray\Aop\Demo;

use Ray\Aop\Aspect;
use Ray\Aop\Matcher;
use RuntimeException;

use const PHP_EOL;

require __DIR__ . '/bootstrap.php';

$aspect = new Aspect(__DIR__ . '/tmp');
$aspect->bind(
    (new Matcher())->any(),
    new IsContainsMatcher('charge'),
    [new WeekendBlocker()]
);
$aspect->weave(__DIR__. '/src');

try {
    $billingService = new RealBillingService();
    echo $billingService->chargeOrder();
} catch (RuntimeException $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}
