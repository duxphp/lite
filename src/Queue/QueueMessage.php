<?php
declare(strict_types=1);

namespace Dux\Queue;

class QueueMessage
{
    public function __construct(
        public array $queueHandlers,
    )
    {
    }

    public function getHandlers(): array
    {
        return $this->queueHandlers;
    }
}