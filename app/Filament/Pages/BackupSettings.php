<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DBSchema;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BackupSettings extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-circle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationLabel = 'Backup da BD';
    protected static ?string $title = 'Gestão de Backup';
    protected static ?string $slug = 'backup-settings';

    protected string $view = 'filament.pages.backup-settings';

    // Schedule config
    public bool $backup_enabled = false;
    public string $backup_time = '02:00';
    public string $backup_frequency = 'daily';
    public int $backup_retention_days = 30;

    // Import state
    public $importFile = null;
    public bool $showImportPreview = false;
    public array $importPreview = [];
    public bool $updateExisting = false;

    public function mount(): void
    {
        $this->backup_enabled = (bool) SystemSetting::get('backup_enabled', false);
        $this->backup_time = SystemSetting::get('backup_time', '02:00');
        $this->backup_frequency = SystemSetting::get('backup_frequency', 'daily');
        $this->backup_retention_days = (int) SystemSetting::get('backup_retention_days', 30);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Programar Backup Automático')
                    ->description('Configure quando o backup da base de dados deve ser realizado automaticamente')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Toggle::make('backup_enabled')
                                    ->label('Backup Automático Activo')
                                    ->helperText('Activar/desactivar backup automático')
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('backup_frequency')
                                    ->label('Frequência')
                                    ->options([
                                        'daily' => 'Diário',
                                        'weekly' => 'Semanal (Domingo)',
                                        'monthly' => 'Mensal (Dia 1)',
                                    ])
                                    ->default('daily')
                                    ->native(false)
                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('backup_enabled'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('backup_time')
                                    ->label('Hora do Backup')
                                    ->type('time')
                                    ->default('02:00')
                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('backup_enabled'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('backup_retention_days')
                                    ->label('Reter (dias)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(7)
                                    ->maxValue(365)
                                    ->suffix('dias')
                                    ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get) => $get('backup_enabled'))
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    public function saveSchedule(): void
    {
        SystemSetting::set('backup_enabled', $this->backup_enabled ? '1' : '0', 'backup');
        SystemSetting::set('backup_time', $this->backup_time, 'backup');
        SystemSetting::set('backup_frequency', $this->backup_frequency, 'backup');
        SystemSetting::set('backup_retention_days', (string) $this->backup_retention_days, 'backup');

        Notification::make()
            ->title('Configurações de backup guardadas!')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();
    }

    public function createBackup(): void
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "sigef_backup_{$timestamp}.sql";
            $backupDir = storage_path('app/backups');

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filepath = "{$backupDir}/{$filename}";

            // Get database config
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Try mysqldump first
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --skip-lock-tables %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                $this->createPhpBackup($filepath, $database);
            }

            $size = filesize($filepath);
            $sizeFormatted = $this->formatBytes($size);

            Notification::make()
                ->title('Backup criado com sucesso!')
                ->body("Ficheiro: {$filename} ({$sizeFormatted})")
                ->success()
                ->icon('heroicon-o-check-circle')
                ->duration(8000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao criar backup')
                ->body($e->getMessage())
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->persistent()
                ->send();
        }
    }

    protected function createPhpBackup(string $filepath, string $database): void
    {
        $tables = DB::select('SHOW TABLES');
        $key = "Tables_in_{$database}";

        $sql = "-- SIGEF Database Backup\n";
        $sql .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$database}\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
                $columnList = implode('`, `', $columns);

                foreach ($rows->chunk(100) as $chunk) {
                    $sql .= "INSERT INTO `{$tableName}` (`{$columnList}`) VALUES\n";
                    $values = [];
                    foreach ($chunk as $row) {
                        $rowValues = [];
                        foreach ((array) $row as $value) {
                            $rowValues[] = $value === null ? 'NULL' : "'" . addslashes((string) $value) . "'";
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $sql);
    }

    public function previewImport(): void
    {
        if (!$this->importFile) {
            Notification::make()->title('Selecione um ficheiro SQL')->warning()->send();
            return;
        }

        try {
            $path = $this->importFile->getRealPath();
            $content = file_get_contents($path);

            $preview = [];
            $totalRecords = 0;
            $existingRecords = 0;
            $newRecords = 0;

            preg_match_all('/INSERT INTO `([^`]+)`/i', $content, $matches);
            $tables = array_unique($matches[1] ?? []);

            foreach ($tables as $tableName) {
                if (!DBSchema::hasTable($tableName)) {
                    $preview[] = [
                        'table' => $tableName,
                        'total' => 0,
                        'new' => 0,
                        'existing' => 0,
                        'status' => 'skip',
                    ];
                    continue;
                }

                // Match both formats:
                // 1) INSERT INTO `table` (`col1`, `col2`) VALUES ...;
                // 2) INSERT INTO `table` VALUES ...;  (mysqldump format)
                $pattern = '/INSERT INTO `' . preg_quote($tableName, '/') . '`\s*(?:\([^)]*\)\s*)?VALUES\s*(.*?);/is';
                preg_match_all($pattern, $content, $insertMatches);

                $rowCount = 0;
                foreach ($insertMatches[1] ?? [] as $valuesBlock) {
                    $rowCount += substr_count($valuesBlock, '),(') + 1;
                }

                $currentCount = DB::table($tableName)->count();
                $tableNew = max(0, $rowCount - $currentCount);
                $tableExisting = min($rowCount, $currentCount);

                $status = $rowCount === 0 ? 'empty' : ($tableNew === 0 ? 'all_exist' : 'ok');

                $preview[] = [
                    'table' => $tableName,
                    'total' => $rowCount,
                    'new' => $tableNew,
                    'existing' => $tableExisting,
                    'status' => $status,
                ];

                $totalRecords += $rowCount;
                $existingRecords += $tableExisting;
                $newRecords += $tableNew;
            }

            $this->importPreview = [
                'tables' => $preview,
                'total_records' => $totalRecords,
                'new_records' => $newRecords,
                'existing_records' => $existingRecords,
                'filename' => $this->importFile->getClientOriginalName(),
                'filesize' => $this->formatBytes($this->importFile->getSize()),
            ];

            if ($newRecords === 0 && $totalRecords > 0) {
                Notification::make()
                    ->title('Todos os registos já existem na base de dados')
                    ->body("O ficheiro contém {$totalRecords} registos. Active 'Actualizar existentes' para substituir os dados.")
                    ->warning()->persistent()->send();
            }

            $this->showImportPreview = true;
        } catch (\Exception $e) {
            Notification::make()->title('Erro ao analisar ficheiro')->body($e->getMessage())->danger()->persistent()->send();
        }
    }

    public function executeImport(): void
    {
        if (!$this->importFile) return;

        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $imported = 0;
            $skipped = 0;
            $updated = 0;
            $errors = [];

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Match both formats:
            // 1) INSERT INTO `table` (`col1`, `col2`) VALUES ...;
            // 2) INSERT INTO `table` VALUES ...;  (mysqldump format)
            preg_match_all('/INSERT INTO `([^`]+)`\s*(?:\((`[^)]*`)\)\s*)?VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $tableName = $match[1];
                $columnsPart = $match[2] ?? '';
                $valuesBlock = $match[3];

                if (!DBSchema::hasTable($tableName)) continue;

                $existingColumns = DBSchema::getColumnListing($tableName);

                // Determine columns: from SQL or from DB schema
                if (!empty($columnsPart)) {
                    $columns = array_map('trim', explode('`,`', trim($columnsPart, '` ')));
                } else {
                    // mysqldump format: no column list, use DB schema columns
                    $columns = $existingColumns;
                }

                $rows = $this->parseValueRows($valuesBlock);

                foreach ($rows as $rowValues) {
                    $data = [];
                    foreach ($columns as $i => $col) {
                        if (!in_array($col, $existingColumns)) continue;
                        $value = $rowValues[$i] ?? null;
                        $data[$col] = ($value === 'NULL') ? null : stripslashes(trim($value, "'"));
                    }

                    if (empty($data)) continue;

                    try {
                        if (isset($data['id']) && DB::table($tableName)->where('id', $data['id'])->exists()) {
                            if ($this->updateExisting) {
                                $id = $data['id'];
                                unset($data['id']);
                                DB::table($tableName)->where('id', $id)->update($data);
                                $updated++;
                            } else {
                                $skipped++;
                            }
                            continue;
                        }
                        DB::table($tableName)->insert($data);
                        $imported++;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                            $skipped++;
                        } else {
                            $errors[] = "{$tableName}: " . substr($e->getMessage(), 0, 100);
                        }
                    }
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->showImportPreview = false;
            $this->importPreview = [];
            $this->importFile = null;

            $msg = "Importados: {$imported} | Atualizados: {$updated} | Ignorados: {$skipped}";
            if (!empty($errors)) $msg .= " | Erros: " . count($errors);

            Notification::make()->title('Importação concluída!')->body($msg)->success()->duration(10000)->send();

            if (!empty($errors)) {
                Notification::make()->title('Erros durante importação')
                    ->body(implode("\n", array_slice($errors, 0, 5)))->warning()->persistent()->send();
            }
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Notification::make()->title('Erro durante importação')->body($e->getMessage())->danger()->persistent()->send();
        }
    }

    protected function parseValueRows(string $valuesBlock): array
    {
        $rows = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = 0; $i < strlen($valuesBlock); $i++) {
            $char = $valuesBlock[$i];
            if ($escape) {
                $current .= $char;
                $escape = false;
                continue;
            }
            if ($char === '\\') {
                $current .= $char;
                $escape = true;
                continue;
            }
            if ($char === "'" && !$escape) {
                $inString = !$inString;
                $current .= $char;
                continue;
            }
            if (!$inString) {
                if ($char === '(') {
                    $depth++;
                    if ($depth === 1) {
                        $current = '';
                        continue;
                    }
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $rows[] = $this->parseSingleRow($current);
                        $current = '';
                        continue;
                    }
                }
            }
            $current .= $char;
        }
        return $rows;
    }

    protected function parseSingleRow(string $row): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $escape = false;

        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];
            if ($escape) {
                $current .= $char;
                $escape = false;
                continue;
            }
            if ($char === '\\') {
                $current .= $char;
                $escape = true;
                continue;
            }
            if ($char === "'" && !$escape) {
                $inString = !$inString;
                $current .= $char;
                continue;
            }
            if ($char === ',' && !$inString) {
                $values[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $char;
        }
        $values[] = trim($current);
        return $values;
    }

    public function cancelImport(): void
    {
        $this->showImportPreview = false;
        $this->importPreview = [];
        $this->importFile = null;
        Notification::make()->title('Importação cancelada')->info()->send();
    }

    public function getBackupsList(): array
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) return [];

        $files = glob("{$backupDir}/*.sql");
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
            ];
        }

        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return $backups;
    }

    public function downloadBackup(string $filename)
    {
        $path = storage_path("app/backups/{$filename}");
        if (!file_exists($path)) {
            Notification::make()->title('Ficheiro não encontrado')->danger()->send();
            return;
        }

        return response()->download($path);
    }

    public function deleteBackup(string $filename): void
    {
        $path = storage_path("app/backups/{$filename}");
        if (file_exists($path)) {
            unlink($path);
            Notification::make()->title('Backup eliminado')->body($filename)->success()->send();
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
