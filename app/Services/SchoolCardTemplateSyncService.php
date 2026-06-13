<?php

namespace App\Services;

use App\Models\CardTemplate;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SchoolCardTemplateSyncService
{
    public function syncGlobalTemplatesToAllSchools(): array
    {
        $result = [
            'templates' => 0,
            'institutions' => 0,
            'created' => 0,
            'existing' => 0,
        ];

        $templates = $this->globalTemplates()->get();
        $institutions = Institution::query()
            ->orderBy('id')
            ->get(['id']);

        $result['templates'] = $templates->count();
        $result['institutions'] = $institutions->count();

        DB::transaction(function () use ($templates, $institutions, &$result): void {
            foreach ($templates as $template) {
                foreach ($institutions as $institution) {
                    $created = $this->syncTemplateToInstitution($template, (int) $institution->id);

                    $created
                        ? $result['created']++
                        : $result['existing']++;
                }
            }
        });

        return $result;
    }

    public function syncGlobalTemplatesToInstitution(Institution|int $institution): array
    {
        $institutionId = $institution instanceof Institution
            ? (int) $institution->getKey()
            : (int) $institution;

        $result = [
            'templates' => 0,
            'institutions' => $institutionId > 0 ? 1 : 0,
            'created' => 0,
            'existing' => 0,
        ];

        if ($institutionId <= 0) {
            return $result;
        }

        $templates = $this->globalTemplates()->get();
        $result['templates'] = $templates->count();

        DB::transaction(function () use ($templates, $institutionId, &$result): void {
            foreach ($templates as $template) {
                $created = $this->syncTemplateToInstitution($template, $institutionId);

                $created
                    ? $result['created']++
                    : $result['existing']++;
            }
        });

        return $result;
    }

    public function syncTemplateToAllInstitutions(CardTemplate $sourceTemplate): array
    {
        $result = [
            'templates' => $sourceTemplate->institution_id === null ? 1 : 0,
            'institutions' => 0,
            'created' => 0,
            'existing' => 0,
        ];

        if ($sourceTemplate->institution_id !== null) {
            return $result;
        }

        $institutions = Institution::query()
            ->orderBy('id')
            ->get(['id']);

        $result['institutions'] = $institutions->count();

        DB::transaction(function () use ($sourceTemplate, $institutions, &$result): void {
            foreach ($institutions as $institution) {
                $created = $this->syncTemplateToInstitution($sourceTemplate, (int) $institution->id);

                $created
                    ? $result['created']++
                    : $result['existing']++;
            }
        });

        return $result;
    }

    protected function syncTemplateToInstitution(CardTemplate $sourceTemplate, int $institutionId): bool
    {
        if ($institutionId <= 0 || $sourceTemplate->institution_id !== null) {
            return false;
        }

        $existing = CardTemplate::query()
            ->where('institution_id', $institutionId)
            ->where(function (Builder $query) use ($sourceTemplate): void {
                $query
                    ->where('source_template_id', $sourceTemplate->getKey())
                    ->orWhere(function (Builder $query) use ($sourceTemplate): void {
                        $query
                            ->whereNull('source_template_id')
                            ->where('name', $sourceTemplate->name)
                            ->where('card_type', $sourceTemplate->card_type)
                            ->where(function (Builder $query) use ($sourceTemplate): void {
                                $variant = $sourceTemplate->card_variant;

                                filled($variant)
                                    ? $query->where('card_variant', $variant)
                                    : $query->whereNull('card_variant');
                            });
                    });
            })
            ->first();

        if ($existing instanceof CardTemplate) {
            if (blank($existing->source_template_id)) {
                $existing->forceFill(['source_template_id' => $sourceTemplate->getKey()])->save();
            }

            return false;
        }

        $copy = $sourceTemplate->replicate();
        $copy->institution_id = $institutionId;
        $copy->source_template_id = $sourceTemplate->getKey();
        $copy->save();

        return true;
    }

    protected function globalTemplates(): Builder
    {
        return CardTemplate::query()
            ->whereNull('institution_id')
            ->whereNull('source_template_id')
            ->orderBy('card_type')
            ->orderBy('name');
    }
}
