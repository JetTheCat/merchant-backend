<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Storage\FeeReportStorageInterface;
use App\Http\Controllers\Controller;
use App\Models\PlatformFeeReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadFeeReportController extends Controller
{
    public function __construct(private readonly FeeReportStorageInterface $feeReportStorage)
    {
    }

    public function __invoke(PlatformFeeReport $platformFeeReport): StreamedResponse
    {
        return $this->feeReportStorage->download($platformFeeReport->file_path);
    }
}
