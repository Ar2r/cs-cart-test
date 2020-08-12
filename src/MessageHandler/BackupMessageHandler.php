<?php
namespace App\MessageHandler;

use App\Message\BackupMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use Symfony\Component\Filesystem\Filesystem;

class BackupMessageHandler implements MessageHandlerInterface {
    /* Filesystem helper */
    private Filesystem $fs;

    /* Amount of rows we are backing up in one go */
    private string $backupRowAmount;

    public function __construct(Filesystem $fs, array $backupParams) {
        $this->fs = $fs;
        $this->backupRowAmount = $backupParams['row-amount'];
    }

    public function __invoke(BackupMessage $message) {
        $fileName = $message->getFileName();
        $tableList = $message->getTableList();
        $current = $message->getCurrent();

        if (empty($current) && empty($tableList)){
            /* we have backed up everything. 
             * TODO log here
             */
            return true;
        }

        if (empty($current)){
            $current = [
                'table' => array_pop($tableList),
                'id' => 0,
                'amount' => $this->backupRowAmount
            ];
        }
    }
}