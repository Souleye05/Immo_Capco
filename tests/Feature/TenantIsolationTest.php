<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Property;
use App\Models\Contract;
use App\Models\Payment;
use App\Enums\PropertyType;
use App\Enums\CommissionUnit;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Créer les rôles nécessaires
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
  }

  public function test_user_can_only_see_own_agency_data()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur et l'associer à l'agence 1
    $user = User::factory()->create();
    $user->agencys()->attach($agency1);
    $user->assignRole('admin');

    // Créer des propriétés dans chaque agence
    $property1 = Property::factory()->create([
      'agency_id' => $agency1->id,
      'name' => 'Property Agency 1',
      'type' => PropertyType::VILLA,
      'commission_value' => 10,
      'commission_unit' => CommissionUnit::PERCENTAGE,
    ]);

    $property2 = Property::factory()->create([
      'agency_id' => $agency2->id,
      'name' => 'Property Agency 2',
      'type' => PropertyType::IMMEUBLE,
      'commission_value' => 15,
      'commission_unit' => CommissionUnit::PERCENTAGE,
    ]);

    // Authentifier l'utilisateur et définir le tenant
    $this->actingAs($user);
    Filament::setTenant($agency1);

    // Vérifier que seules les propriétés de l'agence 1 sont visibles
    $visibleProperties = Property::all();

    $this->assertCount(1, $visibleProperties);
    $this->assertEquals('Property Agency 1', $visibleProperties->first()->name);
    $this->assertEquals($agency1->id, $visibleProperties->first()->agency_id);
  }

  public function test_global_scope_filters_data_automatically()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur pour les tests de tenant
    $user = User::factory()->create();
    $user->agencys()->attach([$agency1->id, $agency2->id]);
    $this->actingAs($user);

    // Créer des propriétés dans chaque agence
    Property::factory()->count(3)->create(['agency_id' => $agency1->id]);
    Property::factory()->count(2)->create(['agency_id' => $agency2->id]);

    // Sans tenant défini, toutes les propriétés sont visibles
    $this->assertCount(5, Property::withoutGlobalScopes()->get());

    // Avec tenant agence 1
    Filament::setTenant($agency1);
    $this->assertCount(3, Property::all());

    // Avec tenant agence 2
    Filament::setTenant($agency2);
    $this->assertCount(2, Property::all());
  }

  public function test_auto_assignment_of_agency_id_on_creation()
  {
    // Créer une agence
    $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);

    // Créer un utilisateur
    $user = User::factory()->create();
    $user->agencys()->attach($agency);

    // Authentifier et définir le tenant
    $this->actingAs($user);
    Filament::setTenant($agency);

    // Créer une propriété sans spécifier agency_id
    $property = Property::create([
      'name' => 'Auto Assigned Property',
      'address' => '123 Test Street',
      'type' => PropertyType::VILLA,
      'number_flat' => 1,
      'commission_value' => 10,
      'commission_unit' => CommissionUnit::PERCENTAGE,
    ]);

    // Vérifier que l'agency_id a été automatiquement assigné
    $this->assertEquals($agency->id, $property->agency_id);
  }

  public function test_user_cannot_access_other_agency_data()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur associé à l'agence 1
    $user = User::factory()->create();
    $user->agencys()->attach($agency1);

    // Créer une propriété dans l'agence 2
    $property = Property::factory()->create([
      'agency_id' => $agency2->id,
      'name' => 'Restricted Property',
    ]);

    // Authentifier l'utilisateur et définir le tenant agence 1
    $this->actingAs($user);
    Filament::setTenant($agency1);

    // Vérifier que la propriété de l'agence 2 n'est pas accessible
    $this->assertNull(Property::find($property->id));
    $this->assertCount(0, Property::where('name', 'Restricted Property')->get());
  }

  public function test_tenant_context_switching()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur avec accès aux deux agences
    $user = User::factory()->create();
    $user->agencys()->attach([$agency1->id, $agency2->id]);

    // Créer des propriétés dans chaque agence
    Property::factory()->create(['agency_id' => $agency1->id, 'name' => 'Property 1']);
    Property::factory()->create(['agency_id' => $agency2->id, 'name' => 'Property 2']);

    $this->actingAs($user);

    // Basculer vers l'agence 1
    Filament::setTenant($agency1);
    $properties1 = Property::all();
    $this->assertCount(1, $properties1);
    $this->assertEquals('Property 1', $properties1->first()->name);

    // Basculer vers l'agence 2
    Filament::setTenant($agency2);
    $properties2 = Property::all();
    $this->assertCount(1, $properties2);
    $this->assertEquals('Property 2', $properties2->first()->name);
  }

  public function test_contracts_are_tenant_scoped()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur pour les tests de tenant
    $user = User::factory()->create();
    $user->agencys()->attach([$agency1->id, $agency2->id]);
    $this->actingAs($user);

    // Créer des contrats dans chaque agence
    $contract1 = Contract::factory()->create(['agency_id' => $agency1->id]);
    $contract2 = Contract::factory()->create(['agency_id' => $agency2->id]);

    // Tester le scoping pour l'agence 1
    Filament::setTenant($agency1);
    $visibleContracts1 = Contract::all();
    $this->assertCount(1, $visibleContracts1);
    $this->assertEquals($contract1->id, $visibleContracts1->first()->id);

    // Tester le scoping pour l'agence 2
    Filament::setTenant($agency2);
    $visibleContracts2 = Contract::all();
    $this->assertCount(1, $visibleContracts2);
    $this->assertEquals($contract2->id, $visibleContracts2->first()->id);
  }

  public function test_payments_are_tenant_scoped()
  {
    // Créer deux agences
    $agency1 = Agency::factory()->create(['name' => 'Agency 1', 'slug' => 'agency-1']);
    $agency2 = Agency::factory()->create(['name' => 'Agency 2', 'slug' => 'agency-2']);

    // Créer un utilisateur pour les tests de tenant
    $user = User::factory()->create();
    $user->agencys()->attach([$agency1->id, $agency2->id]);
    $this->actingAs($user);

    // Créer des paiements dans chaque agence
    $payment1 = Payment::factory()->create(['agency_id' => $agency1->id]);
    $payment2 = Payment::factory()->create(['agency_id' => $agency2->id]);

    // Tester le scoping pour l'agence 1
    Filament::setTenant($agency1);
    $visiblePayments1 = Payment::all();
    $this->assertCount(1, $visiblePayments1);
    $this->assertEquals($payment1->id, $visiblePayments1->first()->id);

    // Tester le scoping pour l'agence 2
    Filament::setTenant($agency2);
    $visiblePayments2 = Payment::all();
    $this->assertCount(1, $visiblePayments2);
    $this->assertEquals($payment2->id, $visiblePayments2->first()->id);
  }
}
