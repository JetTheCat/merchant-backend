<?php

declare(strict_types=1);

namespace App\Enums;

enum MalaysianState: string
{
    case JOHOR = 'Johor';
    case KEDAH = 'Kedah';
    case KELANTAN = 'Kelantan';
    case KUALA_LUMPUR = 'Kuala Lumpur';
    case LABUAN = 'Labuan';
    case MALACCA = 'Malacca';
    case NEGERI_SEMBILAN = 'Negeri Sembilan';
    case PAHANG = 'Pahang';
    case PENANG = 'Penang';
    case PERAK = 'Perak';
    case PERLIS = 'Perlis';
    case PUTRAJAYA = 'Putrajaya';
    case SABAH = 'Sabah';
    case SARAWAK = 'Sarawak';
    case SELANGOR = 'Selangor';
    case TERENGGANU = 'Terengganu';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
