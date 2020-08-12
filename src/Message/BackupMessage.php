<?php

namespace App\Message;

class BackupMessage
{
    /* file to backup to */
    private string $fileName;

    /* array containing info on what tables should be backed up */
    private array $tableList;

    /* array containing info on what table is backed up now */
    private array $current;

    public function __construct(array $content)
    {
        $this->fileName = $content['file_name'];
        $this->current = $content['current'];
        $this->tableList = $content['table_list'];
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getTableList(): array
    {
        return $this->tableList;
    }

    public function getCurrent(): array
    {
        return $this->current;
    }
}