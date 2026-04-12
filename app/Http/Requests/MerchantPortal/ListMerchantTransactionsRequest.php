<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListMerchantTransactionsRequest extends FormRequest
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
            'type' => ['nullable', new Enum(TransactionType::class)],
            'merchant_user_id' => ['nullable', 'uuid', 'exists:merchant_users,id'],
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
        ];
    }

    public function getLimit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }
}
