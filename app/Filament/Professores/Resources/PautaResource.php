<?php

namespace App\Filament\Professores\Resources;

use App\Filament\Professores\Resources\PautaResource\Pages;
use App\Models\StudentClass;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PautaResource extends \App\Filament\Resources\PautaResource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-table-cells';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Mini Pautas';
    protected static ?string $modelLabel = 'Mini Pauta';
    protected static ?string $pluralModelLabel = 'Mini Pautas';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::currentUserIsProfessorAdmin()) {
            return $query;
        }

        $trainer = static::currentTrainer();

        if (! $trainer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($trainer): void {
            static::whereClassBelongsToProfessorScope($query, $trainer);
        });
    }

    public static function canAccess(): bool
    {
        return static::canUseProfessorPautas();
    }

    public static function canViewAny(): bool
    {
        return static::canUseProfessorPautas();
    }

    public static function canView(Model $record): bool
    {
        if (! $record instanceof StudentClass) {
            return false;
        }

        if (static::currentUserIsProfessorAdmin()) {
            return true;
        }

        $trainer = static::currentTrainer();

        if (! $trainer) {
            return false;
        }

        return static::classBelongsToProfessorScope($record, $trainer);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\MiniPauta::route('/'),
        ];
    }

    protected static function canUseProfessorPautas(): bool
    {
        return (bool) Auth::user()?->can('AccessPanel:Professores');
    }

    protected static function currentTrainer(): ?Trainer
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->trainer
            ?: Trainer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])
                ->first();
    }

    protected static function currentUserIsProfessorAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('professores_admin');
    }

    protected static function classBelongsToProfessorScope(StudentClass $record, Trainer $trainer): bool
    {
        return StudentClass::query()
            ->whereKey($record->getKey())
            ->where(function (Builder $query) use ($trainer): void {
                static::whereClassBelongsToProfessorScope($query, $trainer);
            })
            ->exists();
    }

    protected static function whereClassBelongsToProfessorScope(Builder $query, Trainer $trainer): void
    {
        $query
            ->whereExists(function ($assignmentQuery) use ($trainer): void {
                $assignmentQuery
                    ->selectRaw('1')
                    ->from('trainer_class_assignments')
                    ->where('trainer_class_assignments.trainer_id', $trainer->getKey())
                    ->where('trainer_class_assignments.is_active', true)
                    ->whereColumn('trainer_class_assignments.class_id', 'classes.id');
            })
            ->orWhereExists(function ($authorizationQuery) use ($trainer): void {
                $authorizationQuery
                    ->selectRaw('1')
                    ->from('trainer_subject_authorizations')
                    ->join('course_maps', 'course_maps.course_id', '=', 'trainer_subject_authorizations.course_id')
                    ->where('trainer_subject_authorizations.trainer_id', $trainer->getKey())
                    ->whereColumn('trainer_subject_authorizations.institution_id', 'classes.institution_id')
                    ->whereColumn('course_maps.id', 'classes.course_map_id');
            });
    }
}
