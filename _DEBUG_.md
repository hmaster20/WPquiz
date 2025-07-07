# Нужно исправить

## Что исправить

Для Reports добавить сортировку при клике по каждому столбцу.
ProgressBar сломан для тестов при вопросах больше одного.

## Отладка

В каталоге `www` находим файл `wp-config.php`
В файле ищем строку `define( 'WP_DEBUG', false );` и заменяем на:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Журнал событий `debug.log` будет сформирован в каталоге `wp-content`.
