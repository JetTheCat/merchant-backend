<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Storage\PlatformFeeReportStorageInterface;
use App\Http\Controllers\Controller;
use App\Models\PlatformFeeReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPlatformFeeReportController extends Controller
{
    public function __construct(private readonly PlatformFeeReportStorageInterface $feeReportStorage)
    {
    }

    public function __invoke(PlatformFeeReport $platformFeeReport): StreamedResponse
    {
        return $this->feeReportStorage->download($platformFeeReport->file_path);
    }
}
