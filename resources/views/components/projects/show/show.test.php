<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('projects.show')
        ->assertStatus(200);
});
