<?php
/**
 * This file is part of the Ray.Aop package
 *
 * @package Ray.Aop
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Aop;

abstract class AbstractMatcher
{
    /**
     * Match CLASS
     *
     * @var bool
     */
    const TARGET_CLASS = true;

    /**
     * Match Method
     *
     * @var bool
     */
    const TARGET_METHOD = false;

    /**
     * Lazy match method
     *
     * @var string
     */
    protected $method;

    /**
     * Lazy match args
     *
     * @var array
     */
    protected $args;

    /**
     * Return match result
     *
     * @param string $class
     * @param bool   $target self::TARGET_CLASS | self::TARGET_METHOD
     *
     * @return bool | array [$matcher, method]
     */
    public function __invoke($class, $target)
    {
        $args = [$class, $target];
        $thisArgs = is_array($this->args) ? $this->args : [$this->args];
        foreach ($thisArgs as $arg) {
            $args[] = $arg;
        }
        $method = 'is' . $this->method;
        $matched = call_user_func_array([$this, $method], $args);

        return $matched;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = $this->method . ':' . json_encode($this->args);

        return $result;
    }
}
