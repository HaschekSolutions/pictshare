<?php

declare(strict_types=1);

namespace PictShare\Classes\StorageProviders;

interface StorageProviderInterface
{
    /**
     * Get a file.
     *
     * @param string $originalFileName
     * @param string $variationFileName
     *
     * @return bool|string
     */
    public function get(string $originalFileName, string $variationFileName);

    /**
     * Save a file.
     *
     * @param string $originalFileName
     * @param string $variationFileName
     * @param string $fileContent
     */
    public function save(string $originalFileName, string $variationFileName, string $fileContent);

    /**
     * Delete a file.
     *
     * @param string $fileName
     */
    public function delete(string $fileName);
}
