<?php

declare(strict_types=1);

namespace PictShare\Classes\StorageProviders;

class LocalStorageProvider implements StorageProviderInterface
{
    const LOCAL_UPLOAD_DIR = 'upload';

    /**
     * @var string
     */
    private $localBaseDir;


    /**
     * LocalStorageProvider constructor.
     */
    public function __construct()
    {
        $this->localBaseDir = ROOT . DS . self::LOCAL_UPLOAD_DIR . DS;
    }

    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function get(string $originalFileName, string $variationFileName)
    {
        throw new \RuntimeException(__METHOD__ . ' not implemented.');
    }

    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function save(string $originalFileName, string $variationFileName, string $fileContent)
    {
        $uploadDir = $this->localBaseDir . $originalFileName;

        if (!mkdir($uploadDir) && !is_dir($uploadDir)) {
            throw new \RuntimeException('Could not create directory: ' . $uploadDir);
        }

        file_put_contents($uploadDir . DS . $variationFileName, $fileContent);
    }

    /**
     * @inheritdoc
     */
    final public function delete(string $fileName)
    {
        $basePath = $this->localBaseDir . $fileName . DS;

        if (!is_dir($basePath)) {
            return;
        }

        $handle = opendir($basePath);

        if ($handle) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry !== '.' && $entry !== '..') {
                    unlink($basePath . $entry);
                }
            }

            closedir($handle);
        }

        rmdir($basePath);
    }
}
