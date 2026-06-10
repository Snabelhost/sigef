<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartao de Identificacao - {{ $payload['name'] ?? 'Formando' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .student-card-page {
            display: grid;
            gap: 18px;
            justify-items: center;
        }

        .student-card-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
        }

        .student-card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .student-card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border: 0;
            border-radius: 8px;
            background: #041c4f;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .student-card-button.secondary {
            background: #e2e8f0;
            color: #0f172a;
        }

        @media print {
            body {
                display: block;
                min-height: auto;
                padding: 0;
                background: #fff;
            }

            .student-card-title,
            .student-card-actions {
                display: none;
            }

            .student-card-page {
                display: block;
            }
        }
    </style>
</head>
<body>
    <main class="student-card-page">
        <h1 class="student-card-title">Cartao de Identificacao</h1>

        @include('students.partials.identification-card', ['mode' => 'page'])

        <div class="student-card-actions">
            <button class="student-card-button" type="button" onclick="window.print()">Imprimir Cartao</button>
            <a class="student-card-button secondary" href="{{ url()->previous() }}">Voltar</a>
        </div>
    </main>
</body>
</html>
