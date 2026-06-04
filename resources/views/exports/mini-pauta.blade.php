<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Mini Pauta - {{ $disciplina->name ?? 'Disciplina' }} - {{ $turma->name ?? 'Turma' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            background: #fff;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 195mm;
            margin: 0 auto;
        }

        /* ── Logo e cabeçalho ── */
        .container-logo {
            text-align: center;
            margin-bottom: 2mm;
        }

        .container-logo img {
            width: 60px;
            height: auto;
        }

        .conteiner {
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 3mm;
        }

        .conteiner strong {
            font-size: 15px;
        }

        .conteiner p strong {
            font-size: 17px;
            letter-spacing: 1px;
        }

        /* ── Info da disciplina/turma ── */
        .utility-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            margin-bottom: 1mm;
            padding: 1mm 0;
        }

        .utility-row .item {
            display: inline-flex;
            gap: 1mm;
        }

        .utility-row .item label {
            font-weight: bold;
        }

        .utility-row .item .val {
            color: crimson;
            font-weight: 600;
        }

        /* ── Tabela principal ── */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2mm 2mm;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #fff;
            font-weight: bold;
            font-size: 8px;
            color: #000;
        }

        th.utility-header {
            background: #f5f5f5;
            font-size: 9px;
        }

        td.nome {
            text-align: left;
            font-size: 11px;
            max-width: 70mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 2mm;
        }

        td.posto {
            font-size: 10px;
        }

        .col-num {
            width: 8mm;
        }

        .col-posto {
            width: 18mm;
        }

        .col-nome {
            width: 65mm;
        }

        .col-genero {
            width: 8mm;
        }

        .col-nota {
            width: 10mm;
        }

        .col-faltas {
            width: 14mm;
        }

        .col-media {
            width: 13mm;
        }

        .col-obs {
            width: 20mm;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        td.aprovado {
            color: #006400;
            font-weight: bold;
        }

        td.reprovado {
            color: #c00;
            font-weight: bold;
        }

        /* ── Estatística ── */
        .stats-section {
            margin-top: 4mm;
        }

        .stats-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2mm;
        }

        table.stats-table {
            font-size: 8px;
        }

        table.stats-table th {
            background: #f5f5f5;
            font-size: 8px;
            padding: 1mm;
        }

        table.stats-table td {
            padding: 1.5mm 1mm;
            font-weight: 600;
        }

        /* ── Footer ── */
        .footer-container {
            margin-top: 5mm;
            text-align: center;
        }

        .footer-container .main-text {
            font-weight: bold;
            font-style: italic;
            font-size: 10px;
            margin-bottom: 3mm;
        }

        .details-section {
            font-size: 10px;
            line-height: 1.8;
        }

        .details-section .date-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 25mm;
        }

        .coordinator-text {
            margin-top: 10mm;
            font-weight: bold;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Logo --}}
        <div class="container-logo">
            <img src="/images/logo-pna.png" alt="Logotipo">
        </div>

        {{-- Header institucional --}}
        <div class="conteiner">
            {{ strtoupper($instituicao->name ?? 'ESCOLA PRÁTICA DE POLÍCIA') }}<br>
            ÁREA DE INSTRUÇÃO DE ENSINO<br>
            <strong>DEPARTAMENTO DE FORMAÇÃO</strong><br>
            <p><strong>MINI PAUTA PROFESSOR</strong></p>
        </div>

        {{-- Info da disciplina/turma --}}
        <table style="margin-bottom: 2mm;">
            <thead>
                <tr>
                    <th class="utility-header" colspan="2">
                        Disciplina: <span style="color: crimson;">{{ $disciplina->name ?? '-' }}</span>
                    </th>
                    <th class="utility-header" colspan="2" style="color: crimson;">
                        COM. INTER.: <span style="color: #000;">____</span>
                    </th>
                    <th class="utility-header" colspan="2">
                        Turno: ____
                    </th>
                    <th class="utility-header" colspan="3">
                        {{ $turma->name ?? '-' }}
                    </th>
                    <th class="utility-header" colspan="5">
                        ANO LECT. {{ $anoLectivo }}
                    </th>
                </tr>
                <tr>
                    <th colspan="2">TURMA: {{ $turma->name ?? '-' }}</th>
                    <th colspan="2" style="color: rgb(74, 179, 240);">{{ $curso ?? '-' }}</th>
                    <th colspan="10" style="color: rgb(74, 179, 240);">CLASSIFICAÇÃO</th>
                </tr>
            </thead>
        </table>

        {{-- Tabela de notas --}}
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" class="col-num">Nº</th>
                        <th rowspan="2" class="col-posto">Posto</th>
                        <th rowspan="2" class="col-nome">NOME DO ALUNO (A)</th>
                        <th rowspan="2" class="col-genero">Gênero</th>
                        <th colspan="3">IIº FASE</th>
                        <th rowspan="2" class="col-faltas">Total<br>de Faltas</th>
                        <th colspan="2">Avaliação<br>Final</th>
                        <th rowspan="2" class="col-obs">Observações</th>
                    </tr>
                    <tr>
                        <th class="col-nota">P1</th>
                        <th class="col-nota">P2</th>
                        <th class="col-nota">P3</th>
                        <th class="col-media">M. FINAL</th>
                        <th class="col-nota">FALTAS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alunos as $index => $aluno)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="posto">Formando</td>
                        <td class="nome">{{ $aluno['nome'] }}</td>
                        <td>{{ strtoupper(substr($aluno['genero'] ?? '-', 0, 1)) }}</td>
                        <td>{{ $aluno['npp1'] ?? '' }}</td>
                        <td>{{ $aluno['npp2'] ?? '' }}</td>
                        <td>{{ $aluno['npp3'] ?? '' }}</td>
                        <td>{{ $aluno['faltas'] > 0 ? $aluno['faltas'] : '' }}</td>
                        <td class="{{ $aluno['media_final'] !== '-' && floatval($aluno['media_final']) >= 10 ? 'aprovado' : 'reprovado' }}">
                            <strong>{{ $aluno['media_final'] }}</strong>
                        </td>
                        <td>{{ $aluno['faltas'] > 0 ? $aluno['faltas'] : '' }}</td>
                        <td></td>
                    </tr>
                    @empty
                    @for($i = 1; $i <= 50; $i++)
                        <tr>
                        <td>{{ $i }}</td>
                        <td class="posto">Formando</td>
                        <td class="nome"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        </tr>
                        @endfor
                        @endforelse
                </tbody>
            </table>
        </div>

        {{-- Estatística --}}
        <div class="stats-section">
            <div class="stats-title">ESTATÍSTICA</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th colspan="2" rowspan="3" style="background: #f5f5f5;">MATRICULADOS</th>
                        <th colspan="8" style="background: #f5f5f5;">IIº FASE</th>
                        <th colspan="2" rowspan="4" style="background: #f5f5f5; vertical-align: middle;">ASSINATURA DO<br>PROFESSOR</th>
                    </tr>
                    <tr>
                        <th colspan="2">DESIST</th>
                        <th colspan="2">AVAL</th>
                        <th colspan="2">APTO</th>
                        <th colspan="2">N/APTO</th>
                    </tr>
                    <tr>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                        <th>MF</th>
                        <th>F</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="background: #f5f5f5; font-weight: bold;">{{ $stats['total'] ?? '-' }}</td>
                        <td style="background: #f5f5f5; font-weight: bold;">{{ $stats['feminino'] ?? '-' }}</td>
                        <td>{{ $stats['desistentes'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['avaliados'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['aptos'] ?? 0 }}</td>
                        <td></td>
                        <td>{{ $stats['nao_aptos'] ?? 0 }}</td>
                        <td></td>
                        <td rowspan="2"></td>
                        <td rowspan="2"></td>
                    </tr>
                    <tr>
                        <td style="background: #f5f5f5;">%</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['total'] > 0 ? number_format(($stats['avaliados'] / $stats['total']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['avaliados'] > 0 ? number_format(($stats['aptos'] / $stats['avaliados']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                        <td style="background: #f5f5f5;">{{ $stats['avaliados'] > 0 ? number_format(($stats['nao_aptos'] / $stats['avaliados']) * 100, 0) . '%' : '' }}</td>
                        <td style="background: #f5f5f5;"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer-container">
            <p class="main-text">
                FORMAÇÃO, PROFISSIONALISMO E APERFEIÇOAMENTO
            </p>
            <div class="details-section">
                <p>
                    DEPARTAMENTO DE FORMAÇÃO/ÁREA DE INSTRUÇÃO E ENSINO/{{ strtoupper(substr($instituicao->name ?? 'EPP', 0, 3)) }}/PNA, em {{ $instituicao->city ?? 'Luanda' }}, aos
                    <span class="date-line">________</span> /
                    <span class="date-line">________</span> / {{ date('Y') }}.
                </p>
                <p class="coordinator-text">
                    O COORDENADOR/CHEFE DE CÁTEDRA
                    <br><span class="date-line" style="margin-top: 8mm; display: inline-block;">________________________</span>
                </p>
            </div>
        </div>
    </div>
</body>

</html>