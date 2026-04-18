<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Command;
use InvalidArgumentException;

class GenerateSettlement extends Command
{
    protected $signature = 'settlements:generate
                            {merchantId : The UUID of the merchant}
                            {periodStart : Period start date (YYYY-MM-DD)}
                            {periodEnd : Period end date (YYYY-MM-DD)}';

    protected $description = 'Generate a settlement for a merchant over a given period';

    public function __construct(private readonly SettlementService $settlementService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $merchant = Merchant::find($this->argument('merchantId'));

        if ($merchant === null) {
            $this->error("Merchant [{$this->argument('merchantId')}] not found.");

            return self::FAILURE;
        }

        try {
            $periodStart = CarbonImmutable::createFromFormat('Y-m-d', $this->argument('periodStart'));
            $periodEnd = CarbonImmutable::createFromFormat('Y-m-d', $this->argument('periodEnd'));
        } catch (Exception) {
            $this->error('Invalid date format. Use YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($periodEnd->isBefore($periodStart)) {
            $this->error('Period end date must be on or after period start date.');

            return self::FAILURE;
        }

        $this->info("Generating settlement for merchant: {$merchant->name}");
        $this->info("Period: {$periodStart->toDateString()} → {$periodEnd->toDateString()}");

        try {
            $settlement = $this->settlementService->generateForMerchant($merchant, $periodStart, $periodEnd);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Settlement generated successfully.');
        $this->table(
            ['Reference', 'Transactions', 'Total Redemptions', 'Total Fees', 'Net Amount', 'Status'],
            [[
                $settlement->reference_code,
                $settlement->redemption_count,
                'RM '.number_format((float) $settlement->total_redemptions, 2),
                'RM '.number_format((float) $settlement->total_fees, 2),
                'RM '.number_format((float) $settlement->net_amount, 2),
                $settlement->status->label(),
            ]],
        );

        return self::SUCCESS;
    }
}
