<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $isAdmin ? 'Acompanhamento dos Professores' : 'Minhas Turmas e Disciplinas' }}
        </x-slot>

        <x-slot name="description">
            {{ $isAdmin ? 'Visão geral das atribuições e autorizações do corpo docente.' : 'Resumo das turmas e disciplinas associadas ao seu cadastro de formador.' }}
        </x-slot>

        <style>
            .sigef-professor-panel-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .sigef-professor-panel-card {
                border: 1px solid #d8e1f0;
                border-radius: 8px;
                overflow: hidden;
                background: #ffffff;
            }

            .sigef-professor-panel-card header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 16px;
                background: #041c4f;
                color: #ffffff;
                font-weight: 700;
            }

            .sigef-professor-panel-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .sigef-professor-panel-table th,
            .sigef-professor-panel-table td {
                padding: 11px 12px;
                border-bottom: 1px solid #e5ebf5;
                text-align: left;
                vertical-align: top;
            }

            .sigef-professor-panel-card .sigef-professor-panel-table thead th {
                color: #061b49 !important;
                font-size: 11px;
                font-weight: 800;
                text-transform: uppercase;
                background: #e9eef8 !important;
                border-bottom: 1px solid #cbd6ea;
                letter-spacing: 0;
            }

            .sigef-professor-empty {
                padding: 24px 16px;
                color: #64748b;
                text-align: center;
            }

            @media (max-width: 1100px) {
                .sigef-professor-panel-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        @if (! $isAdmin && ! $trainer)
            <div class="sigef-professor-empty">
                Este utilizador ainda não está vinculado a um cadastro de formador. Use o mesmo e-mail do utilizador no formulário do formador.
            </div>
        @else
            <div class="sigef-professor-panel-grid">
                <section class="sigef-professor-panel-card">
                    <header>
                        <span>{{ $isAdmin ? 'Atribuições recentes' : 'Minhas atribuições' }}</span>
                        <span>{{ count($assignments) }}</span>
                    </header>

                    @if ($assignments)
                        <table class="sigef-professor-panel-table">
                            <thead>
                                <tr>
                                    @if ($isAdmin)
                                        <th>Professor</th>
                                    @endif
                                    <th>Turma</th>
                                    <th>Curso</th>
                                    <th>Disciplina</th>
                                    <th>Ano Lectivo</th>
                                    <th>Horário</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignments as $assignment)
                                    <tr>
                                        @if ($isAdmin)
                                            <td>{{ $assignment['trainer'] }}</td>
                                        @endif
                                        <td>{{ $assignment['class'] }}</td>
                                        <td>{{ $assignment['course'] }}</td>
                                        <td>{{ $assignment['subject'] }}</td>
                                        <td>{{ $assignment['academic_year'] }}</td>
                                        <td>{{ $assignment['schedule'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="sigef-professor-empty">Nenhuma turma atribuída.</div>
                    @endif
                </section>

                <section class="sigef-professor-panel-card">
                    <header>
                        <span>{{ $isAdmin ? 'Autorizações recentes' : 'Minhas autorizações' }}</span>
                        <span>{{ count($authorizations) }}</span>
                    </header>

                    @if ($authorizations)
                        <table class="sigef-professor-panel-table">
                            <thead>
                                <tr>
                                    @if ($isAdmin)
                                        <th>Professor</th>
                                    @endif
                                    <th>Instituição</th>
                                    <th>Curso</th>
                                    <th>Disciplina</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($authorizations as $authorization)
                                    <tr>
                                        @if ($isAdmin)
                                            <td>{{ $authorization['trainer'] }}</td>
                                        @endif
                                        <td>{{ $authorization['institution'] }}</td>
                                        <td>{{ $authorization['course'] }}</td>
                                        <td>{{ $authorization['subject'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="sigef-professor-empty">Nenhuma autorização registada.</div>
                    @endif
                </section>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
