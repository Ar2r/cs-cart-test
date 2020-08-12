<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\BackupService;

class BackupController extends AbstractController
{
    private backupService $backupService;

    public function __construct(BackupService $backupService) {
        $this->backupService = $backupService;
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


        return new JsonResponse(
            [
                'status' => "backup started in {$backupFile}"
            ],
            JsonResponse::HTTP_OK
        );
    }
}