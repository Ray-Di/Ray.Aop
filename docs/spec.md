# Aspect クラス仕様書

## 概要
`Aspect` クラスは、Aspect Oriented Programming (AOP) の中心的な機能を提供します。このクラスを使用して、特定のクラスやメソッドにインターセプターを適用し、アスペクトを「織り込む」ことができます。

## クラス定義
```php
final class Aspect
```

## コンストラクタ
```php
public function __construct(string $classDir)
```
- `$classDir`: スキャン対象のディレクトリパス

## パブリックメソッド

### bind
```php
public function bind(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
```
アスペクトのバインディングを定義します。
- `$classMatcher`: クラスに対するマッチャー
- `$methodMatcher`: メソッドに対するマッチャー
- `$interceptors`: 適用するインターセプターの配列

### weave
```php
public function weave(): void
```
定義されたバインディングに基づいて、アスペクトを織り込みます。

## 内部動作

1. コンストラクタで指定されたディレクトリ内のPHPクラスファイルをスキャンします。
2. `bind` メソッドで定義されたマッチャーとインターセプターの情報を保持します。
3. `weave` メソッドが呼び出されると、以下の処理を行います：
   a. スキャンしたクラスに対してマッチャーを適用し、マッチするメソッドを特定します。
   b. マッチしたメソッドに対して、指定されたインターセプターを適用します。
   c. PECL拡張機能 `ray_aop` を使用して、実際のインターセプションを設定します。

## 使用例

```php
$aspect = new Aspect(__DIR__ . '/src');
$aspect->bind(
    new AnyMatcher(),
    new AnnotatedWithMatcher(OnWeekend::class),
    [new WeekendBlocker()]
);
$aspect->weave();
```

## 注意事項

- このクラスは PECL 拡張機能 `ray_aop` に依存しています。拡張機能がインストールされていない場合、`weave` メソッドは例外をスローします。
- 大規模なプロジェクトでは、パフォーマンスに影響を与える可能性があるため、適切なキャッシング戦略の実装を検討してください。
- エラーハンドリングとロギングを適切に実装し、問題が発生した場合のデバッグを容易にすることを推奨します。
- `getBound` メソッドは内部使用のみを目的としているため、外部からアクセスすることはできません。バインディング情報が必要な場合は、適切な公開メソッドを追加することを検討してください。
