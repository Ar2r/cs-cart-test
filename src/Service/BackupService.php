<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Class: BackupService
 * This will be used for all DB operations needed for backup
 */
class BackupService
{
    private EntityManagerInterface $em;

    private string $projectDir;

    /* all backup files will be placed here */
    private string $backupDir;

    private array $tableList = [];

    private Filesystem $fs;

    public function __construct(EntityManagerInterface $em, Filesystem $fs, string $projectDir, array $backupParams)
    {
        $this->em = $em;
        $this->fs = $fs;
        $this->backupDir = "{$projectDir}/{$backupParams['backup-dir']}";
    }

    public function getTableList(): array
    {
        return $this->tableList;
    }

    /**
     * @param string $tableName
     */
    private function addToTableList(string $tableName)
    {
        if (!in_array($tableName, $this->tableList)){
            $this->tableList[] = $tableName;
        }
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

        $fileName = "{$this->backupDir}/backup-" . time() . ".sql";
        $this->fs->touch($fileName);

        foreach($stmt->fetchAll() as $table){
            $conn = $this->em->getConnection();
            $sql = "SHOW CREATE TABLE {$table['TABLE_NAME']};";
            $stmt = $conn->prepare($sql);
            try {
                $stmt->execute();
                $create = $stmt->fetchAll()[0]['Create Table'];
                $this->fs->appendToFile($fileName, $create . "\n");
                $this->addToTableList($table['TABLE_NAME']);
            } catch (TableNotFoundException $e){
                continue;
            }
        }

        return $fileName;
    }
    
    /**
     * @param string $table
     * @param int $id
     * @param int $amount
     *
     * @return array [last row id, INSERT statements for the rows]
     */
    public function getRowsInsert(string $table, int $id, int $amount): array
    {
        $rowInsert = '';
        $conn = $this->em->getConnection();
        
        /* TODO LIMIT might be bad here */
        $sql = "SELECT * FROM `{$table}` WHERE id > {$id} ORDER BY id ASC LIMIT {$amount};";
        $lastId = 0;
        if (!$id){
            /* we are starting work with new table. Checking if it has id column */
            $sqlCheck = "SHOW COLUMNS FROM `{$table}` LIKE 'id';";
            $stmt = $conn->prepare($sqlCheck);
            $stmt->execute();
            if (empty($stmt->fetchAll())){
                /* we are selecting everything from not numbered tables */
                $sql = "SELECT * FROM `{$table}`;";
            }
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row){
            $insertSQL = "INSERT INTO `" . $table . "` SET ";
            foreach ($row as $field => $value) {
                $insertSQL .= " `" . $field . "` = '" . $value . "', ";
                if ('id' === $field){
                    $lastId = $value;
                }
            }
            $insertSQL = trim($insertSQL, ", ");
            $rowInsert .= $insertSQL . ";\n";
        }
        echo "$table: $lastId\n";
        return [$lastId, $rowInsert];
    }
}