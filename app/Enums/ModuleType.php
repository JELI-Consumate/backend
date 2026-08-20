<?php

declare(strict_types=1);

namespace App\Enums;

enum ModuleType: string
{
    case Opening = 'opening';
    case Video = 'video';
    case Materi = 'materi';
    case Infografis = 'infografis';
    case Komik = 'komik';
    case Kuis = 'kuis';
    case Simulasi = 'simulasi';
    case Refleksi = 'refleksi';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Pembuka Journey',
            self::Video => 'Video Pembelajaran',
            self::Materi => 'Materi Interaktif',
            self::Infografis => 'Infografis',
            self::Komik => 'Komik Edukatif',
            self::Kuis => 'Kuis Evaluasi',
            self::Simulasi => 'Simulasi Interaktif',
            self::Refleksi => 'Refleksi Pembelajaran',
        };
    }
}
