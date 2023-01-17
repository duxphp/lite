<?php
declare(strict_types=1);

namespace Dux\Queue;
use Dux\Handlers\Exception;
use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Context;
use RuntimeException;

class QueueHandlers {


    public Context $context;
    private string $group;
    private string $class;
    private string $method;
    private array $params;
    private int $delay;
    private bool $supportDelay;

    public function __construct(Context $context, bool $supportDelay, string $group = "default") {
        $this->group = $group;
        $this->context = $context;
        $this->supportDelay = $supportDelay;
    }

    /**
     * 设置回调类
     * @param string $class
     * @param string $method
     * @param array $params
     * @return QueueHandlers
     */
    public function callback(string $class, string $method = "", array $params = []): self {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
        return $this;
    }


    /**
     * 设置延时任务
     * @param int $millisecond
     * @return QueueHandlers
     */
    public function delay(int $millisecond): self {
        $this->delay = $millisecond;
        return $this;
    }

    /**
     * 发送队列
     * @return void
     */
    public function send(): void {
        if (!$this->class) {
            throw new Exception("Please set the callback class");
        }
        $body = [$this->class];
        if ($this->method) {
            $body[] = $this->method;
        }
        $queue = $this->context->createQueue($this->group);
        $message = $this->context->createMessage(implode(":", $body), $this->params);
        $ctx = $this->context->createProducer();

        if ($this->supportDelay && $this->delay) {
            $ctx = $ctx->setDeliveryDelay($this->delay);
        }
        $ctx->send($queue, $message);
    }


}