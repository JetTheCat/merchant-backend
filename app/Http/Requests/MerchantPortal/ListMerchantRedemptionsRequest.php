<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Support\DateTimeParser;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ListMerchantRedemptionsRequest extends FormRequest
{
    public const DEFAULT_LIMIT = 25;

    public const MAX_LIMIT = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchantId = $this->user()->merchant_id;

        return [
            'merchant_user_id' => [
                'nullable',
                'uuid',
                Rule::exists('merchant_users', 'id')->where('merchant_id', $merchantId),
            ],
            'merchant_location_id' => [
                'nullable',
                'uuid',
                Rule::exists('merchant_locations', 'id')->where('merchant_id', $merchantId),
            ],
            'status' => ['nullable', new Enum(TransactionStatus::class)],
            'timezone' => ['nullable', 'timezone'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    public function getLimit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }

    public function parseDate(string $field): ?Carbon
    {
        $value = $this->validated($field);

        return $value !== null ? DateTimeParser::toUtc($value, $this->validated('timezone')) : null;
    }
}
