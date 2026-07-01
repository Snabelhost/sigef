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
    Route::get('/accesses', [ReportController::class, 'accessLogs'])->name('accesses');
    Route::get('/audit', [ReportController::class, 'auditLogs'])->name('audit');
    // Currículo
    Route::get('/course-maps', [ReportController::class, 'courseMaps'])->name('course-maps');
    Route::get('/course-plans', [ReportController::class, 'coursePlans'])->name('course-plans');
    Route::get('/courses', [ReportController::class, 'courses'])->name('courses');
    Route::get('/subjects', [ReportController::class, 'subjects'])->name('subjects');
    // Gestão Escolar
    Route::get('/trainers', [ReportController::class, 'trainers'])->name('trainers');
    Route::get('/trainer-subjects', [ReportController::class, 'trainerSubjects'])->name('trainer-subjects');
    Route::get('/effectives', [ReportController::class, 'effectives'])->name('effectives');
    Route::get('/students-by-provenance', [ReportController::class, 'studentsByProvenance'])->name('students-by-provenance');
    Route::get('/student-types', [ReportController::class, 'studentsByType'])->name('student-types');
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

Route::middleware(['auth'])->prefix('gestao-formandos')->name('students.')->group(function () {
    Route::get('/{student}/ficha-inscricao', \App\Http\Controllers\StudentSheetPrintController::class)->name('sheet.print');
});

Route::middleware(['auth'])->prefix('dispensas-faltas')->name('student-leaves.')->group(function () {
    Route::get('/{studentLeave}/ficha-dispensa', \App\Http\Controllers\StudentLeaveSheetPrintController::class)->name('sheet.print');
});

/*
|--------------------------------------------------------------------------
| Cartões de Identificação dos Estudantes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('formandos')->name('candidates.')->group(function () {
    Route::get('/{candidate}/ficha-inscricao', \App\Http\Controllers\CandidateSheetPrintController::class)->name('sheet.print');
});

Route::middleware(['auth'])->prefix('formadores')->name('trainers.')->group(function () {
    Route::get('/{trainer}/ficha-professor', \App\Http\Controllers\TrainerSheetPrintController::class)->name('sheet.print');
});

Route::middleware(['auth'])->prefix('efectivos')->name('effectives.')->group(function () {
    Route::get('/{effective}/ficha-efectivo', \App\Http\Controllers\EffectiveSheetPrintController::class)->name('sheet.print');
});

Route::middleware(['auth'])->prefix('cartoes')->name('cartoes.')->group(function () {
    Route::get('/estudante/{student}/preview', \App\Http\Controllers\StudentCardPreviewController::class)->name('preview');
    Route::get('/efectivo/{effective}/preview', \App\Http\Controllers\EffectiveCardPreviewController::class)->name('effectives.preview');
    Route::get('/formador/{trainer}/preview', \App\Http\Controllers\TrainerCardPreviewController::class)->name('trainers.preview');
    Route::get('/estudante/{student}', [\App\Http\Controllers\StudentCardController::class, 'show'])->name('show');
    Route::post('/imprimir-lote', [\App\Http\Controllers\StudentCardController::class, 'printBatch'])->name('batch');
    Route::get('/lista', [\App\Http\Controllers\StudentCardController::class, 'index'])->name('index');
});

Route::middleware(['auth'])
    ->get('/admin/configuracoes/card-templates/{cardTemplate}/preview', \App\Http\Controllers\CardTemplatePreviewController::class)
    ->name('admin.card-templates.preview');

/*
|--------------------------------------------------------------------------
| Storage Files - Servir ficheiros de storage/app/public
|--------------------------------------------------------------------------
| Rota para servir ficheiros quando o Apache não consegue seguir o symlink
| (comum em hosting cPanel). O .htaccess envia /storage/* para cá.
| Usa controller em vez de closure para funcionar com route:cache.
*/
Route::get('/storage/{path}', \App\Http\Controllers\StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.files');
