<?php

namespace App\Support;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Permission\PermissionRegistrar;

class AccessPermissionCatalog
{
    protected const RESOURCE_ACTIONS = [
        'ViewAny',
        'View',
        'Create',
        'Update',
        'Delete',
        'DeleteAny',
        'Restore',
        'RestoreAny',
        'ForceDelete',
        'ForceDeleteAny',
        'Replicate',
        'Reorder',
    ];

    protected const PANEL_PERMISSIONS = [
        'AccessPanel:Admin',
        'AccessPanel:Escola',
        'AccessPanel:Professores',
    ];

    protected const REPORTS = [
        'Users',
        'Accesses',
        'Audit',
        'CourseMaps',
        'CoursePlans',
        'Courses',
        'Subjects',
        'Trainers',
        'TrainerSubjects',
        'Effectives',
        'StudentsByProvenance',
        'StudentTypes',
        'Alistados',
        'Enrollments',
        'Equipment',
        'Transfers',
        'Leaves',
        'Evaluations',
        'MiniPauta',
        'PautaGeral',
        'Certificados',
        'Attendance',
        'Institutions',
        'Documents',
    ];

    protected const DASHBOARD_ACTIONS = [
        'OpenFormandosChart',
        'OpenAlistadosChart',
        'OpenRecrutasInstruendosChart',
        'OpenEmFormacaoConcluidosChart',
        'OpenFormadoresChart',
        'OpenCursosAnoLectivoChart',
        'OpenDisciplinasCursoChart',
        'OpenInstituicoesEnsinoChart',
    ];

    protected const STANDARD_FILAMENT_ACTIONS = [
        'create',
        'edit',
        'view',
        'delete',
        'deleteAny',
        'restore',
        'restoreAny',
        'forceDelete',
        'forceDeleteAny',
        'replicate',
        'reorder',
    ];

    protected static ?array $permissionCache = null;

    public static function sync(?string $guardName = null): int
    {
        $guardName ??= Utils::getFilamentAuthGuard() ?: (string) config('auth.defaults.guard', 'web');
        $permissionModel = Utils::getPermissionModel();
        $created = 0;

        foreach (static::permissions() as $permission) {
            $record = $permissionModel::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        }

        if ($created > 0) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $created;
    }

    public static function permissions(): array
    {
        static::$permissionCache ??= collect()
            ->merge(static::PANEL_PERMISSIONS)
            ->merge(static::resourcePermissions())
            ->merge(static::pagePermissions())
            ->merge(static::widgetPermissions())
            ->merge(static::reportPermissions())
            ->merge(static::dashboardActionPermissions())
            ->merge(static::customActionPermissions())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return static::$permissionCache;
    }

    protected static function resourcePermissions(): array
    {
        return collect(static::resourceSubjects())
            ->flatMap(fn (string $subject): array => array_map(
                fn (string $action): string => "{$action}:{$subject}",
                static::RESOURCE_ACTIONS,
            ))
            ->all();
    }

    protected static function pagePermissions(): array
    {
        return collect(static::pageSubjects())
            ->map(fn (string $subject): string => "View:{$subject}")
            ->all();
    }

    protected static function widgetPermissions(): array
    {
        return collect(static::widgetSubjects())
            ->map(fn (string $subject): string => "View:{$subject}")
            ->all();
    }

    protected static function reportPermissions(): array
    {
        return collect(static::REPORTS)
            ->map(fn (string $report): string => "Report:{$report}")
            ->all();
    }

    protected static function dashboardActionPermissions(): array
    {
        return collect(static::DASHBOARD_ACTIONS)
            ->map(fn (string $action): string => "Action:Dashboard.{$action}")
            ->all();
    }

    protected static function customActionPermissions(): array
    {
        $permissions = [];
        $actionPattern = '/(?:Action|BulkAction)::make\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';
        $wireClickPattern = '/wire:click(?:\.prevent|\.stop|\.debounce|\.lazy|\.defer)*=["\']([a-zA-Z_][a-zA-Z0-9_]*)/';

        foreach (static::files([app_path('Filament'), resource_path('views/filament')]) as $file) {
            $content = @file_get_contents($file);

            if ($content === false || $content === '') {
                continue;
            }

            $subject = static::subjectFromPath($file);

            if ($subject === null) {
                continue;
            }

            preg_match_all($actionPattern, $content, $actionMatches);
            preg_match_all($wireClickPattern, $content, $wireClickMatches);

            $actions = collect($actionMatches[1] ?? [])
                ->merge($wireClickMatches[1] ?? [])
                ->map(fn (string $action): string => trim($action))
                ->reject(fn (string $action): bool => $action === '' || in_array($action, static::STANDARD_FILAMENT_ACTIONS, true))
                ->map(fn (string $action): string => Str::studly($action))
                ->unique();

            foreach ($actions as $action) {
                $permissions[] = "Action:{$subject}.{$action}";
            }
        }

        return $permissions;
    }

    protected static function resourceSubjects(): array
    {
        return collect(static::files([app_path('Filament')]))
            ->filter(fn (string $file): bool => str_ends_with(basename($file), 'Resource.php'))
            ->reject(fn (string $file): bool => str_contains($file, DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR))
            ->reject(fn (string $file): bool => str_contains($file, DIRECTORY_SEPARATOR . 'RelationManagers' . DIRECTORY_SEPARATOR))
            ->reject(fn (string $file): bool => str_contains($file, DIRECTORY_SEPARATOR . 'Concerns' . DIRECTORY_SEPARATOR))
            ->map(fn (string $file): string => Str::of(pathinfo($file, PATHINFO_FILENAME))->replaceEnd('Resource', '')->studly()->toString())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function pageSubjects(): array
    {
        return collect(static::files([
            app_path('Filament/Pages'),
            app_path('Filament/Escola/Pages'),
            app_path('Filament/Professores/Pages'),
        ]))
            ->filter(fn (string $file): bool => str_ends_with($file, '.php'))
            ->map(fn (string $file): string => Str::of(pathinfo($file, PATHINFO_FILENAME))->studly()->toString())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function widgetSubjects(): array
    {
        return collect(static::files([
            app_path('Filament/Widgets'),
            app_path('Filament/Escola/Widgets'),
            app_path('Filament/Professores/Widgets'),
        ]))
            ->filter(fn (string $file): bool => str_ends_with($file, '.php'))
            ->map(fn (string $file): string => Str::of(pathinfo($file, PATHINFO_FILENAME))->studly()->toString())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function subjectFromPath(string $file): ?string
    {
        $normalized = str_replace('\\', '/', $file);

        if (preg_match('#/Resources/(?:.*/)?([^/]+Resource)(?:/|\.php$)#', $normalized, $matches)) {
            return Str::of($matches[1])->replaceEnd('Resource', '')->studly()->toString();
        }

        if (preg_match('#/resources/views/filament/(?:escola/)?resources/([^/]+)-resource#i', $normalized, $matches)) {
            return Str::of($matches[1])->studly()->toString();
        }

        if (preg_match('#/Pages/([^/]+)\.php$#', $normalized, $matches)) {
            return Str::of($matches[1])->studly()->toString();
        }

        if (preg_match('#/resources/views/filament/(?:escola/)?pages/([^/]+)\.blade\.php$#i', $normalized, $matches)) {
            return Str::of($matches[1])->studly()->toString();
        }

        return null;
    }

    protected static function files(array $roots): array
    {
        $files = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                if (str_ends_with($path, '.php') || str_ends_with($path, '.blade.php')) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }
}
