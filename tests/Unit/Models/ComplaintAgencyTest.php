<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ComplaintChannelType;
use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use App\Models\ComplaintCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ComplaintAgencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_excludes_inactive_agencies(): void
    {
        ComplaintAgency::factory()->create(['is_active' => true]);
        ComplaintAgency::factory()->create(['is_active' => false]);

        $this->assertCount(1, ComplaintAgency::query()->active()->get());
    }

    public function test_when_to_contact_is_cast_to_array(): void
    {
        $agency = ComplaintAgency::factory()->create(['when_to_contact' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $agency->fresh()->when_to_contact);
    }

    public function test_channels_are_ordered_by_order_column(): void
    {
        $agency = ComplaintAgency::factory()->create();
        ComplaintAgencyChannel::factory()->create(['complaint_agency_id' => $agency->id, 'label' => 'B', 'order' => 2]);
        ComplaintAgencyChannel::factory()->create(['complaint_agency_id' => $agency->id, 'label' => 'A', 'order' => 1]);

        $this->assertSame(['A', 'B'], $agency->channels->pluck('label')->all());
    }

    public function test_agency_and_category_are_linked_many_to_many(): void
    {
        $agency = ComplaintAgency::factory()->create();
        $category = ComplaintCategory::factory()->create();

        $category->agencies()->attach($agency->id, ['order' => 1]);

        $this->assertTrue($agency->categories->contains($category));
        $this->assertTrue($category->agencies->contains($agency));
    }

    public function test_deleting_agency_hard_cascades_to_channels(): void
    {
        $agency = ComplaintAgency::factory()->create();
        ComplaintAgencyChannel::factory()->create(['complaint_agency_id' => $agency->id]);

        $agency->forceDelete();

        $this->assertSame(0, ComplaintAgencyChannel::query()->count());
    }

    #[DataProvider('urlProvider')]
    public function test_channel_url_per_type(ComplaintChannelType $type, string $value, ?string $expected): void
    {
        $channel = ComplaintAgencyChannel::factory()->make(['type' => $type, 'value' => $value]);

        $this->assertSame($expected, $channel->url());
    }

    /**
     * @return array<string, array{ComplaintChannelType, string, ?string}>
     */
    public static function urlProvider(): array
    {
        return [
            'website https' => [ComplaintChannelType::Website, 'https://lapor.go.id', 'https://lapor.go.id'],
            'website javascript ditolak' => [ComplaintChannelType::Website, 'javascript:alert(1)', null],
            'app url' => [ComplaintChannelType::App, 'https://play.google.com/x', 'https://play.google.com/x'],
            'whatsapp 08 jadi 62' => [ComplaintChannelType::Whatsapp, '0811-2011-151', 'https://wa.me/628112011151'],
            'whatsapp +62' => [ComplaintChannelType::Whatsapp, '(+62) 81-157-157-157', 'https://wa.me/6281157157157'],
            'whatsapp kosong' => [ComplaintChannelType::Whatsapp, '---', null],
            'phone pendek' => [ComplaintChannelType::Phone, '151', 'tel:151'],
            'phone dengan spasi' => [ComplaintChannelType::Phone, '(021) 3441839', 'tel:0213441839'],
            'email' => [ComplaintChannelType::Email, 'a@b.go.id', 'mailto:a@b.go.id'],
            'address tanpa url' => [ComplaintChannelType::Address, 'Jl. Jambu No. 32', null],
        ];
    }
}
