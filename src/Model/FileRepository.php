<?php

declare(strict_types=1);

namespace PHPUploader\Model;

class FileRepository
{
    public const PUBLIC_FILE_COLUMNS = [
        'id',
        'origin_file_name',
        'comment',
        'size',
        'count',
        'input_date',
    ];

    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public static function createFromConfig(array $config): self
    {
        $db = new \PDO('sqlite:' . $config['dbDirectoryPath'] . '/uploader.db');
        $db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return new self($db);
    }

    public function fetchAllPublic(): array
    {
        $stmt = $this->db->prepare('SELECT ' . $this->publicColumnsSql() . ' FROM uploaded');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findPublicById(int $fileId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . $this->publicColumnsSql() . ' FROM uploaded WHERE id = :id'
        );
        $stmt->execute(['id' => $fileId]);
        $file = $stmt->fetch();

        return is_array($file) ? $file : null;
    }

    public function findDetailById(int $fileId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . $this->publicColumnsSql() . ',
                CASE WHEN dl_key_hash IS NOT NULL AND dl_key_hash <> \'\' THEN 1 ELSE 0 END AS has_download_key,
                CASE WHEN del_key_hash IS NOT NULL AND del_key_hash <> \'\' THEN 1 ELSE 0 END AS has_delete_key
             FROM uploaded
             WHERE id = :id'
        );
        $stmt->execute(['id' => $fileId]);
        $file = $stmt->fetch();

        if (!is_array($file)) {
            return null;
        }

        $file['has_download_key'] = (bool)$file['has_download_key'];
        $file['has_delete_key'] = (bool)$file['has_delete_key'];

        return $file;
    }

    private function publicColumnsSql(): string
    {
        return implode(', ', self::PUBLIC_FILE_COLUMNS);
    }
}
