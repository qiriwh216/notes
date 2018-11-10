<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function rules()
    {
        $book = $this->route('book');

        return [
            'page'         => 'bail|required|integer|max:' . $book->total,
            'title'        => 'bail|nullable|string|max:255',
            'desc'         => 'bail|nullable|string|max:255',
            'content'      => 'bail|required|string|max:60000',
            'html_content' => 'bail|required|string|max:60000',
            'tags'         => 'array',
        ];
    }

    public function attributes()
    {
        return [
            'page'    => '页数',
            'title'   => '标题',
            'desc'    => '描述',
            'content' => '内容',
            'tags'    => '标签',
        ];
    }

    public function messages()
    {
        return [
            'page.max'   => ':attribute不能超过:max页',
            'tags.array' => ':attribute数据格式不对',
        ];
    }
}