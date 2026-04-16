<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
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
        return [
            'merchant_user_id' => ['nullable', 'uuid', 'exists:merchant_users,id'],
            'status' => ['nullable', new Enum(TransactionStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
        ];
    }

    public function getLimit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }
}
