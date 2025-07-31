<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Agency;
use App\Traits\TenantScoped;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantScopingTest extends TestCase
{
  use RefreshDatabase;

  public function test_tenant_scoped_trait_provides_correct_methods()
  {
    $testModel = new class extends Model {
      use TenantScoped;

      protected $table = 'test_models';
      protected $fillable = ['name', 'agency_id'];
    };

    // Test default tenant scoping behavior
    $this->assertTrue($testModel->isTenantScoped());
    $this->assertEquals('agency_id', $testModel->getTenantKeyName());
  }

  public function test_tenant_scope_class_exists_and_implements_scope_interface()
  {
    $scope = new TenantScope();
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Scope::class, $scope);
  }

  public function test_tenant_scoped_trait_adds_agency_relationship()
  {
    $testModel = new class extends Model {
      use TenantScoped;

      protected $table = 'test_models';
      protected $fillable = ['name', 'agency_id'];
    };

    // Test that agency relationship exists
    $relation = $testModel->agency();
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
  }

  public function test_tenant_scoped_trait_provides_scoping_methods()
  {
    $testModel = new class extends Model {
      use TenantScoped;

      protected $table = 'test_models';
      protected $fillable = ['name', 'agency_id'];
    };

    // Test that scoping methods exist
    $this->assertTrue(method_exists($testModel, 'scopeWithoutTenantScope'));
    $this->assertTrue(method_exists($testModel, 'scopeForTenant'));
  }

  public function test_agency_factory_works()
  {
    $agency = Agency::factory()->create(['name' => 'Test Agency']);

    $this->assertInstanceOf(Agency::class, $agency);
    $this->assertEquals('Test Agency', $agency->name);
    $this->assertNotNull($agency->slug);
  }

  public function test_tenant_model_uses_tenant_scoped_trait()
  {
    $tenant = new \App\Models\Tenant();

    // Test that Tenant model uses TenantScoped trait
    $this->assertTrue($tenant->isTenantScoped());
    $this->assertEquals('agency_id', $tenant->getTenantKeyName());

    // Test that agency relationship exists
    $relation = $tenant->agency();
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);

    // Test that scoping methods are available
    $this->assertTrue(method_exists($tenant, 'scopeWithoutTenantScope'));
    $this->assertTrue(method_exists($tenant, 'scopeForTenant'));
  }
}
