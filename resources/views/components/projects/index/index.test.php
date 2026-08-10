<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('projects.index')
        ->assertStatus(200);
});
