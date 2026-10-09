<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplaintChannelType;
use Database\Factories\ComplaintAgencyChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['complaint_agency_id', 'type', 'label', 'value', 'button_label', 'order'])]
class ComplaintAgencyChannel extends Model
{
    /** @use HasFactory<ComplaintAgencyChannelFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'type' => ComplaintChannelType::class,
        ];
    }

    /**
     * @return BelongsTo<ComplaintAgency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(ComplaintAgency::class, 'complaint_agency_id');
    }

    /**
     * URL siap-pakai untuk tombol di app (tel:, mailto:, wa.me, https), atau
     * null kalau kanal ini hanya informasi (alamat) / nilainya tidak valid.
     */
    public function url(): ?string
    {
        $value = trim($this->value);

        return match ($this->type) {
            ComplaintChannelType::Website, ComplaintChannelType::App => preg_match('#^https?://#i', $value) === 1 ? $value : null,
            ComplaintChannelType::Whatsapp => $this->whatsappUrl($value),
            ComplaintChannelType::Phone => ($digits = preg_replace('/[^\d+]/', '', $value)) !== '' ? 'tel:'.$digits : null,
            ComplaintChannelType::Email => 'mailto:'.$value,
            ComplaintChannelType::Address => null,
        };
    }

    private function whatsappUrl(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }
}
