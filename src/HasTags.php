<?php

namespace Spatie\Tags;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    protected $queuedTags = [];

    public static function bootHasTags()
    {
        static::created(function (Model $taggableModel) {
            $taggableModel->attachTags($taggableModel->queuedTags);

            $taggableModel->queuedTags = [];
        });
    }

    public function tags(): MorphToMany
    {
        return $this
            ->morphToMany(Tag::class, 'taggable')
            ->orderBy('order_column');
    }

    /**
     * @param string|array|\ArrayAccess|\Spatie\Tags\Tags $tags
     */
    public function setTagsAttribute($tags)
    {
        if (! $this->exists) {
            $this->queuedTags = $tags;

            return;
        }
        $this->attachTags($tags);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|\ArrayAccess|\Spatie\Tags\Tags $tags
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAllTags(Builder $query, $tags): Builder
    {
        /** @TODO implement */
        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|\ArrayAccess|\Spatie\Tags\Tags $tags
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAnyTags(Builder $query, $tags): Builder
    {
        if (! $this->isIterable($tags)) {
            $tags = [$tags];
        }

        dd($tags);

        if (! count($tags)) {
            return $query;
        }

        return $query->whereHas('tags', function(Builder $query) use ($tags) {
            $query->whereIn('id', dd(collect($tags)->pluck('id')->toArray()));
        });
    }

    public function tagsOfType(string $type = null): Collection
    {
        return $this->tags->filter(function (Tag $tag) use ($type) {
            return $tag->type === $type;
        });
    }

    /**
     * @param array|\ArrayAccess|\Spatie\Tags\Tag $tags
     *
     * @return $this
     */
    public function attachTags($tags)
    {
        if (!$this->isIterable($tags)) {
            $tags = [$tags];
        }

        if (! count($tags)) {
            return $this;
        }

        $tags = Tag::findOrCreate($tags);

        collect($tags)->each(function (Tag $tag) {
            $this->tags()->attach($tag);
        });

        return $this;
    }

    /**
     * @param array|\ArrayAccess|\Spatie\Tags\Tag $tags
     *
     * @return $this
     */
    public function attachTag($tags)
    {
        return $this->attachTags($tags);
    }

    /**
     * @param array|\ArrayAccess|\Spatie\Tags\Tag $tags
     *
     * @return $this
     */
    public function detachTags($tags)
    {
        if (!$this->isIterable($tags)) {
            $tags = [$tags];
        }

        $tags = Tag::findOrCreate($tags);

        collect($tags)->each(function (Tag $tag) {
            $this->tags()->detach($tag);
        });

        return $this;
    }

    /**
     * @param array|\ArrayAccess|\Spatie\Tags\Tag $tags
     *
     * @return $this
     */
    public function detachTag($tags)
    {
        return $this->detachTags($tags);
    }

    /**
     * @param array|\ArrayAccess $tags
     *
     * @return $this
     */
    public function syncTags($tags)
    {
        $tags = Tag::findOrCreate($tags);

        $this->tags()->sync($tags);

        return $this;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isIterable($value): bool
    {
        if (is_array($value)) {
            return true;
        }

        return ($value instanceof ArrayAccess);
    }
}