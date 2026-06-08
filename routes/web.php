<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Auth\UnifiedLoginController;

/*
|--------------------------------------------------------------------------
| Página Inicial - Mostra o formulário de login
|--------------------------------------------------------------------------
*/

Route::get('/', [UnifiedLoginController::class, 'showLoginForm'])->name('home');

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação Unificada
|--------------------------------------------------------------------------
| Todos os utilizadores usam a mesma rota de login.
| Após autenticação, são redirecionados para o painel correto baseado no role.
*/
Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');

Route::post('/login', [UnifiedLoginController::class, 'login']);

Route::get('/select-panel', [UnifiedLoginController::class, 'showPanelSelection'])
    ->middleware('auth')
    ->name('select-panel');

Route::post('/logout', [UnifiedLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| PDF Reports (protected by auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    // Gestão de Acesso
    Route::get('/users', [ReportController::class, 'users'])->name('users');
    // Currículo
    Route::get('/course-maps', [ReportController::class, 'courseMaps'])->name('course-maps');
    Route::get('/course-plans', [ReportController::class, 'coursePlans'])->name('course-plans');
    Route::get('/courses', [ReportController::class, 'courses'])->name('courses');
    Route::get('/subjects', [ReportController::class, 'subjects'])->name('subjects');
    // Gestão Escolar
    Route::get('/trainers', [ReportController::class, 'trainers'])->name('trainers');
    Route::get('/cadetes', [ReportController::class, 'cadetes'])->name('cadetes');
    Route::get('/alistados', [ReportController::class, 'alistados'])->name('alistados');
    Route::get('/enrollments', [ReportController::class, 'enrollments'])->name('enrollments');
    Route::get('/equipment', [ReportController::class, 'equipment'])->name('equipment');
    Route::get('/transfers', [ReportController::class, 'transfers'])->name('transfers');
    Route::get('/leaves', [ReportController::class, 'leaves'])->name('leaves');
    // Avaliação
    Route::get('/evaluations', [ReportController::class, 'evaluations'])->name('evaluations');
    Route::get('/mini-pauta', [ReportController::class, 'miniPauta'])->name('mini-pauta');
    Route::get('/pauta-geral', [ReportController::class, 'pautaGeral'])->name('pauta-geral');
    Route::get('/certificados', [ReportController::class, 'certificados'])->name('certificados');
    Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
    // Instituições & Documentos
    Route::get('/institutions', [ReportController::class, 'institutions'])->name('institutions');
    Route::get('/documents', [ReportController::class, 'documents'])->name('documents');
});

/*
|--------------------------------------------------------------------------
| Pautas - Impressão
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('pautas')->name('pauta.')->group(function () {
    Route::get('/mini-pauta/print', [\App\Http\Controllers\PautaController::class, 'miniPautaPrint'])->name('mini-pauta.print');
    Route::get('/pauta-geral/print', [\App\Http\Controllers\PautaController::class, 'pautaGeralPrint'])->name('pauta-geral.print');
});

/*
|--------------------------------------------------------------------------
| Certificados
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('certificados')->name('certificados.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CertificadoController::class, 'gerar'])->name('gerar');
    Route::get('/individual/{student}', [\App\Http\Controllers\CertificadoController::class, 'individual'])->name('individual');
    Route::get('/bulk', [\App\Http\Controllers\CertificadoController::class, 'bulk'])->name('bulk');
});

/*
|--------------------------------------------------------------------------
| Ficha de Inscrição do Aluno
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/student/{student}/print-ficha', [\App\Http\Controllers\StudentController::class, 'printFicha'])->name('student.print-ficha');
});

/*
|--------------------------------------------------------------------------
| Cartões de Identificação dos Estudantes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('formadores')->name('trainers.')->group(function () {
    Route::get('/{trainer}/ficha-professor', \App\Http\Controllers\TrainerSheetPrintController::class)->name('sheet.print');
});

Route::middleware(['auth'])->prefix('cartoes')->name('cartoes.')->group(function () {
    Route::get('/estudante/{student}', [\App\Http\Controllers\StudentCardController::class, 'show'])->name('show');
    Route::post('/imprimir-lote', [\App\Http\Controllers\StudentCardController::class, 'printBatch'])->name('batch');
    Route::get('/lista', [\App\Http\Controllers\StudentCardController::class, 'index'])->name('index');
});

/*
|--------------------------------------------------------------------------
| TEMPORÁRIO - Deploy Setup (REMOVER APÓS USO!)
|--------------------------------------------------------------------------
*/
Route::get('/deploy-setup/{key}', function ($key) {
    if ($key !== 'sigef2026deploy') {
        abort(404);
    }

    $output = [];

    // 1. Clear caches
    \Artisan::call('optimize:clear');
    $output[] = '✅ optimize:clear: ' . \Artisan::output();

    // 2. Rebuild caches
    \Artisan::call('optimize');
    $output[] = '✅ optimize: ' . \Artisan::output();

    // 3. Filament optimize
    \Artisan::call('filament:optimize');
    $output[] = '✅ filament:optimize: ' . \Artisan::output();

    // 4. Icons cache
    \Artisan::call('icons:cache');
    $output[] = '✅ icons:cache: ' . \Artisan::output();

    // 5. Storage link
    try {
        \Artisan::call('storage:link');
        $output[] = '✅ storage:link: ' . \Artisan::output();
    } catch (\Exception $e) {
        $output[] = '⚠️ storage:link: ' . $e->getMessage();
    }

    // 6. Migrate
    \Artisan::call('migrate', ['--force' => true]);
    $output[] = '✅ migrate: ' . \Artisan::output();

    return '<pre style="font-family:monospace;padding:20px;background:#1a1a2e;color:#0f0;font-size:14px;">'
        . '<h2 style="color:#fff;">🚀 SIGEF Deploy Setup</h2>'
        . implode("\n", $output)
        . "\n\n<span style='color:red;font-weight:bold;'>⚠️ REMOVA ESTA ROTA APÓS O DEPLOY!</span>"
        . '</pre>';
});
