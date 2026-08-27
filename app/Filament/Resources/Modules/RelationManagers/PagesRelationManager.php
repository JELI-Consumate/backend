<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\ContentableType;
use App\Filament\Resources\ArticleContents\ArticleContentResource;
use App\Filament\Resources\QuizContents\QuizContentResource;
use App\Filament\Resources\ReflectionContents\ReflectionContentResource;
use App\Filament\Resources\SimulationContents\SimulationContentResource;
use App\Filament\Resources\VideoContents\VideoContentResource;
use App\Filament\Support\AdminScope;
use App\Models\ArticleContent;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    private static function contentModel(?string $type): ?string
    {
        return match ($type) {
            ContentableType::Video->value => VideoContent::class,
            ContentableType::Article->value => ArticleContent::class,
            ContentableType::Quiz->value => QuizContent::class,
            ContentableType::Simulation->value => SimulationContent::class,
            ContentableType::Reflection->value => ReflectionContent::class,
            default => null,
        };
    }

    /**
     * Resource Filament yang punya form pembuatan/pengeditan konten
     * sesungguhnya untuk tiap tipe — dipakai buat tombol "Buat Baru" & "Edit
     * Konten" di bawah, supaya nempel langsung ke form aslinya (title,
     * blocks, segments, dst), bukan cuma dropdown pilih yang sudah ada.
     */
    private static function contentResource(?string $type): ?string
    {
        return match ($type) {
            ContentableType::Video->value => VideoContentResource::class,
            ContentableType::Article->value => ArticleContentResource::class,
            ContentableType::Quiz->value => QuizContentResource::class,
            ContentableType::Simulation->value => SimulationContentResource::class,
            ContentableType::Reflection->value => ReflectionContentResource::class,
            default => null,
        };
    }

    private static function contentTypeLabel(ContentableType $type): string
    {
        return match ($type) {
            ContentableType::Video => 'Video',
            ContentableType::Article => 'Artikel',
            ContentableType::Quiz => 'Kuis',
            ContentableType::Simulation => 'Simulasi',
            ContentableType::Reflection => 'Refleksi',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('contentable_type')
                ->label('Tipe Konten')
                ->options(collect(ContentableType::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                ->live()
                ->required(),
            Select::make('contentable_id')
                ->label('Konten')
                ->options(function ($get) {
                    $model = self::contentModel($get('contentable_type'));

                    if (! $model) {
                        return [];
                    }

                    $query = $model === QuizContent::class
                        ? AdminScope::scopeQuizContentSector($model::query())
                        : AdminScope::scopeSectorContent($model::query());

                    return $query->pluck('title', 'id');
                })
                ->searchable()
                ->required(),
            TextInput::make('order')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order')
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('contentable_type')->label('Tipe'),
                TextColumn::make('contentable.title')->label('Judul Konten'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                // Bikin konten baru langsung dari form aslinya (title,
                // blocks, segments, dst.) — begitu tersimpan, otomatis
                // ditempel ke module ini dan kembali ke halaman ini. Lihat
                // App\Filament\Concerns\AttachesContentToModulePage.
                ActionGroup::make(
                    collect(ContentableType::cases())
                        ->map(fn (ContentableType $type) => Action::make("create-{$type->value}")
                            ->label(self::contentTypeLabel($type))
                            ->url(fn (RelationManager $livewire): string => self::contentResource($type->value)::getUrl('create').'?'.http_build_query([
                                'module_id' => $livewire->getOwnerRecord()->getKey(),
                            ])))
                        ->all()
                )
                    ->label('Buat Konten Baru')
                    ->icon(Heroicon::OutlinedPlus)
                    ->button(),
                // Tempel konten yang sudah ada sebelumnya (reuse).
                CreateAction::make()
                    ->label('Pakai Konten yang Sudah Ada'),
            ])
            ->recordActions([
                Action::make('editContent')
                    ->label('Edit Konten')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(function ($record): ?string {
                        $resource = self::contentResource($record->contentable_type);

                        return $resource ? $resource::getUrl('edit', ['record' => $record->contentable_id]) : null;
                    })
                    ->visible(fn ($record): bool => self::contentResource($record->contentable_type) !== null),
                EditAction::make()
                    ->label('Ganti Penempatan'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
