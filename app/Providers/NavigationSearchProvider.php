<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Illuminate\Support\Str;

class NavigationSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();
        $query = mb_strtolower(trim($query));

        if (strlen($query) < 1) {
            return $builder;
        }

        $pageResults = collect();

        // Search through navigation items
        $navigation = Filament::getNavigation();

        foreach ($navigation as $group) {
            $groupLabel = $group->getLabel() ?? '';

            foreach ($group->getItems() as $item) {
                $label = $item->getLabel() ?? '';
                $url = $item->getUrl() ?? '';

                if (!$label || !$url) {
                    continue;
                }

                // Check if label matches query (fuzzy)
                $labelLower = mb_strtolower($label);
                $groupLower = mb_strtolower($groupLabel);

                if (
                    Str::contains($labelLower, $query) ||
                    Str::contains($groupLower, $query) ||
                    similar_text($labelLower, $query, $percent) && $percent > 60
                ) {
                    $pageResults->push(new GlobalSearchResult(
                        title: $label,
                        url: $url,
                        details: $groupLabel ? ['Grupo' => $groupLabel] : [],
                    ));
                }

                // Search sub-items
                foreach ($item->getChildItems() as $childItem) {
                    $childLabel = $childItem->getLabel() ?? '';
                    $childUrl = $childItem->getUrl() ?? '';

                    if (!$childLabel || !$childUrl) {
                        continue;
                    }

                    $childLabelLower = mb_strtolower($childLabel);

                    if (
                        Str::contains($childLabelLower, $query) ||
                        similar_text($childLabelLower, $query, $percent) && $percent > 60
                    ) {
                        $pageResults->push(new GlobalSearchResult(
                            title: $childLabel,
                            url: $childUrl,
                            details: ['Grupo' => $groupLabel, 'Em' => $label],
                        ));
                    }
                }
            }
        }

        if ($pageResults->isNotEmpty()) {
            $builder->category('Páginas', $pageResults);
        }

        // Also search through resources (the default behavior, but only for resources with proper titles)
        $resourceResults = $this->searchResources($query);
        foreach ($resourceResults as $category => $results) {
            if ($results->isNotEmpty()) {
                $builder->category($category, $results);
            }
        }

        return $builder;
    }

    protected function searchResources(string $query): array
    {
        $results = [];
        $resources = Filament::getResources();

        foreach ($resources as $resource) {
            if (! $resource::canGloballySearch()) {
                continue;
            }

            $resourceResults = $resource::getGlobalSearchResults($query);

            if (! $resourceResults->count()) {
                continue;
            }

            $results[$resource::getPluralModelLabel()] = $resourceResults;
        }

        return $results;
    }
}
