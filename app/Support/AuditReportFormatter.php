<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class AuditReportFormatter
{
    protected static array $referenceCache = [];

    public static function activityActionLabel(ActivityLog|string $activity): string
    {
        $action = is_string($activity) ? $activity : (string) $activity->action;

        return match ($action) {
            'login' => 'Entrada no sistema',
            'logout' => 'Saida do sistema',
            'create' => 'Registo criado',
            'update' => 'Registo atualizado',
            'delete' => 'Registo eliminado',
            'view' => 'Consulta',
            default => str($action)->replace(['_', '.', '-'], ' ')->title()->toString(),
        };
    }

    public static function activityDescription(ActivityLog|string $activity): string
    {
        if (is_string($activity)) {
            return match ($activity) {
                'login' => 'Utilizador iniciou sessao no sistema',
                'logout' => 'Utilizador encerrou sessao no sistema',
                default => self::activityActionLabel($activity),
            };
        }

        if (filled($activity->description)) {
            return (string) $activity->description;
        }

        return match ((string) $activity->action) {
            'login' => 'Entrou no sistema',
            'logout' => 'Saiu do sistema',
            default => self::activityActionLabel($activity),
        };
    }

    public static function auditEventLabel($audit): string
    {
        if (is_string($audit)) {
            return match ($audit) {
                'created' => 'Registo criado',
                'updated' => 'Registo atualizado',
                'deleted' => 'Registo eliminado',
                'restored' => 'Registo restaurado',
                default => str($audit)->replace(['_', '.', '-'], ' ')->title()->toString(),
            };
        }

        $modelLabel = self::auditModelLabel((string) $audit->auditable_type);

        return match ((string) $audit->event) {
            'created' => "{$modelLabel} criado",
            'updated' => "{$modelLabel} atualizado",
            'deleted' => "{$modelLabel} eliminado",
            'restored' => "{$modelLabel} restaurado",
            default => $modelLabel.' '.str((string) $audit->event)->replace(['_', '.', '-'], ' ')->lower()->toString(),
        };
    }

    public static function auditDescription($audit): string
    {
        $oldValues = self::normalizeAuditValues($audit->old_values);
        $newValues = self::normalizeAuditValues($audit->new_values);
        $modelType = (string) $audit->auditable_type;
        $modelLabel = self::auditModelLabel($modelType);
        $recordName = self::auditRecordName($audit, $oldValues, $newValues);

        $subject = $modelLabel.($recordName ? ' "'.$recordName.'"' : ' #'.$audit->auditable_id);
        $event = (string) $audit->event;
        $changes = self::changesSummary($oldValues, $newValues, $modelType, $event);

        $description = match ($event) {
            'created' => "Criou {$subject}",
            'updated' => "Atualizou {$subject}",
            'deleted' => "Eliminou {$subject}",
            'restored' => "Restaurou {$subject}",
            default => self::auditEventLabel($audit).' '.$subject,
        };

        return $changes ? "{$description}: {$changes}" : $description;
    }

    public static function isTechnicalSessionAudit($audit): bool
    {
        if (! in_array((string) $audit->auditable_type, [User::class, (new User())->getMorphClass()], true)) {
            return false;
        }

        $keys = self::changedAuditKeys(
            self::normalizeAuditValues($audit->old_values),
            self::normalizeAuditValues($audit->new_values)
        );

        if ($keys === []) {
            return false;
        }

        return empty(array_diff($keys, [
            'current_session_id',
            'last_login_at',
            'last_login_ip',
            'remember_token',
        ]));
    }

    public static function normalizeAuditValues($values): array
    {
        if ($values instanceof \Illuminate\Support\Collection) {
            return $values->toArray();
        }

        if (is_array($values)) {
            return $values;
        }

        if (is_object($values) && method_exists($values, 'toArray')) {
            return $values->toArray();
        }

        if (is_string($values) && trim($values) !== '') {
            $decoded = json_decode($values, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public static function auditModelLabel(?string $modelType): string
    {
        return [
            'App\\Models\\User' => 'Utilizador',
            'App\\Models\\Candidate' => 'Alistado',
            'App\\Models\\Student' => 'Formando',
            'App\\Models\\Trainer' => 'Formador',
            'App\\Models\\Institution' => 'Instituicao',
            'App\\Models\\InstitutionType' => 'Tipo de instituicao',
            'App\\Models\\Course' => 'Curso',
            'App\\Models\\CourseMap' => 'Mapa de curso',
            'App\\Models\\CoursePlan' => 'Plano de curso',
            'App\\Models\\Subject' => 'Disciplina',
            'App\\Models\\Rank' => 'Patente',
            'App\\Models\\Provenance' => 'Orgao de proveniencia',
            'App\\Models\\RecruitmentType' => 'Tipo de recrutamento',
            'App\\Models\\StudentType' => 'Tipo de aluno',
            'App\\Models\\StudentClass' => 'Turma',
            'App\\Models\\StudentLeave' => 'Ocorrencia',
            'App\\Models\\Evaluation' => 'Avaliacao',
            'App\\Models\\EquipmentAssignment' => 'Atribuicao de equipamento',
            'App\\Models\\Document' => 'Documento',
        ][$modelType] ?? (class_basename((string) $modelType) ?: 'Registo');
    }

    public static function fieldLabel(string $field): string
    {
        return [
            'name' => 'Nome',
            'full_name' => 'Nome completo',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'password' => 'Palavra-passe',
            'institution_id' => 'Instituicao',
            'institution_type_id' => 'Tipo de instituicao',
            'is_active' => 'Estado',
            'status' => 'Estado',
            'student_type' => 'Tipo',
            'student_type_id' => 'Tipo de aluno',
            'id_number' => 'N. BI',
            'bi_number' => 'N. BI',
            'bilhete' => 'N. BI',
            'nuri' => 'NURI',
            'student_number' => 'N. de aluno',
            'candidate_id' => 'Alistado',
            'provenance_id' => 'Proveniencia',
            'rank_id' => 'Patente',
            'current_rank_id' => 'Patente',
            'recruitment_type_id' => 'Tipo de recrutamento',
            'course_id' => 'Curso',
            'course_map_id' => 'Mapa de curso',
            'course_plan_id' => 'Plano de curso',
            'academic_year_id' => 'Ano lectivo',
            'subject_id' => 'Disciplina',
            'class_id' => 'Turma',
            'student_class_id' => 'Turma',
            'title' => 'Titulo',
            'description' => 'Descricao',
            'reference_number' => 'N. de referencia',
        ][$field] ?? str($field)->replace('_', ' ')->title()->toString();
    }

    public static function formatValue(string $field, mixed $value, ?string $modelType = null): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $field === 'is_active'
                ? ($value ? 'Activo' : 'Inactivo')
                : ($value ? 'Sim' : 'Nao');
        }

        if (is_array($value)) {
            return str(json_encode($value, JSON_UNESCAPED_UNICODE))->limit(90)->toString();
        }

        $resolved = self::resolveReference($field, $value);

        if ($resolved !== null) {
            return $resolved;
        }

        if (is_string($value) && preg_match('/(_at|_date|date)$/', $field)) {
            try {
                return Carbon::parse($value)->format('d/m/Y H:i');
            } catch (\Throwable) {
                //
            }
        }

        return str((string) $value)->limit(90)->toString();
    }

    protected static function changedAuditKeys(array $oldValues, array $newValues): array
    {
        return array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
    }

    protected static function changesSummary(array $oldValues, array $newValues, ?string $modelType = null, ?string $event = null): string
    {
        $parts = [];
        $keys = self::changedAuditKeys($oldValues, $newValues);

        foreach ($keys as $key) {
            if (in_array($key, ['id', 'current_session_id', 'last_login_at', 'last_login_ip', 'remember_token'], true)) {
                continue;
            }

            if ($key === 'password') {
                $parts[] = 'Palavra-passe: '.($event === 'created' ? 'definida' : 'alterada');
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($event === 'created') {
                if ($newValue === null || $newValue === '') {
                    continue;
                }

                $parts[] = self::fieldLabel($key).': '.self::formatValue($key, $newValue, $modelType);
                continue;
            }

            if ($event === 'deleted') {
                if ($oldValue === null || $oldValue === '') {
                    continue;
                }

                $parts[] = self::fieldLabel($key).': '.self::formatValue($key, $oldValue, $modelType);
                continue;
            }

            if (self::valuesAreEqual($oldValue, $newValue)) {
                continue;
            }

            $parts[] = self::fieldLabel($key).': '
                .self::formatValue($key, $oldValue, $modelType)
                .' -> '
                .self::formatValue($key, $newValue, $modelType);
        }

        if ($parts === []) {
            return '';
        }

        $extraCount = max(count($parts) - 6, 0);
        $parts = array_slice($parts, 0, 6);

        if ($extraCount > 0) {
            $parts[] = "+{$extraCount} campo(s)";
        }

        return implode('; ', $parts);
    }

    protected static function auditRecordName($audit, array $oldValues, array $newValues): ?string
    {
        foreach ([$newValues, $oldValues] as $values) {
            foreach (['name', 'full_name', 'title', 'email', 'student_number', 'id_number', 'nuri', 'reference_number'] as $field) {
                if (! empty($values[$field]) && is_scalar($values[$field])) {
                    return self::formatValue($field, $values[$field], (string) $audit->auditable_type);
                }
            }
        }

        try {
            if (is_string($audit->auditable_type) && class_exists($audit->auditable_type)) {
                $auditable = $audit->auditable_type::find($audit->auditable_id);

                return $auditable?->name
                    ?? $auditable?->full_name
                    ?? $auditable?->title
                    ?? $auditable?->email
                    ?? $auditable?->student_number
                    ?? null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected static function valuesAreEqual(mixed $oldValue, mixed $newValue): bool
    {
        return json_encode($oldValue, JSON_UNESCAPED_UNICODE) === json_encode($newValue, JSON_UNESCAPED_UNICODE);
    }

    protected static function resolveReference(string $field, mixed $value): ?string
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        $map = [
            'institution_id' => ['App\\Models\\Institution', ['name']],
            'institution_type_id' => ['App\\Models\\InstitutionType', ['name']],
            'candidate_id' => ['App\\Models\\Candidate', ['full_name', 'id_number']],
            'provenance_id' => ['App\\Models\\Provenance', ['name', 'acronym']],
            'rank_id' => ['App\\Models\\Rank', ['name']],
            'current_rank_id' => ['App\\Models\\Rank', ['name']],
            'recruitment_type_id' => ['App\\Models\\RecruitmentType', ['name']],
            'student_type_id' => ['App\\Models\\StudentType', ['name']],
            'course_id' => ['App\\Models\\Course', ['name']],
            'course_map_id' => ['App\\Models\\CourseMap', ['name']],
            'course_plan_id' => ['App\\Models\\CoursePlan', ['name']],
            'academic_year_id' => ['App\\Models\\AcademicYear', ['name', 'year']],
            'subject_id' => ['App\\Models\\Subject', ['name']],
            'class_id' => ['App\\Models\\StudentClass', ['name']],
            'student_class_id' => ['App\\Models\\StudentClass', ['name']],
        ];

        if (! isset($map[$field])) {
            return null;
        }

        [$class, $columns] = $map[$field];
        $cacheKey = $field.':'.$value;

        if (array_key_exists($cacheKey, self::$referenceCache)) {
            return self::$referenceCache[$cacheKey];
        }

        try {
            if (! class_exists($class)) {
                return self::$referenceCache[$cacheKey] = '#'.$value;
            }

            $record = $class::find($value);

            if (! $record) {
                return self::$referenceCache[$cacheKey] = '#'.$value;
            }

            $label = collect($columns)
                ->map(fn (string $column) => $record->{$column} ?? null)
                ->filter()
                ->implode(' - ');

            return self::$referenceCache[$cacheKey] = ($label ?: '#'.$value);
        } catch (\Throwable) {
            return self::$referenceCache[$cacheKey] = '#'.$value;
        }
    }
}
