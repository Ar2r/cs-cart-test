<?php
namespace App\MessageHandler;

use App\Message\BackupMessage;
use App\Service\BackupService;
use App\Service\Helper\BackupQueueHelper;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use Symfony\Component\Filesystem\Filesystem;

class BackupMessageHandler implements MessageHandlerInterface {
    /* Filesystem helper */
    private Filesystem $fs;

    private BackupService $backupService;

    private BackupQueueHelper $backupQueueHelper;

    /* Amount of rows we are backing up in one go */
    private string $backupRowAmount;

    public function __construct(Filesystem $fs, BackupService $backupService, BackupQueueHelper $backupQueueHelper, array $backupParams) {
        $this->fs = $fs;
        $this->backupService = $backupService;
        $this->backupQueueHelper = $backupQueueHelper;
        $this->backupRowAmount = $backupParams['row-amount'];
    }

    /**
     * @param BackupMessage $message
     */
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
        list($lastId, $rowsInsert) = $this->backupService->getRowsInsert($current['table'], $current['id'], $current['amount']);
        $this->fs->appendToFile($fileName, $rowsInsert);
        
        /* checking if we have dumped all table at once or if these are last rows of the table */
        if (!$lastId || substr_count($rowsInsert, ";") < $current['amount']){
            $current = [];
        } else {
            $current['id'] = $lastId;
        }

        $this->backupQueueHelper->queueBackup($fileName, $tableList, $current);
    }
}