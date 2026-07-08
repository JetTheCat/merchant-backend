<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Contracts\Storage\FeeReportStorageInterface;
use App\Exceptions\Storage\FileAlreadyExistsException;
use App\Exceptions\Storage\FileStorageException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeReportStorageService implements FeeReportStorageInterface
{
    private const string DIRECTORY = 'fee-reports';

    public function store(string $filename, string $content): string
    {
        $path = $this->getFilePath($filename);

        if ($this->exists($path)) {
            throw new FileAlreadyExistsException("File already exists at path: {$path}");
        }

        if (! Storage::put($path, $content)) {
            throw new FileStorageException("Failed to store file at path: {$path}");
        }

        return $path;
    }

    public function download(string $filename): StreamedResponse
    {
        return Storage::download($this->getFilePath($filename));
    }

    public function exists(string $path): bool
    {
        return Storage::exists($path);
    }

    private function getFilePath(string $filename): string
    {
        return sprintf('%s/%s/%s', self::DIRECTORY, now()->year, $filename);
    }
}
