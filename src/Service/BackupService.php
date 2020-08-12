<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Class: GalleryHelper
 * This will be used for all DB operations needed for backup
 */
class BackupService
{
    private EntityManagerInterface $em;

    private string $projectDir;

    /* all backup files will be placed here */
    private string $backupDir;

    private Filesystem $fs;

    public function __construct(EntityManagerInterface $em, Filesystem $fs, string $projectDir, array $backupParams)
    {
        $this->em = $em;
        $this->fs = $fs;
        $this->backupDir = "{$projectDir}/{$backupParams['backup-dir']}";
    }

    /**
     * Creates a file with create statements for all tables
     * @return ?string returns a backup file to write to or NULL if error has occured
     */
    public function dumpSceleton(): ?string
    {
        $conn = $this->em->getConnection();
        $sql = 'SELECT * FROM information_schema.tables;';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $fileName = "{$this->backupDir}/" . time() . ".sql";
        $this->fs->touch($fileName);

        foreach($stmt->fetchAll() as $table){
            $conn = $this->em->getConnection();
            $sql = "SHOW CREATE TABLE {$table['TABLE_NAME']};";
            $stmt = $conn->prepare($sql);
            try {
                $stmt->execute();
                $create = $stmt->fetchAll()[0]['Create Table'];
                $this->fs->appendToFile($fileName, $create . "\n");
            } catch (TableNotFoundException $e){
                continue;
            }
        }

        return $fileName;
    }
}