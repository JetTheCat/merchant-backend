<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantArea: string
{
    // Kuala Lumpur
    case KLCC = 'KLCC';
    case BUKIT_BINTANG = 'Bukit Bintang';
    case BANGSAR = 'Bangsar';
    case MONT_KIARA = 'Mont Kiara';
    case CHOW_KIT = 'Chow Kit';
    case CHERAS = 'Cheras';
    case BUKIT_JALIL = 'Bukit Jalil';
    case WANGSA_MAJU = 'Wangsa Maju';
    case KEPONG = 'Kepong';
    case SRI_PETALING = 'Sri Petaling';

    // Selangor
    case PETALING_JAYA = 'Petaling Jaya';
    case SUBANG_JAYA = 'Subang Jaya';
    case SHAH_ALAM = 'Shah Alam';
    case KLANG = 'Klang';
    case PUCHONG = 'Puchong';
    case CYBERJAYA = 'Cyberjaya';
    case ARA_DAMANSARA = 'Ara Damansara';
    case KOTA_DAMANSARA = 'Kota Damansara';
    case KAJANG = 'Kajang';
    case AMPANG = 'Ampang';
    case SUNWAY = 'Sunway';
    case RAWANG = 'Rawang';

    // Penang
    case GEORGETOWN = 'Georgetown';
    case BUTTERWORTH = 'Butterworth';
    case BAYAN_LEPAS = 'Bayan Lepas';
    case BATU_FERRINGHI = 'Batu Ferringhi';
    case AIR_ITAM = 'Air Itam';
    case JELUTONG = 'Jelutong';
    case BUKIT_MERTAJAM = 'Bukit Mertajam';

    // Johor
    case JOHOR_BAHRU = 'Johor Bahru';
    case SKUDAI = 'Skudai';
    case ISKANDAR_PUTERI = 'Iskandar Puteri';
    case KULAI = 'Kulai';
    case BATU_PAHAT = 'Batu Pahat';
    case MUAR = 'Muar';
    case MASAI = 'Masai';

    public function state(): string
    {
        return match ($this) {
            self::KLCC,
            self::BUKIT_BINTANG,
            self::BANGSAR,
            self::MONT_KIARA,
            self::CHOW_KIT,
            self::CHERAS,
            self::BUKIT_JALIL,
            self::WANGSA_MAJU,
            self::KEPONG,
            self::SRI_PETALING => 'Kuala Lumpur',

            self::PETALING_JAYA,
            self::SUBANG_JAYA,
            self::SHAH_ALAM,
            self::KLANG,
            self::PUCHONG,
            self::CYBERJAYA,
            self::ARA_DAMANSARA,
            self::KOTA_DAMANSARA,
            self::KAJANG,
            self::AMPANG,
            self::SUNWAY,
            self::RAWANG => 'Selangor',

            self::GEORGETOWN,
            self::BUTTERWORTH,
            self::BAYAN_LEPAS,
            self::BATU_FERRINGHI,
            self::AIR_ITAM,
            self::JELUTONG,
            self::BUKIT_MERTAJAM => 'Penang',

            self::JOHOR_BAHRU,
            self::SKUDAI,
            self::ISKANDAR_PUTERI,
            self::KULAI,
            self::BATU_PAHAT,
            self::MUAR,
            self::MASAI => 'Johor',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function groupedByState(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->state()][] = $case;
        }

        return $grouped;
    }
}
