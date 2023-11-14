<?php

namespace RahulHaque\Filepond\Tests;

use RahulHaque\Filepond\Tests\User;
use Faker\Generator as Faker;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'remember_token' => \Illuminate\Support\Str::random(10),
    ];
});
