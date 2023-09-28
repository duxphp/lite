<?php
declare(strict_types=1);

namespace Dux\Queue;


class QueueHandlers
{
    public function __invoke(QueueMessage $message): void
    {
        // Message processing...
    }
}