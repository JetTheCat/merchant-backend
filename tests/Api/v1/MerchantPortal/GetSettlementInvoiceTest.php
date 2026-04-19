<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Settlement;
use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetSettlementInvoiceTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;
    private MerchantUser $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        Pdf::fake();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testMerchantUserCanDownloadInvoice(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getSettlementInvoiceEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_OK);

        Pdf::assertRespondedWithPdf(function ($pdf) use ($settlement) {
            return $pdf->viewName === 'settlements.invoice'
                && $pdf->downloadName === "settlement-{$settlement->reference_code}.pdf";
        });
    }

    public function testMerchantCannotDownloadAnotherMerchantsInvoice(): void
    {
        $otherSettlement = Settlement::factory()->for(Merchant::factory()->active())->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getSettlementInvoiceEndpoint($otherSettlement->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->getJson(self::getSettlementInvoiceEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::getSettlementInvoiceEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
