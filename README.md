# hyperf_rocketmq

基于阿里云 rocketmq SDK 封装，实现了协程、连接池、消息可靠投递 等功能

> 该库来源于： https://gitee.com/easy_code/hyperf-rocketmq

### 1、安装

```shell
composer require leoxml/hyperf-rocketmq
```

### 2、配置

#### 发布配置

```shell
php bin/hyperf.php vendor:publish leoxml/hyperf-rocketmq
```

#### 配置说明

| 配置        | 类型   | 默认值 | 备注                 |
| ----------- | ------ | ------ | -------------------- |
| host        | string |        | HTTP协议客户端接入点 |
| access_key  | string |        | AccessKey ID         |
| secret_key  | string |        | AccessKey Secret     |
| instance_id | string |        | 实例id               |
| pool        | array  |        | 连接池配置           |

```php
return [
    'default' => [ // 分组名，基于 host、port、scheme 进行区分
        'host' => env('ROCKETMQ_HTTP_HOST'),
        'access_key' => env('ROCKETMQ_HTTP_ACCESS_KEY_ID'),
        'secret_key' => env('ROCKET_MQ_HTTP_ACCESS_KEY_SECRET'),
        'instance_id' => env('ROCKET_MQ_HTTP_INSTANCE_ID'),
        'pool' => [
            'min_connections' => 50,
            'max_connections' => 300,
            'connect_timeout' => 3.0,
            'wait_timeout' => 30.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
    ],
];
```

### 3、创建相关数据表

> 如果不需要记录日志 或 消息不需要可靠投递，可以忽略这步

```shell
php bin/hyperf.php migrate --path=migrations/rocketmq
```

表说明：

rocketmq_status_log：消息生产状态表

rocketmq_produce_status_log：生成消息状态

rocketmq_consume_log：消费日志

### 4、投递消息

Producer注解参数

| 字段名       | 类型   | 描述                                          | 默认值   |
| ------------ | ------ | --------------------------------------------- | -------- |
| poolName     | string | 连接池名称。对应配置文件 rocketmq.php 中的key | default  |
| dbConnection | string | 数据库连接名称（用于记录生产日志）            | default  |
| topic        | string | topic                                         | 无       |
| messageKey   | string | 消息key                                       | 随机生成 |
| messageTag   | string | 消息标签                                      | 无       |

#### 4.1 定义生产者相关信息

在 DemoProducer 文件中，我们可以修改 `@Producer` 注解对应的字段来替换对应的 `poolName`、`topic`、`messageTag`。就是最终投递到消息队列中的数据，所以我们可以随意改写 `__construct` 方法，只要最后赋值 `payload` 即可。

> 使用 `@Producer` 注解时需 `use Leoxml\RocketMQ\Annotation\Producer;` 命名空间；

```shell
<?php
declare(strict_types=1);

namespace App\Test\Queue\Producer;

use Leoxml\RocketMQ\Annotation\Producer;
use Leoxml\RocketMQ\Message\ProducerMessage;

#[Producer(topic:"Topic_03_test", messageTag:"tMsgKey")]
class DemoProducer extends ProducerMessage
{
    public function __construct(array $data)
    {
        // 设置消息内容
        $this->setPayload($data);
        // 自定义messageKey（不定义，会自动生成）
        $this->setMessageKey('xxxxx');
    }
}
```

#### 4.2 普通投递方式

通过`Leoxml\RocketMQ\Producer`实例，即可投递消息。

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Producer\DemoProducer;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Leoxml\RocketMQ\Producer;

#[Controller]
class IndexController extends AbstractController
{
    #[Inject(Producer::class)]
    protected Producer $producer;

    #[RequestMapping("index")]
    public function index()
    {
        $message = new DemoProducer(['a' => 1, 'b' =>2]);
        $this->producer->produce($message);
        return $this->response->json([]);
    }
}
```

#### 4.3 消息可靠投递方式

> 目前，消息投递时Rocketmq返回成功响应，就视为投递成功（暂不考虑Rocketmq缓存丢失的问题）。

实现原理：先将需要投递的消息入库处理，然后再进行发送操作

1. 执行以下命令，生成相关的数据表

   ```shell
   php bin/hyperf.php migrate --path=migrations/rocketmq
   ```

2. 使用示例

   ```php
   $demoProducer = new BarProducer(['test' => 12345, 'name' => '张三1231']);
   
   Db::beginTransaction();
   try{
       // todo 业务逻辑
   
       // 记录消息状态
       $demoProducer->saveMessageStatus();
       Db::commit();
   } catch(\Throwable $ex){
       Db::rollBack();
   }
   
   // 推送消息
   $this->producer->produce($demoProducer);
   ```
   
3. 投递失败的消息，可以通过守护进程监听 `mq_status_log` 数据表`status`不等于3的消息，进行重新投递（后面实现）

### 5、消息消费

Consumer注解属性说明

| 属性         | 类型   | 描述                                          | 默认值  |
| ------------ | ------ | --------------------------------------------- | ------- |
| name         | string | 消费名称                                      | 无      |
| poolName     | string | 连接池名称。对应配置文件 rocketmq.php 中的key | default |
| topic        | string | topic                                         | 无      |
| groupId      | string | 消费组id                                      | 无      |
| messageTag   | string | 消息标签                                      | 无      |
| numOfMessage | int    | 每次拉取消息数                                | 3       |
| waitSeconds  | int    | 轮询等待时间                                  | 3       |
| processNums  | int    | 启动消费进程数                                | 1       |
| enable       | bool   | 是否初始化启动进程                            | true    |

在 DemoConsumer文件中，我们可以修改 `@Consumer` 注解对应的字段来替换对应的 `topic`、`groupId`、`messageTag`。

> 使用 `@Consumer` 注解时需 `use Leoxml\RocketMQ\Annotation\Consumer;` 命名空间；

```php
use Leoxml\RocketMQ\Annotation\Consumer;
use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;
use Leoxml\RocketMQ\Message\ConsumerMessage;
use Leoxml\RocketMQ\Result;

#[Consumer(topic: "Topic_03_test", groupId: "test_test", messageTag: "tMsgKey||tMsgKey_bar")]
class DemoCounser extends ConsumerMessage
{
    public function consumeMessage(RocketMQMessage $rocketMQMessage): string
    {
        $msgTag = $rocketMQMessage->getMessageTag(); // 消息标签
        $msgKey = $rocketMQMessage->getMessageKey(); // 消息唯一标识
        $msgBody = $rocketMQMessage->toArray(); // 消息体
        $msgId = $rocketMQMessage->getMessageId();

        var_dump('消费端接收到消息：', $msgBody);

        return Result::ACK;
    }
}
```

### 6、事件说明

> 下面事件都在 `Leoxml\RocketMQ\Event` 命名空间下

| 事件          | 说明                   |
| ------------- | ---------------------- |
| AfterProduce  | 消息生产成功触发的事件 |
| BeforeConsume | 开启消费前触发的事件   |
| AfterConsume  | 成功消费后触发的事件   |
| FailToConsume | 消费失败触发的事件     |

