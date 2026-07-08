<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use Illuminate\Http\UploadedFile;

interface MerchantLogoStorageInterface
{
    public function store(string $merchantId, UploadedFile $file): string;

    public function url(string $path): string;

    public function delete(string $path): void;
}
