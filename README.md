MasterSlaveRedis
====

Redisのレプリカを使えるようにする。

## Usage

```php
$manager = new RedisManager([
    "master" => [
        "host" => "master-host",
    ],
    "slave" => [
        [
            "host" => "slave-host",
        ]
    ]
]);

$manager->getMaster()->set("key", 1); // master-host
$manager->getSlave()->get("key"); // slave-host
```

```php
$manager = new RedisManager([
    "master" => [
        "host" => "master-host",
    ],
    "slave" => [
    ]
]);

$manager->getMaster()->set("key", 1); // master-host
$manager->getSlave()->get("key"); // master-host, use master
```

```php
$manager = new RedisManager([
    "master" => [
        "host" => "master-host",
    ],
    "slave" => [
        [
            "host" => "slave1",
        ],
        [
            "host" => "slave2",
        ],
        [
            "host" => "slave3",
        ],
    ]
]);

$manager->getMaster()->set("key", 1); // master-host
$manager->getSlave()->get("key"); // slave1, slave2 or slave3, random choice
```


```php
$manager = new RedisManager([
    "master" => [
        "host" => "master-host",
        "port" => 6379,  // 指定可能。デフォルトは6379
        "timeout" => 0, // 指定可能。デフォルトは0(無制限)
    ],
    "slave" => [
    ]
]);

$manager->getMaster()->set("key", 1); // master-host
$manager->getSlave()->get("key"); // master-host, use master
```

## Test

```php
docker-compose up -d --scale redis-replica1=4 redis-replica1
docker-compose run --rm phpunit-full
```

## Test design

|Feature|Small|Medium|
|---|---|---|
|Server Access|No|Yes|
|Logic|Yes|Yes|
