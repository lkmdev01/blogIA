<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('articles.edit')
        ->assertStatus(200);
});
