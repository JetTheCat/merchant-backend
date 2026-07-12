<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface FeeReportStorageInterface
{
    public function store(string $filename, string $content): string;

    public function download(string $path): StreamedResponse;

    public function exists(string $path): bool;
}
