<?php

namespace App\Http\Controllers;

use App\Filters\TagFilter;
use App\Http\Requests\TagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(TagFilter $tagFilter, Tag $tag)
    {
        $tags = $tag
            ->withCount(['targets' => function ($query) {
                $query->whereHas('baseNote');
            }])
            ->orderByDesc('targets_count')
            ->filter($tagFilter)
            ->get();

        return TagResource::collection($tags);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return $this->noContent();
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $tag->update($request->all());

        return $this->noContent();
    }

    public function show(Tag $tag)
    {
        $tag->load(['notes', 'posts', 'notes.book', 'notes.tags', 'posts.tags']);
        $tag->setAttribute('targets_count', $tag->targets()->whereHas('baseNote')->count());

        return TagResource::make($tag);
    }
}
