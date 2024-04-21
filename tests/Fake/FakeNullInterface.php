<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionMethod;

use function assert;
use function call_user_func_array;
use function func_get_args;
use function is_callable;

interface FakeNullInterface
{
}
