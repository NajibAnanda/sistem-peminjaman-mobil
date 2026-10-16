<?php

use App\Support\TemporaryPasswordGenerator;
use Tests\TestCase;

uses(TestCase::class);

test('password sementara selalu lolos kebijakan dan mudah dibaca', function () {
    $generator = new TemporaryPasswordGenerator;

    foreach (range(1, 200) as $ignored) {
        $password = $generator->generate();

        expect($password)
            ->toHaveLength(12)
            ->toMatch('/[A-Za-z]/')
            ->toMatch('/\d/')
            ->not->toMatch('/[0O1lI]/');
    }
});
