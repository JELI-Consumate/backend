<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Support\AdminScope;
use App\Models\Module;
use App\Models\ModulePage;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dipakai di halaman Create{X}Content supaya bisa dibuat langsung dari tab
 * "Pages" milik Module (lihat PagesRelationManager), bukan cuma lewat
 * dropdown "pilih konten yang sudah ada". Kalau request datang dengan query
 * "module_id" (dari tombol "Buat Baru" di tab Pages), konten yang baru
 * dibuat otomatis ditempel jadi ModulePage, lalu diarahkan balik ke halaman
 * edit Module tersebut alih-alih ke list index resource ini.
 */
trait AttachesContentToModulePage
{
    /**
     * Ditangkap sekali di mount() dari query string ?module_id=..., lalu
     * disimpan sebagai property Livewire biasa — BUKAN dibaca ulang lewat
     * request()->query() di afterCreate()/getRedirectUrl(), karena submit
     * form (wire:submit) adalah request Livewire terpisah yang tidak lagi
     * membawa query string halaman aslinya.
     */
    public ?string $attachToModuleId = null;

    public function mount(): void
    {
        parent::mount();

        $moduleId = request()->query('module_id');

        $this->attachToModuleId = filled($moduleId) ? (string) $moduleId : null;
    }

    protected function getRedirectUrl(): string
    {
        if ($module = $this->getTargetModuleForAttachment()) {
            return ModuleResource::getUrl('edit', ['record' => $module->getKey()]);
        }

        return parent::getRedirectUrl();
    }

    protected function afterCreate(): void
    {
        $module = $this->getTargetModuleForAttachment();

        if (! $module) {
            return;
        }

        $nextOrder = ((int) ModulePage::query()->where('module_id', $module->getKey())->max('order')) + 1;

        ModulePage::create([
            'module_id' => $module->getKey(),
            'order' => $nextOrder,
            'contentable_type' => $this->record->getMorphClass(),
            'contentable_id' => $this->record->getKey(),
        ]);
    }

    /**
     * Validasi ulang module_id terhadap scope admin yang sedang login —
     * jangan percaya begitu saja nilai dari URL (admin sector tidak boleh
     * nempelin konten baru ke module milik sector lain cuma dengan
     * mengarang query string).
     */
    private function getTargetModuleForAttachment(): ?Module
    {
        if (! $this->attachToModuleId) {
            return null;
        }

        return Module::withoutGlobalScopes()
            ->whereKey($this->attachToModuleId)
            ->when(
                AdminScope::restrictedSectorId(),
                fn (Builder $query, string $sectorId) => $query->whereHas(
                    'journey',
                    fn (Builder $journeyQuery) => $journeyQuery->withoutGlobalScopes()->where('sector_id', $sectorId)
                )
            )
            ->first();
    }
}
