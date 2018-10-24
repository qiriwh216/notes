<?php

namespace App\Models;

use App\Filters\BookFilter;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    /**
     * 书籍挂件的书籍数量
     */
    const VERY_RECENT_COUNT = 5;

    protected $fillable = ['title', 'total', 'read', 'started_at', 'cover'];

    public static function getBooks()
    {
        return static::orderBy('updated_at', 'desc')
                     ->filter(app(BookFilter::class))->get();
    }

    public static function addBook($data)
    {
        $data['hidden'] = false;
        static::unguard();

        return static::create($data);
    }
}