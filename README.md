# cookie-helper

一个操作`$_COOKIE`的类。

## 安装

在你的`composer.json`，`require`项添加：

```
"gemingcao/cookie-helper": "~1.0"
```

或者`composer`直接安装：

```sh
composer require gemingcao/cookie-helper:~1.0
```

## Cookie helper

你可以全局实例化`CookieHelper`类：

```php
  $cookie = new \Gemingcao\Helper\CookieHelper();

  // 检测cookie是否存在
  $exists = $cookie->exists('my_key');
  $exists = isset($cookie->my_key);
  $exists = isset($cookie['my_key']);

  // 获取一个cookie
  $my_value = $cookie->get('my_key', 'default');
  $my_value = $cookie->my_key;
  $my_value = $cookie['my_key'];

  // 设置一个cookie
  $app->cookie->set('my_key', 'my_value');
  $cookie->my_key = 'my_value';
  $cookie['my_key'] = 'my_value';

  // 合并cookie数组
  $app->cookie->merge('my_key', ['first' => 'value']);
  $cookie->merge('my_key', ['second' => ['a' => 'A']]);
  $letter_a = $cookie['my_key']['second']['a'];  // "A"

  // 删除一个cookie
  $cookie->delete('my_key');
  unset($cookie->my_key);
  unset($cookie['my_key']);

  // 销毁cookie
  $cookie::destroy();
```

## License

MIT
