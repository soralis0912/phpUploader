<?php

declare(strict_types=1);

namespace PHPUploader\Model;

class Download
{
    public function index(): array
    {
        $fileId = (int)($_GET['id'] ?? 0);
        if ($fileId <= 0) {
            return [
                'downloadFile' => null,
            ];
        }

        $config = new \PHPUploader\Config();
        $configData = $config->index();

        try {
            $repository = FileRepository::createFromConfig($configData);
        } catch (\Exception $e) {
            return [
                'downloadFile' => null,
            ];
        }

        return [
            'downloadFile' => $repository->findDetailById($fileId),
        ];
    }
}
