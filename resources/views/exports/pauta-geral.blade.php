<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Pauta Geral - {{ $turma->name ?? 'Turma' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 18mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            background: #fff;
            color: #000;
        }

        .container {
            width: 100%;
            padding: 10mm 18mm;
        }

        /* ── Logo e cabeçalho centralizado ── */
        .container-logo {
            text-align: center;
            margin-bottom: 1mm;
        }

        .container-logo img {
            width: 40px;
            height: auto;
        }

        .conteiner {
            text-align: center;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 3mm;
        }

        .conteiner strong {
            font-size: 14px;
        }

        .conteiner p strong {
            font-size: 16px;
            letter-spacing: 1px;
        }

        /* ── Info da turma ── */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 11px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1mm;
        }

        .info-item label {
            font-weight: bold;
        }

        .info-item .value {
            border-bottom: 1px solid #000;
            min-width: 20mm;
            padding: 0 1mm;
        }

        /* ── Tabela ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #fff;
            font-weight: bold;
            font-size: 9px;
        }

        .th-disciplina {
            background: #f5f5f5;
            writing-mode: vertical-lr;
            text-orientation: mixed;
            transform: rotate(180deg);
            height: 25mm;
            font-size: 8px;
            padding: 1px;
            white-space: nowrap;
            width: 8mm;
        }

        td.nome {
            text-align: left;
            font-size: 9px;
            max-width: 50mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 2px;
        }

        .col-num {
            width: 8mm;
        }

        .col-nome {
            width: 30mm;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .aprovado {
            color: green;
            font-weight: bold;
        }

        .reprovado {
            color: red;
            font-weight: bold;
        }

        .pendente {
            color: #666;
            font-weight: bold;
        }

        /* ── Rodapé ── */
        .footer {
            margin-top: 5mm;
            font-size: 10px;
        }

        .assinatura-row {
            display: flex;
            justify-content: space-between;
            margin-top: 5mm;
            padding: 0 15mm;
        }

        .assinatura {
            text-align: center;
            width: 30%;
        }

        .assinatura-linha {
            border-top: 1px solid #000;
            margin-top: 10mm;
            padding-top: 1mm;
            font-size: 10px;
        }

        .slogan {
            text-align: center;
            font-style: italic;
            font-size: 10px;
            margin: 3mm 0;
        }

        .footer-text {
            text-align: center;
            font-size: 10px;
            margin-bottom: 3mm;
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
        {{-- Logo centralizado --}}
        <div class="container-logo">
            <img src="/images/logo-pna.png" alt="Logotipo">
        </div>

        {{-- Header institucional centralizado --}}
        <div class="conteiner">
            {{ strtoupper($instituicao->name ?? 'ESCOLA PRÁTICA DE POLÍCIA') }}<br>
            ÁREA DE INSTRUÇÃO DE ENSINO<br>
            <strong>DEPARTAMENTO DE FORMAÇÃO</strong><br>
            <p><strong>PAUTA GERAL DE CLASSIFICAÇÃO</strong></p>
        </div>

        {{-- Info --}}
        <div class="info-row">
            <div class="info-item">
                <label>Turma:</label>
                <span class="value">{{ $turma->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Curso:</label>
                <span class="value">{{ $curso ?? $turma->courseMap?->course?->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Ano Lect.:</label>
                <span class="value">{{ $anoLectivo ?? $turma->academicYear?->year ?? date('Y') }}</span>
            </div>
        </div>

        {{-- Tabela de Notas --}}
        <table>
            <thead>
                <tr>
                    <th class="col-num">Nº</th>
                    <th class="col-nome">NOME DO FORMANDO</th>
                    @foreach($disciplinas as $disciplina)
                    <th class="th-disciplina">{{ strtoupper($disciplina->name) }}</th>
                    @endforeach
                    <th class="th-disciplina" style="font-weight: bold;">MÉDIA GERAL</th>
                    <th class="th-disciplina">RESULTADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alunos as $index => $aluno)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nome">{{ $aluno['nome'] }}</td>
                    @foreach($disciplinas as $disciplina)
                    <td>{{ $aluno['medias'][$disciplina->id] ?? '-' }}</td>
                    @endforeach
                    <td style="font-weight: bold;">{{ $aluno['media_geral'] ?? '-' }}</td>
                    <td class="{{ ($aluno['resultado'] ?? '') == 'Aprovado' ? 'aprovado' : (($aluno['resultado'] ?? '') == 'Pendente' ? 'pendente' : 'reprovado') }}">
                        {{ strtoupper($aluno['resultado'] ?? '-') }}
                    </td>
                </tr>
                @empty
                @for($i = 1; $i <= 50; $i++)
                    <tr>
                    <td>{{ $i }}</td>
                    <td class="nome"></td>
                    @foreach($disciplinas as $disciplina)
                    <td></td>
                    @endforeach
                    <td></td>
                    <td></td>
                    </tr>
                    @endfor
                    @endforelse
            </tbody>
        </table>

        {{-- Footer --}}
        <div class="footer">
            <div class="slogan">"FORMAÇÃO, PROFISSIONALISMO E APERFEIÇOAMENTO"</div>

            <div class="footer-text">
                DEPARTAMENTO DE FORMAÇÃO/ÁREA DE INSTRUÇÃO E ENSINO/{{ strtoupper(substr($instituicao->name ?? 'EPP', 0, 3)) }}/PNA, em {{ $instituicao->city ?? 'Luanda' }}, aos ___/___/{{ date('Y') }}.
            </div>

            <div class="assinatura-row">
                <div class="assinatura">
                    <div class="assinatura-linha">O COORDENADOR PEDAGÓGICO</div>
                </div>
                <div class="assinatura">
                    <div class="assinatura-linha">O DIRECTOR ADJ. P/ INSTRUÇÃO</div>
                </div>
                <div class="assinatura">
                    <div class="assinatura-linha">O DIRECTOR DA ESCOLA</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>