<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Storage;

use App\Exceptions\Storage\FileAlreadyExistsException;
use App\Exceptions\Storage\FileStorageException;
use App\Services\Storage\PlatformFeeReportStorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PlatformFeeReportStorageServiceTest extends TestCase
{
    private PlatformFeeReportStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlatformFeeReportStorageService::class);
    }

    public function testStoreWritesContentToExpectedPath(): void
    {
        Storage::fake();

        $filePath = $this->service->store('test.txt', 'this is a test file');

        $this->assertSame('fee-reports/test.txt', $filePath);
        Storage::assertExists($filePath);
        $this->assertSame('this is a test file', Storage::get($filePath));
    }

    public function testStoreThrowsFileAlreadyExistsExceptionWhenFileAlreadyExistsAtPath(): void
    {
        Storage::fake();

        $filePath = $this->service->store('test.txt', 'this is a test file');

        $this->expectException(FileAlreadyExistsException::class);
        $this->expectExceptionMessage("File already exists at path: {$filePath}");

        $this->service->store('test.txt', 'another test file');
    }

    public function testStoreThrowsFileStorageExceptionWhenPutFails(): void
    {
        Storage::fake();

        Storage::shouldReceive('exists')->once()->andReturn(false);
        Storage::shouldReceive('put')->once()->andReturn(false);

        $this->expectException(FileStorageException::class);

        $this->service->store('test.txt', 'this is a test file');
    }

    public function testDownloadReturnsStreamedResponseForExistingFile(): void
    {
        Storage::fake();

        $filePath = $this->service->store('test.txt', 'this is a test file');

        $response = $this->service->download($filePath);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testExistsReturnsTrueWhenFileExists(): void
    {
        Storage::fake();

        $filePath = $this->service->store('test.txt', 'this is a test file');

        $this->assertTrue($this->service->exists($filePath));
    }

    public function testExistsReturnsFalseWhenFileDoesNotExist(): void
    {
        Storage::fake();

        $this->assertFalse($this->service->exists('fee-reports/missing.txt'));
    }
}
