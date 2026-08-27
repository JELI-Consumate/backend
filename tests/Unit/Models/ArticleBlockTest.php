<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ArticleBlockType;
use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_item_number_is_null_for_non_list_item_blocks(): void
    {
        $block = ArticleBlock::factory()->create(['block_type' => ArticleBlockType::Paragraph]);

        $this->assertNull($block->list_item_number);
    }

    /**
     * Nomor bullet dihitung cuma dari sesama block list_item, bukan dari
     * semua block campur tipe lain — supaya paragraph/gambar yang
     * diselipkan di antara list_item tidak ikut menaikkan nomornya.
     */
    public function test_list_item_number_counts_only_among_sibling_list_items(): void
    {
        $article = ArticleContent::factory()->create();

        $first = ArticleBlock::factory()->create([
            'article_content_id' => $article->id,
            'block_type' => ArticleBlockType::ListItem,
            'order' => 1,
        ]);
        ArticleBlock::factory()->create([
            'article_content_id' => $article->id,
            'block_type' => ArticleBlockType::Paragraph,
            'order' => 2,
        ]);
        $second = ArticleBlock::factory()->create([
            'article_content_id' => $article->id,
            'block_type' => ArticleBlockType::ListItem,
            'order' => 3,
        ]);
        $third = ArticleBlock::factory()->create([
            'article_content_id' => $article->id,
            'block_type' => ArticleBlockType::ListItem,
            'order' => 4,
        ]);

        $this->assertSame(1, $first->fresh()->list_item_number);
        $this->assertSame(2, $second->fresh()->list_item_number);
        $this->assertSame(3, $third->fresh()->list_item_number);
    }

    public function test_list_item_number_is_scoped_to_its_own_article(): void
    {
        $articleA = ArticleContent::factory()->create();
        $articleB = ArticleContent::factory()->create();

        ArticleBlock::factory()->create([
            'article_content_id' => $articleA->id,
            'block_type' => ArticleBlockType::ListItem,
            'order' => 1,
        ]);
        $firstInB = ArticleBlock::factory()->create([
            'article_content_id' => $articleB->id,
            'block_type' => ArticleBlockType::ListItem,
            'order' => 1,
        ]);

        $this->assertSame(1, $firstInB->fresh()->list_item_number);
    }
}
