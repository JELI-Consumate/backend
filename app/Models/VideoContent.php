<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VideoContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'youtube_url', 'prompt_question'])]
class VideoContent extends Model
{
    /** @use HasFactory<VideoContentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return MorphOne<ModulePage, $this>
     */
    public function modulePage(): MorphOne
    {
        return $this->morphOne(ModulePage::class, 'contentable');
    }

    protected function youtubeVideoId(): Attribute
    {
        return Attribute::get(fn (): ?string => preg_match(
            '~(?:youtu\.be/|v=|embed/)([A-Za-z0-9_-]{11})~',
            $this->youtube_url,
            $m
        ) ? $m[1] : null)->shouldCache();
    }
}
