<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\MalaysianState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMerchantLocationsRequest extends FormRequest
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
            'is_active' => ['nullable'],
            'state' => ['nullable', 'string', Rule::enum(MalaysianState::class)],
            'name' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    public function getLimit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }
}
