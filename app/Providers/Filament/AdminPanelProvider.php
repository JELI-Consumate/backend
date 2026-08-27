<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            /**
             * Urutan grup sidebar sengaja didaftarkan eksplisit di sini —
             * tanpa ini Filament mengurutkan grup berdasarkan urutan
             * kemunculan item lintas resource (hasil auto-discover),
             * bukan alur kerja sebenarnya. Urutan di bawah mengikuti alur
             * pengisian data: struktur (Sector -> Journey -> Module) lebih
             * dulu, baru konten, lalu data pengguna, lalu administrasi.
             *
             * Tidak diberi ->icon() di level grup — Filament melarang grup
             * dan item di dalamnya sama-sama punya ikon, dan tiap resource
             * di bawah sudah punya ikon sendiri-sendiri yang lebih berguna
             * untuk membedakan Sector/Journey/Module & jenis konten.
             */
            ->navigationGroups([
                NavigationGroup::make('Struktur Belajar'),
                NavigationGroup::make('Konten Modul'),
                NavigationGroup::make('Pengguna & Analitik'),
                NavigationGroup::make('Administrasi'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
