<?php
require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/sample-01-quick-weave/BillingService.php';
require __DIR__ . '/sample-01-quick-weave/RealBillingService.php';
require __DIR__ . '/sample-01-quick-weave/WeekendBlocker.php';

require __DIR__ . '/sample-02-multiple-intercepters/intercepterA.php';
require __DIR__ . '/sample-02-multiple-intercepters/intercepterB.php';
require __DIR__ . '/sample-02-multiple-intercepters/Timer.php';

require __DIR__ . '/sample-03-benchmark/EmptyInterceptor.php';

require __DIR__ . '/sample-04-annotation/WeekendBlock.php';
require __DIR__ . '/sample-04-annotation/AnnotationRealBillingService.php';

require __DIR__ . '/sample-05-my-matcher/MyMatcher.php';
