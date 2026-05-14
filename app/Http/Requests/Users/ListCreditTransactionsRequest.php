<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Enums\TransactionType;
use App\Support\DateTimeParser;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListCreditTransactionsRequest extends FormRequest
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
            'merchant_id' => ['nullable', 'uuid', 'exists:merchants,id'],
            'type' => ['nullable', new Enum(TransactionType::class)],
            'timezone' => ['nullable', 'timezone'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
            'cursor' => ['nullable', 'string'],
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
