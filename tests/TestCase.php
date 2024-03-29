<?php

namespace Tests;

use App\Models\Book;
use App\Models\Note;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $user;
    protected $token;

    protected function login()
    {
        $user = create(User::class, ['username' => 'largezhou']);
        $this->actingAs($user);

        $this->user = $user;
        $this->token = auth()->tokenById($user->id);
    }

    /**
     * 测试响应中的某个键的数据，是否按指定的顺序显示
     *
     * @param TestResponse $response json响应
     * @param string       $orderBy 排序字段
     * @param string       $orderType 排序类型 desc 或 asc
     * @param string|null  $key 数据中的那个键
     */
    protected function assertOrderBy(TestResponse $response, string $orderBy, string $orderType, string $key = null)
    {
        $data = json_decode($response->getContent(), true);
        if ($key) {
            $data = $data[$key];
        }

        $json = json_encode($data);

        $sortFunc = $orderType == 'desc' ? 'sortByDesc' : 'sortBy';
        $ordered = collect($data)->$sortFunc($orderBy);

        $orderedJson = json_encode($ordered);

        $this->assertEquals($orderedJson, $json, "按 ({$orderBy}) 的 ({$orderType}) 排序不对");
    }

    /**
     * 生成10本书数据，1 为软删除，2 为隐藏
     *
     * @throws \Exception
     */
    protected function prepareBooks()
    {
        Book::truncate();
        create(Book::class, [], 10);

        Book::find(1)->delete();
        Book::find(2)->update(['hidden' => true]);
    }

    /**
     * 每本书生成10条笔记，每本书的第一个笔记为软删除的，第二个为隐藏的
     *
     * @throws \Exception
     */
    protected function prepareNotes()
    {
        Note::truncate();
        // 100条笔记
        Book::showAll()->get()->each(function (Book $book) {
            $notesData = factory(Note::class, 10)->make()->each(function (Note $note) use ($book) {
                $note->page = mt_rand(1, $book->read);
            });
            $notesData[0]['deleted_at'] = Carbon::now();
            $notesData[1]['hidden'] = true;

            $book->notes()->saveMany($notesData);
        });

        // 添加20篇博客，即 book_id 为0的
        create(Post::class, [], 20);
        $posts = Post::limit(2)->get();
        $posts[0]->update(['deleted_at' => Carbon::now()]);
        $posts[1]->update(['hidden' => true]);
    }

    /**
     * 断言一个 json 字符串中，是否包含某个字符串片段
     *
     * @param string $needle 要查找的片段
     * @param string $haystack 原始 json 字符串
     */
    protected function assertJsonContains(string $needle, string $haystack)
    {
        $haystack = json_encode(json_decode($haystack, true), JSON_UNESCAPED_UNICODE);

        $this->assertContains($needle, $haystack);
    }

    /**
     * 是否看到某个表单验证消息
     *
     * @param TestResponse $res
     * @param string|null  $text
     *
     * @return TestResponse
     */
    protected function seeErrorText(TestResponse $res, string $text = null)
    {
        $res = $res->assertStatus(422);

        if ($text !== null) {
            $res = $res->assertSee(json_get_str($text));
        }

        return $res;
    }
}
