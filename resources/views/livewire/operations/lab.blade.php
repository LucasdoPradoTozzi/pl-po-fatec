<section class="w-full">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-amber-50 via-white to-cyan-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Laboratorio de Pesquisa Operacional</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Descreva o problema em linguagem natural, revise o modelo matematico e compare os modos academico e industrial.</p>
        </div>

        @if ($errorMessage)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
            {{ $errorMessage }}
        </div>
        @endif

        @if ($successMessage)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ $successMessage }}
        </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">1. Descreva o problema (opcional IA)</h2>
                <textarea
                    wire:model.live.debounce.400ms="description"
                    class="mt-3 h-48 w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="Ex.: Uma fabrica produz camisetas e moletons com limite de tecido e horas de costura..."
                    @disabled($this->isEditingLocked)
                ></textarea>

                <div class="mt-4 flex gap-3">
                    <flux:button wire:click="generateModel" variant="primary" :disabled="$loadingAi || $this->isEditingLocked">
                        @if ($loadingAi)
                        Gerando...
                        @else
                        Gerar Modelo
                        @endif
                    </flux:button>
                </div>

                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">Se preferir, pule a IA e preencha manualmente o modelo abaixo.</p>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">Estado do problema</h2>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50">
                        <dt class="text-zinc-500">AI Generated</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $state['aiGenerated'] ? 'Sim' : 'Nao' }}</dd>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50">
                        <dt class="text-zinc-500">User Edited</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $state['userEdited'] ? 'Sim' : 'Nao' }}</dd>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50">
                        <dt class="text-zinc-500">Validation</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $state['validationState'] }}</dd>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50">
                        <dt class="text-zinc-500">Dirty</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $state['dirtyState'] ? 'Sim' : 'Nao' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">2. Preencha e revise o modelo</h2>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Titulo</label>
                    <input type="text" wire:model.live="problem.title" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($this->isEditingLocked)>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Resumo</label>
                    <input type="text" wire:model.live="problem.problem_summary" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($this->isEditingLocked)>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Objetivo (expressao)</label>
                    <input type="text" wire:model.live="problem.objective.expression" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="3x1 + 5x2" @disabled($this->isEditingLocked)>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tipo</label>
                    <select wire:model.live="problem.objective.type" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($this->isEditingLocked)>
                        <option value="maximize">maximize</option>
                        <option value="minimize">minimize</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">Variaveis</h3>
                        <button type="button" wire:click="addVariable" class="text-xs text-cyan-700" @disabled($this->isEditingLocked)>+ adicionar</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        @foreach ($problem['variables'] as $index => $variable)
                        <div class="grid grid-cols-12 gap-2">
                            <input type="text" wire:model.live="problem.variables.{{ $index }}.name" class="col-span-4 rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($this->isEditingLocked)>
                            <input type="text" wire:model.live="problem.variables.{{ $index }}.meaning" class="col-span-6 rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="significado" @disabled($this->isEditingLocked)>
                            <button type="button" wire:click="removeVariable({{ $index }})" class="col-span-2 rounded-lg border border-zinc-300 text-xs" @disabled($this->isEditingLocked)>X</button>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">Restricoes</h3>
                        <button type="button" wire:click="addConstraint" class="text-xs text-cyan-700" @disabled($this->isEditingLocked)>+ adicionar</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        @foreach ($problem['constraints'] as $index => $constraint)
                        <div class="grid grid-cols-12 gap-2">
                            <div class="col-span-2 rounded-lg bg-zinc-100 px-2 py-1 text-center text-xs dark:bg-zinc-800">#{{ $constraint['id'] }}</div>
                            <input type="text" wire:model.live="problem.constraints.{{ $index }}.expression" class="col-span-8 rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="2x1 + x2 <= 100" @disabled($this->isEditingLocked)>
                            <button type="button" wire:click="removeConstraint({{ $index }})" class="col-span-2 rounded-lg border border-zinc-300 text-xs" @disabled($this->isEditingLocked)>X</button>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">Nao negatividade</h3>
                        <button type="button" wire:click="addNonNegativity" class="text-xs text-cyan-700" @disabled($this->isEditingLocked)>+ adicionar</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        @foreach ($problem['non_negativity'] as $index => $item)
                        <div class="grid grid-cols-12 gap-2">
                            <input type="text" wire:model.live="problem.non_negativity.{{ $index }}" class="col-span-10 rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" @disabled($this->isEditingLocked)>
                            <button type="button" wire:click="removeNonNegativity({{ $index }})" class="col-span-2 rounded-lg border border-zinc-300 text-xs" @disabled($this->isEditingLocked)>X</button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if (!empty($problem['warnings']))
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
                <p class="font-medium">Avisos:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($problem['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (!$problem['academic_mode_allowed'] && !empty($problem['complexity_reason']))
            <div class="mt-4 rounded-xl border border-zinc-300 bg-zinc-50 p-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                Modo academico desabilitado: {{ $problem['complexity_reason'] ?: 'Limites excedidos para simplex visual.' }}
            </div>
            @endif

            <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <h3 class="font-medium text-zinc-900 dark:text-zinc-100">3. Resolver</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Depois de concluir variaveis, restricoes e objetivo, escolha o modo de resolucao.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <flux:button wire:click="solveAcademic" variant="primary" :disabled="!$problem['academic_mode_allowed'] || $loadingSolve || $this->isEditingLocked">
                        @if ($loadingSolve)
                        Processando...
                        @else
                        Resolver com Visualizador (Simplex)
                        @endif
                    </flux:button>
                    <flux:button wire:click="solveIndustrial" variant="outline" :disabled="$loadingSolve || $this->isEditingLocked">
                        @if ($loadingSolve)
                        Processando...
                        @else
                        Resolver com Google OR-Tools
                        @endif
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    @if ($state['activeModal'] === 'academic' && !empty($state['academicResult']))
    <div class="or-modal-overlay fixed inset-0 z-40 flex items-center justify-center bg-black/60 p-4">
        <div class="or-modal-panel w-full max-w-6xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Simplex passo a passo</h3>
                    <p class="text-sm text-zinc-500">Navegue entre as iteracoes e acompanhe as operacoes de linha.</p>
                </div>
                <button wire:click="closeModal" class="rounded-lg border border-zinc-300 px-3 py-1 text-sm">Fechar</button>
            </div>

            @php
            $step = $this->currentStep;
            $columnLabels = data_get($state, 'academicResult.column_labels', []);
            $rowLabels = data_get($state, 'academicResult.row_labels', []);
            $initialTableau = data_get($state, 'academicResult.initial_tableau', []);
            $variableNames = data_get($state, 'academicResult.variable_names', []);
            $slackVariables = array_values(array_filter($variableNames, static fn($name) => str_starts_with((string) $name, 's')));
            @endphp

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Objetivo original</p>
                    <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                        {{ strtoupper((string) data_get($state, 'academicResult.objective_type', data_get($problem, 'objective.type', 'maximize'))) }}
                        {{ data_get($state, 'academicResult.objective_expression', data_get($problem, 'objective.expression', '-')) }}
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Mapeamento de variaveis de folga</p>
                    <p class="mt-1 text-zinc-700 dark:text-zinc-300">
                        @if (empty($slackVariables))
                        Nenhuma variavel de folga.
                        @else
                        {{ implode(', ', $slackVariables) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                <p class="text-sm font-medium">Tableau inicial (antes da iteracao 1)</p>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <th class="px-3 py-2 text-left text-xs uppercase text-zinc-500">Linha</th>
                                @foreach ($columnLabels as $label)
                                <th class="px-3 py-2 text-right text-xs uppercase text-zinc-500">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($initialTableau as $rowIndex => $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-3 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $rowLabels[$rowIndex] ?? ('L'.($rowIndex + 1)) }}</td>
                                @foreach ($row as $value)
                                <td class="px-3 py-2 text-right">{{ $value }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($step)
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Iteracao</p>
                    <p class="mt-1 text-xl font-semibold">{{ $step['step'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Entra / Sai</p>
                    <p class="mt-1 font-semibold">{{ $step['entering_variable'] }} / {{ $step['leaving_variable'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Pivot</p>
                    <p class="mt-1 font-semibold">Linha {{ $step['pivot_row'] }} | Coluna {{ $step['pivot_column'] }}</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-3 py-2 text-left text-xs uppercase text-zinc-500">Linha</th>
                            @foreach ($columnLabels as $label)
                            <th class="px-3 py-2 text-right text-xs uppercase text-zinc-500">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($step['tableau_after'] as $rowIndex => $row)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $rowLabels[$rowIndex] ?? ('L'.($rowIndex + 1)) }}</td>
                            @foreach ($row as $colIndex => $value)
                            @php
                            $isPivotRow = ($rowIndex + 1) === $step['pivot_row'];
                            $isPivotCol = ($colIndex + 1) === $step['pivot_column'];
                            @endphp
                            <td class="or-tableau-cell px-3 py-2 text-right {{ $isPivotRow ? 'bg-cyan-50 dark:bg-cyan-950/30' : '' }} {{ $isPivotCol ? 'ring-1 ring-amber-400' : '' }}">
                                {{ $value }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                <p class="font-medium">Operacoes</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($step['operations'] as $operation)
                    <li>{{ $operation }}</li>
                    @endforeach
                </ul>
                <p class="mt-3 text-zinc-500">{{ $step['explanation'] }}</p>
            </div>
            @endif

            <div class="mt-5 flex items-center justify-between">
                <flux:button wire:click="previousStep" variant="ghost">Voltar passo</flux:button>
                <div class="text-sm text-zinc-500">Passo {{ $currentStepIndex + 1 }} de {{ count($state['academicResult']['steps']) }}</div>
                <flux:button wire:click="nextStep" variant="primary">Avancar passo</flux:button>
            </div>
        </div>
    </div>
    @endif

    @if ($state['activeModal'] === 'industrial' && !empty($state['industrialResult']))
    <div class="or-modal-overlay fixed inset-0 z-40 flex items-center justify-center bg-black/60 p-4">
        <div class="or-modal-panel w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Resultado industrial (OR-Tools)</h3>
                <button wire:click="closeModal" class="rounded-lg border border-zinc-300 px-3 py-1 text-sm">Fechar</button>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Status</p>
                    <p class="mt-1 font-semibold">{{ data_get($state, 'industrialResult.status', 'unknown') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Objetivo</p>
                    <p class="mt-1 font-semibold">{{ data_get($state, 'industrialResult.objective_value', '-') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Tempo (s)</p>
                    <p class="mt-1 font-semibold">{{ data_get($state, 'industrialResult.metrics.solve_time_sec', '-') }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                <p class="font-medium">Variaveis otimas</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    @foreach ((array) data_get($state, 'industrialResult.variables', []) as $name => $value)
                    <div class="rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/60">{{ $name }} = {{ $value }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</section>