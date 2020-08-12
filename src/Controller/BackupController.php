<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\BackupService;
use App\Service\Helper\BackupQueueHelper;

class BackupController extends AbstractController
{
    private backupService $backupService;
    private BackupQueueHelper $backupQueue;

    public function __construct(BackupService $backupService, BackupQueueHelper $backupQueue) {
        $this->backupService = $backupService;
        $this->backupQueue = $backupQueue;
    }

     /**
      * @Route("/backup/start", methods={"POST"})
      *
      * This will start backup process by dumping db sceleton and putting a backup job in queue
      */
    public function start(): JsonResponse
    {
        if (!$backupFile = $this->backupService->dumpSceleton()){
            return new JsonResponse(
                [
                    'status' => 'Not able to dump DB sceleton'
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->backupQueue->queueBackup($backupFile, $this->backupService->getTableList());

        return new JsonResponse(
            [
                'status' => "backup started in {$backupFile}"
            ],
            JsonResponse::HTTP_OK
        );
    }
}