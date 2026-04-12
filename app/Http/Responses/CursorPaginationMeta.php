<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Pagination\CursorPaginator;

class CursorPaginationMeta
{
    public static function build(CursorPaginator $paginator, int $perPage): array
    {
        return [
            'perPage' => $perPage,
            'nextCursor' => $paginator->nextCursor()?->encode(),
            'prevCursor' => $paginator->previousCursor()?->encode(),
            'hasMore' => $paginator->hasMorePages(),
        ];
    }
}
