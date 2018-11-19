<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Note;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestCanVisitShowAndIndexOnly()
    {
        $this->prepareBook();

        $res = $this->getBooks();
        $res->assertStatus(200)
            ->assertJsonCount(8);

        // 未登录的情况下，即使传了 edit_mode 也不能看到隐藏的和软删除的
        $this->getBooks(['edit_mode' => 1])
            ->assertJsonCount(8)
            ->assertDontSee('hidden')
            ->assertDontSee('deleted_at');

        // 书籍详情页
        $this->getBook(1)->assertStatus(404);
        $this->getBook(2)->assertStatus(404);
        $this->getBook(3)->assertStatus(200)->assertDontSee('hidden')->assertDontSee('deleted_at');

        // 添加
        $this->postCreateBook()
            ->assertStatus(401);

        // 删除，由于删除书，使用了模型的绑定，所以查询模型会先于权限判定，所以要使用一个没有隐藏且没有软删除的
        $this->destroyBook(10)
            ->assertStatus(401);

        // 彻底删除
        $this->forceDestroyBook()
            ->assertStatus(401);

        // 更新
        $this->updateBook()
            ->assertStatus(401);
    }

    protected function getBooks($params = [])
    {
        return $this->json('get', route('books.index'), $params);
    }

    public function testGetBooks()
    {
        $this->login();

        $this->prepareBook();

        $this->getBooks()
            ->assertStatus(200)
            ->assertJsonCount(9)
            ->assertSee('hidden')
            ->assertSee('deleted_at');

        $this->getBooks(['edit_mode' => 1])
            ->assertJsonCount(10);
    }

    protected function postCreateBook($book = [])
    {
        return $this->json('post', route('books.store'), $book);
    }

    public function testCreateBook()
    {
        $this->login();

        $this->postCreateBook()
            ->assertStatus(422)
            ->assertSee('title')
            ->assertSee('total')
            ->assertSee('cover');

        $cover = UploadedFile::fake()->image('cover.jpg');
        $book = collect(make(Book::class, ['cover' => $cover, 'hidden' => 1, 'deleted_at' => (string) Carbon::now()]))
            ->only(['title', 'total', 'read', 'started_at', 'cover', 'hidden', 'deleted_at'])
            ->toArray();

        $input = $book;
        $input['read'] = $input['total'] + 1;
        $this->postCreateBook($input)
            ->assertStatus(422)
            ->assertSee(json_encode('已读不能大于' . $input['total']));

        $input = $book;
        $input['cover'] = 'not a file';
        $this->postCreateBook($input)
            ->assertStatus(422)
            ->assertSee(json_encode('封面不是图片不行的'));

        $input = $book;
        $input['read'] = null;
        $res = $this->postCreateBook($input);
        $res->assertStatus(201)
            ->assertSee('id');

        $seeData = array_except($input, ['cover']);
        $seeData['cover'] = '/uploads/' . md5_file($input['cover']) . '.jpg';
        $seeData['deleted_at'] = null;
        $seeData['read'] = 0;
        $this->assertDatabaseHas((new Book)->getTable(), $seeData);
    }

    protected function destroyBook($id = null)
    {
        return $this->json('delete', route('books.destroy', ['book' => $id ?: 1]));
    }

    public function testDestroyBook()
    {
        $this->login();

        $this->prepareBook();

        // 已经软删除的无法查询到
        $this->destroyBook(1)
            ->assertStatus(404);

        // 正常软删除
        $this->destroyBook(2)
            ->assertStatus(204);

        $this->assertDatabaseHas((new Book())->getTable(), ['id' => 2, 'deleted_at' => Carbon::now()]);
    }

    protected function forceDestroyBook($id = null)
    {
        return $this->json('delete', route('books.force_destroy', ['id' => $id ?: 1]));
    }

    public function testForceDestroyBook()
    {
        $this->login();

        $this->prepareBook();
        Book::showAll()->find(1)->notes()->save(make(Note::class));
        Note::find(1)->tags()->save(make(Tag::class));

        // 没有被软删除的不能彻底删除
        $this->forceDestroyBook(2)
            ->assertStatus(404);

        $this->forceDestroyBook(1)
            ->assertStatus(204);

        $this->assertDatabaseMissing('books', ['id' => 1]);
        $this->assertDatabaseMissing('notes', ['id' => 1]);
        $this->assertDatabaseMissing('model_tags', [
            'tag_id'      => 1,
            'target_id'   => 1,
            'target_type' => 'notes',
        ]);
    }

    protected function getBook($id = null, $params = [])
    {
        return $this->json('get', route('books.show', ['book' => $id ?: 1]), $params);
    }

    public function testShowBook()
    {
        // 登录的情况下
        $this->login();

        $this->prepareNotes();

        // 软删除的
        $this->getBook(1)
            ->assertStatus(404);

        // 隐藏的
        $this->getBook(2)
            ->assertSee('"hidden":"1"');

        // 笔记有软删除的
        $this->getBook(3)
            ->assertJsonCount(9, 'notes');

        // 笔记编辑模式显示所有
        $this->getBook(3, ['edit_mode' => 1])
            ->assertJsonFragment(['notes_count' => 10])
            ->assertJsonCount(10, 'notes');
    }

    public function testBookNotesSort()
    {
        $this->prepareNotes();

        // 默认笔记所属页数倒序
        $this->assertOrderBy($this->getBook(4), 'page', 'desc', 'notes');

        $this->assertOrderBy($this->getBook(4, [
            '_sort_field' => 'page',
            '_sort_type'  => 'asc',
        ]), 'page', 'asc', 'notes');

        $this->assertOrderBy($this->getBook(4, [
            '_sort_field' => 'created_at',
            '_sort_type'  => 'asc',
        ]), 'created_at', 'dsc', 'notes');

        $this->assertOrderBy($this->getBook(4, [
            '_sort_field' => 'created_at',
            '_sort_type'  => 'desc',
        ]), 'created_at', 'desc', 'notes');
    }

    protected function updateBook($id = null, $data = [])
    {
        return $this->json('put', route('books.update', ['book' => $id ?: 1]), $data);
    }

    public function testUpdateHidden()
    {
        $this->login();

        $this->prepareBook();

        // 显示
        $this->updateBook(2, ['hidden' => false]);
        $this->assertDatabaseHas((new Book())->getTable(), ['id' => 2, 'hidden' => 0]);

        // 隐藏
        $this->updateBook(2, ['hidden' => true]);
        $this->assertDatabaseHas((new Book())->getTable(), ['id' => 2, 'hidden' => 1]);
    }

    public function testRestoreBook()
    {
        $this->login();

        $this->prepareBook();

        $this->updateBook(1, ['deleted_at' => null]);
        $this->assertDatabaseHas((new Book())->getTable(), ['id' => 1, 'deleted_at' => null]);
    }

    public function testUpdateBook()
    {
        $this->login();
        $this->prepareBook();

        $this->updateBook(1, [
            'title' => 'update title',
            'total' => '900',
            'read'  => '666',
        ])->assertStatus(200);

        $this->assertDatabaseHas((new Book())->getTable(), [
            'title' => 'update title',
            'total' => '900',
            'read'  => '666',
        ]);
    }

    public function testUpdateReadOrTotalOnly()
    {
        $this->login();
        $this->prepareBook();


        $book = Book::editMode()->first();

        $res = $this->updateBook(1, ['read' => '10000']);
        $res->assertStatus(422)
            ->assertSee(json_encode(['read' => ['已读不能大于' . $book->total]]));

        $res = $this->updateBook(1, ['total' => '1']);
        $res->assertStatus(422)
            ->assertSee(json_encode(['total' => ['总页数不能小于' . $book->read]]));
    }
}
