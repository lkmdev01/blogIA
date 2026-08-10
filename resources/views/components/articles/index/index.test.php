<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('articles.index')
        ->assertStatus(200);
});
