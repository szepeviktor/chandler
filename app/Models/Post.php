<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'journal_id',
        'title',
        'published',
        'written_at',
        'updated_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'written_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published' => 'boolean',
    ];

    /**
     * Get the journal associated with the post.
     *
     * @return BelongsTo
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the post sections associated with the post.
     *
     * @return HasMany
     */
    public function postSections(): HasMany
    {
        return $this->hasMany(PostSection::class);
    }

    /**
     * Get the contacts associated with the post.
     *
     * @return BelongsToMany
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the post's feed item.
     *
     * @return MorphOne
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value) {
                    return $value;
                }

                return trans('app.undefined');
            },
            set: fn ($value) => $value,
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $section = $this->postSections()
                    ->orderBy('position')
                    ->whereNotNull('content')
                    ->first();

                if (! $section) {
                    return null;
                }

                return $section->content;
            }
        );
    }
}
