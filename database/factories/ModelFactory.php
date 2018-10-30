<?php

use Faker\Generator as Faker;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, function (Faker $faker) {
    static $password;

    return [
        'username' => $faker->name,
        'password' => $password ?: $password = bcrypt('000000'),
    ];
});

$factory->define(App\Models\Book::class, function (Faker $faker) {
    $total = mt_rand(200, 900);
    $read = mt_rand(0, $total);

    return [
        'title'      => $faker->sentence,
        'started_at' => $faker->dateTimeBetween('-2 months'),
        'created_at' => $faker->dateTimeBetween('-2 months'),
        'updated_at' => $faker->dateTimeBetween('-2 months'),
        'read'       => $read,
        'total'      => $total,
        'cover'      => '',
        'deleted_at' => null,
        'hidden'     => false,
    ];
});

$factory->define(App\Models\Note::class, function (Faker $faker) {
    return [
        'book_id'      => mt_rand(1, 1000),
        'title'        => $faker->sentence,
        'desc'         => $faker->sentence,
        'content'      => $faker->paragraph(10),
        'html_content' => '<h1>HTML_CONTENT</h1>'.$faker->paragraph(10),
        'page'         => mt_rand(1, 1000),
        'created_at'   => $faker->dateTimeBetween('-2 months'),
        'updated_at'   => $faker->dateTimeBetween('-2 months'),
        'deleted_at'   => null,
        'hidden'       => false,
    ];
});
