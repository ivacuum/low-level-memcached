# Тестовое задание

[![Build Status](https://travis-ci.org/ivacuum/low-level-memcached.svg?branch=master)](https://travis-ci.org/ivacuum/low-level-memcached)

Реализация команд get/set/delete. Поддержка работы в синхронном и асинхронном режимах.

## Установка

```bash
composer require ivacuum/low-level-memcached
```

## Использование

```php
use Vacuum\LowLevelMemcached;

$memcached = new LowLevelMemcached('127.0.0.1', 11211);

// Сохранение данных
$memcached->set('cron.last', time());

// Получение данных
$data = $memcached->get('cron.last');

// Удаление данных
$memcached->delete('cron.last');
```

### Асинхронное получение данных

С помощью метода `getLater($key)` можно запросить данные в неблокирующем режиме. Затем в нужном месте необходимо вызвать метод `fetch()` для фактического получения данных.

```php
$memcached->set('async.key', 'data to set');

$memcached->getLater('async.key');

// ...
// Прочие операции
// ...

// Настал момент, когда понадобились данные
$async_data = $memcached->fetch();
```

## Ограничения

- Из-за отсутствия сериализации в данной версии поддерживается сохранение и получение только строковых данных.
- За один запрос можно получить данные только по одному ключу.
