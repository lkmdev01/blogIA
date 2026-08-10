<?php

namespace App\Models;

use Database\Factories\InternalLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'article_id',
    'linked_article_id',
    'anchor_text',
    'context',
])]
class InternalLink extends Model
{
    /** @use HasFactory<InternalLinkFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function linkedArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'linked_article_id');
    }
}
