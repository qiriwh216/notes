<?php

namespace App\Http\Controllers;

use App\Filters\BookFilter;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $notes = Note::latest()
            ->with(['book' => function ($query) {
                $query->withTrashed();
            }])
            ->whereHas('book', function ($query) use ($request) {
                $query->filter(app(BookFilter::class)->only(['edit_mode']));
            })
            ->paginate();

        return NoteResource::collection($notes)->except(['updated_at', 'created_at', 'content', 'html_content']);
    }
}
