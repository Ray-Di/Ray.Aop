<?php

declare(strict_types=1);

namespace Ray\Aop;

/** doc comment of FakeMock */
class  FakeWeaverMock_2665014008 extends FakeWeaverMock implements \Ray\Aop\WeavedInterface{
    use \Ray\Aop\InterceptTrait;
    /**
     * doc comment of returnSame
     */
      public function returnSame($a)
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }

    /**
     * doc comment of getSub
     */
      public function getSub($a, $b)
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }

    public function returnValue(?\Ray\Aop\FakeNum $num = NULL)
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }

    public function getPrivateVal()
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
