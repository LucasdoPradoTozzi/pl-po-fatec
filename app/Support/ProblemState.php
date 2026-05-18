<?php

namespace App\Support;

final class ProblemState
{
    /**
     * @return array<string, mixed>
     */
    public static function initial(): array
    {
        return [
            'aiGenerated' => false,
            'userEdited' => false,
            'validationState' => 'idle',
            'academicResult' => null,
            'industrialResult' => null,
            'activeModal' => null,
            'dirtyState' => false,
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public static function invalidateComputed(array $state): array
    {
        $state['academicResult'] = null;
        $state['industrialResult'] = null;
        $state['activeModal'] = null;
        $state['dirtyState'] = true;

        return $state;
    }
}
