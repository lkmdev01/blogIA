<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('dashboard.overview')
        ->assertStatus(200);
});
