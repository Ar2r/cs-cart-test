<?php

namespace App\Service\Helper;

use App\Message\BackupMessage;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class: BackupQueueHelper
 * This will be used for all queueing operations needed for backup
 */
class BackupQueueHelper
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @param string $fileName
     * @param array $tableList
     * @param array $current
     */
    public function queueBackup(string $fileName, array $tableList, array $current = [])
    {
        $this->bus->dispatch(new BackupMessage([
            'file_name' => $fileName,
            'table_list' => $tableList,
            'current' => $current
        ]));
    }
}
