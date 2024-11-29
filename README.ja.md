# Ray.Aop

## アスペクト指向フレームワーク

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Aop/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/Ray-Di/Ray.Aop/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Aop/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Aop)
[![Type Coverage](https://shepherd.dev/github/bearsunday/Ray.Aop/coverage.svg)](https://shepherd.dev/github/bearsunday/Ray.Aop)
[![Build Status](https://travis-ci.org/ray-di/Ray.Aop.svg?branch=2.x)](https://travis-ci.org/ray-di/Ray.Aop)
[![Total Downloads](https://poser.pugx.org/ray/aop/downloads)](https://packagist.org/packages/ray/aop)

[\[English\]](https://github.com/ray-di/Ray.Aop/blob/2.x/README.md)

**Ray.Aop** パッケージはメソッドインターセプションの機能を提供します。マッチするメソッドが実行される度に実行されるコードを記述する事ができます。トランザクション、セキュリティやログといった横断的な”アスペクト”に向いています。なぜならインターセプターが問題をオブジェクトというよりアスペクトに分けるからです。これらの用法はアスペクト指向プログラミング(AOP)と呼ばれます。

[Matcher](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MatcherInterface.php) は値を受け取ったり拒否したりするシンプルなインターフェイスです。例えばRay.Aopでは２つの **Matcher** が必要です。１つはどのクラスに適用するかを決め、もう１つはそのクラスのどのメソッドに適用するかを決めます。これらを簡単に利用するためのファクトリークラスがあります。

[MethodInterceptors](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MethodInterceptor.php) はマッチしたメソッドが呼ばれる度に実行されます。呼び出しやメソッド、それらの引き数、インスタンスを調べる事ができます。横断的なロジックと委譲されたメソッドが実行されます。最後に返り値を調べて返します。インターセプターは沢山のメソッドに適用され沢山のコールを受け取るので、実装は効果的で透過的なものになります。

## 例：平日のメソッドコールを禁止する

メソッドインターセプターがRay.Aopでどのように機能するかを明らかにするために、週末にはピザの注文を禁止するようにしてみましょう。デリバリーは平日だけ受け付ける事にして、ピザの注文を週末には受け付けないようにします！この例はAOPで認証を使用するときにのパターンと構造的に似ています。

週末だけにするための[アノテーション](http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/annotations.html)を定義します。

```php
<?php
#[Attribute(Attribute::TARGET_METHOD)]
final class NotOnWeekends
{
}
```

そして、インターセプトするメソッドに適用します。

```php
<?php
class RealBillingService
{
    #[NotOnWeekends] 
    public function chargeOrder(PizzaOrder $order, CreditCard $creditCard)
    {
```

次に、org.aopalliance.intercept.MethodInterceptorインターフェイスを実装したインターセプターを定義します。元のメソッドを実行するためには `$invocation->proceed()` と実行します。

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

設定完了しました。このコードでは「どのクラスでも」「メソッドに`@NotOnWeekends`アノテーションがある」という条件にマッチします。

```php
<?php

use Ray\Aop\Sample\Annotation\NotOnWeekends;
use Ray\Aop\Sample\Annotation\RealBillingService;

$aspect = new Aspect();
$aspect->bind(
    (new Matcher())->any(),
    (new Matcher())->annotatedWith(NotOnWeekends::class),
    [new WeekendBlocker()]
);

$billing = $aspect->newInstance(RealBillingService::class);
try {
    echo $billing->chargeOrder();
} catch (\RuntimeException $e) {
    echo $e->getMessage() . "\n";
}
```

全てをまとめ（土曜日まで待って）、メソッドをコールするとインターセプターにより拒否されます。

```
chargeOrder not allowed on weekends!
```

## PECL拡張

Ray.Aopは[PECL拡張](https://github.com/ray-di/ext-rayaop)もサポートしています。拡張機能がインストールされている場合、weaveメソッドを使用してディレクトリ内のすべてのクラスにアスペクトを適用できます。

```php
$aspect = new Aspect();
$aspect->bind(
    (new Matcher())->any(),
    (new Matcher())->annotatedWith(NotOnWeekends::class),
    [new WeekendBlocker()]
);
$aspect->weave(__DIR__ . '/src'); // ディレクトリ内でマッチャーに一致するすべてのクラスにアスペクトを織り込みます。

$billing = new RealBillingService();
echo $billing->chargeOrder(); // インターセプターが適用されます。
```

PECL拡張機能を使用すると：

通常のnewキーワードを使用してコードのどこでも新しいインスタンスを作成できます。
インターセプションはfinalクラスやメソッドでも動作します。
これらの機能を使用するには、PECL拡張機能をインストールするだけで、Ray.Aopが自動的に利用します。 

### PECL拡張のインストール

PECL拡張機能の利用にはPHP 8.1以上が必要です。詳細は[ext-rayaop](https://github.com/ray-di/ext-rayaop?tab=readme-ov-file#installation)を参照してください。

## 設定オプション
Aspectインスタンスを作成する際に、オプションで一時ディレクトリを指定できます。

```php
$aspect = new Aspect('/path/to/tmp/dir');
```
指定しない場合、システムのデフォルトの一時ディレクトリが使用されます。

## インターセプターの詳細

呼び出されたメソッドをそのまま実行するだけのインターセプターは以下のようになります。

```php
class MyInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        // メソッド呼び出し前
        $result = $invocation->proceed();
        // メソッド呼び出し後
        return $result;
    }
}
```

`$invocation->proceed()`はチェーン内の次のインターセプターを呼び出します。インターセプターが存在しない場合、ターゲットメソッドを呼び出します。このチェーンにより、単一のメソッドに対して複数のインターセプターを適用し、バインドされた順序で実行することができます。

インターセプターA、B、Cがメソッドにバインドされている場合の実行フローの例：

1. インターセプターA（前）
1. インターセプターB（前）
1. インターセプターC（前）
1. ターゲットメソッド
1. インターセプターC（後）
1. インターセプターB（後）
1. インターセプターA（後）


 * [`$invocation->proceed()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Joinpoint.php#L41) - メソッド実行
 * [`$invocation->getMethod()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/MethodInvocation.php#L30) -  メソッドリフレクションの取得
 * [`$invocation->getThis()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Joinpoint.php#L50) - オブジェクトの取得
 * [`$invocation->getArguments()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Invocation.php#L25) - 引数の取得
 * [`$invocation->getNamedArguments()`](https://github.com/ray-di/Ray.Aop/blob/2.x/src/Invocation.php#L32) - 名前付き引数の取得


拡張されたClassReflectionとMethodReflectionはPHP 8の属性とドクトリンアノテーションを取得するメソッドを提供します。
 
```php
/** @var $method \Ray\Aop\ReflectionMethod */
$method = $invocation->getMethod();
/** @var $class \Ray\Aop\ReflectionClass */
$class = $invocation->getMethod()->getDeclaringClass();
```

 
 * [`$method->getAnnotations()`]() - メソッドアトリビュート/アノテーションの取得
 * [`$method->getAnnotation($name)`]() 
 * [`$class->->getAnnotations()`]() - クラスアトリビュート/アノテーションの取得
 * [`$class->->getAnnotation($name)`]()

## 独自のマッチャー

独自のマッチャーを作成できます。例えば、`ContainsMatcher`を作成するには：

```php
use Ray\Aop\AbstractMatcher;

class ContainsMatcher extends AbstractMatcher
{
    public function matchesClass(\ReflectionClass $class, array $arguments) : bool
    {
        [$contains] = $arguments;
        return (strpos($class->name, $contains) !== false);
    }

    public function matchesMethod(\ReflectionMethod $method, array $arguments) : bool
    {
        [$contains] = $arguments;
        return (strpos($method->name, $contains) !== false);
    }
}
```

アノテーション/属性
Ray.Aopは[doctrine/annotation](https://github.com/doctrine/annotations)またはPHP 8の[Attributes](https://www.php.net/manual/en/language.attributes.overview.php)のどちらかも使用できます。

## AOPアライアンス

このメソッドインターセプターのAPIは[AOPアライアンス](http://aopalliance.sourceforge.net/doc/org/aopalliance/intercept/MethodInterceptor.html)の部分実装です。

## インストール

Ray.Aopの推奨インストール方法は、[Composer](https://github.com/composer/composer)でのインストールです。

```bash
# Ray.Aop を依存パッケージとして追加する
$ composer require ray/aop ^2.0
```

### アトリビュートのみ使用（推奨）

```php
SevericeLocator::setReader(new AttributeReader);`
```

## DI Framework

DIとAOPを統合したDIフレームワーク[Ray.Di](https://github.com/ray-di/Ray.Di)もご覧ください。

## 安定性

Ray.Aopはセマンティックバージョニングに従い、後方互換性を保証します。2015年のバージョン2.0以降、PHPの進化に合わせて機能を拡張しながら互換性を維持してきました。今後もこの安定性を維持していきます。

---

* この文書の大部分は [Guice/AOP](https://github.com/google/guice/wiki/AOP) から借用しています。
