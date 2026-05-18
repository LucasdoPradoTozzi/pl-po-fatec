<?php

use App\Livewire\Operations\Lab;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('editing any problem field invalidates old results and closes modal', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(Lab::class)
        ->set('state.academicResult', [
            'status' => 'optimal',
            'steps' => [[
                'step' => 1,
                'pivot_column' => 1,
                'pivot_row' => 1,
                'entering_variable' => 'x1',
                'leaving_variable' => 's1',
                'tableau_after' => [[1, 0, 10], [0, 1, 5]],
                'operations' => ['L1 = L1 / 1'],
                'explanation' => 'ok',
            ]],
        ])
        ->set('state.activeModal', 'academic')
        ->set('problem.title', 'Novo titulo')
        ->assertSet('state.academicResult', null)
        ->assertSet('state.industrialResult', null)
        ->assertSet('state.activeModal', null)
        ->assertSet('state.dirtyState', true);
});
