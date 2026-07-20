<?php

namespace PHPUploader\Model;

class Index
{
    public const PUBLIC_FILE_COLUMNS = FileRepository::PUBLIC_FILE_COLUMNS;

    public function index()
    {
        $config = new \PHPUploader\Config();
        $ret = $config->index();

        try {
            $repository = FileRepository::createFromConfig($ret);
        } catch (\Exception $e) {
            $error = '500 - データベースの接続に失敗しました。';
            exit;
        }

        return [
            'data' => $repository->fetchAllPublic(),
        ];
    }
}
