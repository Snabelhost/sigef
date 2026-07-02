<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Institution;
use App\Models\User;
use App\Notifications\NewDocumentNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use ReflectionMethod;
use Tests\TestCase;

class SchoolPanelUrlTest extends TestCase
{
    public function test_school_panel_uses_filament_tenant_entrypoint(): void
    {
        $institution = new Institution();
        $institution->forceFill(['id' => 2]);

        $panels = $this->schoolOnlyUser($institution)->accessiblePanels();

        $this->assertArrayHasKey('escola', $panels);
        $this->assertSame('/escola', $panels['escola']['url']);
        $this->assertSame(2, $panels['escola']['tenant_id']);
    }

    public function test_school_panel_is_hidden_when_the_user_institution_is_missing(): void
    {
        $panels = $this->schoolOnlyUser(null)->accessiblePanels();

        $this->assertArrayNotHasKey('escola', $panels);
    }

    public function test_school_panel_tenant_access_accepts_matching_numeric_ids(): void
    {
        $user = new User();
        $user->forceFill(['institution_id' => '2']);

        $tenant = new Institution();
        $tenant->forceFill(['id' => 2]);

        $this->assertTrue($user->canAccessTenant($tenant));
    }

    public function test_school_panel_tenant_access_rejects_other_institutions(): void
    {
        $user = new User();
        $user->forceFill(['institution_id' => 2]);

        $tenant = new Institution();
        $tenant->forceFill(['id' => 3]);

        $this->assertFalse($user->canAccessTenant($tenant));
    }

    public function test_school_document_notification_url_uses_filament_route_name(): void
    {
        $document = new Document();
        $document->forceFill(['id' => 10]);

        $notifiable = new class {
            public int $institution_id = 2;

            public function hasRole(string $role): bool
            {
                return $role === 'escola_admin';
            }
        };

        $method = new ReflectionMethod(NewDocumentNotification::class, 'resolveDocumentUrl');

        $url = $method->invoke(new NewDocumentNotification($document), $notifiable);

        $this->assertSame('/escola/2/documents/10', $url);
    }

    private function schoolOnlyUser(?Institution $institution): User
    {
        $user = new class extends User {
            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $permission === 'AccessPanel:Escola';
            }
        };

        $user->forceFill([
            'institution_id' => $institution?->getKey() ?? 2,
            'is_active' => true,
        ]);
        $user->setRelation('institution', $institution);
        $user->setRelation('roles', new EloquentCollection());

        return $user;
    }
}
