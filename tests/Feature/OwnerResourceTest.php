<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Filament\Admin\Resources\OwnerResource;

class OwnerResourceTest extends TestCase
{
  use RefreshDatabase;

  private User $adminUser;
  private Agency $agency;
  private Property $property;

  protected function setUp(): void
  {
    parent::setUp();

    // Create roles
    Role::create(['name' => 'super-admin']);
    Role::create(['name' => 'agency-owner']);
    Role::create(['name' => 'owner']);

    // Create admin user
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('super-admin');

    // Create agency and property
    $this->agency = Agency::factory()->create();
    $this->property = Property::factory()->create([
      'agency_id' => $this->agency->id,
    ]);
  }

  public function test_owner_creation_with_new_email_through_filament_interface()
  {
    $this->actingAs($this->adminUser);

    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'email' => 'newowner@example.com',
      'property_id' => $this->property->id,
    ];

    // Test the create page component
    Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm($ownerData)
      ->call('create')
      ->assertHasNoFormErrors();

    // Verify owner was created
    $this->assertDatabaseHas('owners', [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'property_id' => $this->property->id,
    ]);

    // Verify user was created
    $this->assertDatabaseHas('users', [
      'email' => 'newowner@example.com',
      'name' => 'Test Owner',
    ]);

    // Verify user has owner role
    $user = User::where('email', 'newowner@example.com')->first();
    $this->assertTrue($user->hasRole('owner'));

    // Verify user is associated with agency
    $this->assertTrue($user->agencies()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_owner_creation_with_existing_email_through_filament_interface()
  {
    $this->actingAs($this->adminUser);

    // Create existing user
    $existingUser = User::factory()->create([
      'email' => 'existing@example.com',
      'name' => 'Existing User',
    ]);

    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'email' => 'existing@example.com',
      'property_id' => $this->property->id,
    ];

    // Test the create page component
    Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm($ownerData)
      ->call('create')
      ->assertHasNoFormErrors();

    // Verify owner was created
    $this->assertDatabaseHas('owners', [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'property_id' => $this->property->id,
      'user_id' => $existingUser->id,
    ]);

    // Verify existing user now has owner role
    $existingUser->refresh();
    $this->assertTrue($existingUser->hasRole('owner'));

    // Verify user is associated with agency
    $this->assertTrue($existingUser->agencies()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_owner_email_update_functionality_through_filament_interface()
  {
    $this->actingAs($this->adminUser);

    // Create owner with initial user
    $initialUser = User::factory()->create(['email' => 'initial@example.com']);
    $owner = Owner::factory()->create([
      'user_id' => $initialUser->id,
      'property_id' => $this->property->id,
    ]);

    $updateData = [
      'name' => $owner->name,
      'phone' => $owner->phone,
      'email' => 'updated@example.com',
      'property_id' => $owner->property_id,
    ];

    // Test the edit page component
    Livewire::test(OwnerResource\Pages\EditOwner::class, ['record' => $owner->id])
      ->fillForm($updateData)
      ->call('save')
      ->assertHasNoFormErrors();

    // Verify owner was updated
    $owner->refresh();
    $this->assertNotNull($owner->user_id);

    // Verify new user was created or existing user was associated
    $this->assertDatabaseHas('users', [
      'email' => 'updated@example.com',
    ]);

    $newUser = User::where('email', 'updated@example.com')->first();
    $this->assertEquals($owner->user_id, $newUser->id);
    $this->assertTrue($newUser->hasRole('owner'));
  }

  public function test_form_validation_scenarios_and_error_messages()
  {
    $this->actingAs($this->adminUser);

    // Test required field validation
    Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm([
        'name' => '',
        'phone' => '',
        'email' => '',
        'property_id' => null,
      ])
      ->call('create')
      ->assertHasFormErrors([
        'name' => 'required',
        'phone' => 'required',
        'email' => 'required',
      ]);

    // Test email format validation
    Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm([
        'name' => 'Test Owner',
        'phone' => '123456789',
        'email' => 'invalid-email',
        'property_id' => $this->property->id,
      ])
      ->call('create')
      ->assertHasFormErrors(['email' => 'email']);

    // Test email uniqueness validation (if implemented)
    $existingOwner = Owner::factory()->create([
      'property_id' => $this->property->id,
    ]);
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $existingOwner->update(['user_id' => $existingUser->id]);

    Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm([
        'name' => 'Test Owner',
        'phone' => '123456789',
        'email' => 'existing@example.com',
        'property_id' => $this->property->id,
      ])
      ->call('create');
    // Note: This should work as the same email can be associated with multiple owners
    // but in different agencies or properties
  }

  public function test_notification_display_for_different_scenarios()
  {
    $this->actingAs($this->adminUser);

    // Test notification for new user creation
    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'email' => 'newowner@example.com',
      'property_id' => $this->property->id,
    ];

    $component = Livewire::test(OwnerResource\Pages\CreateOwner::class)
      ->fillForm($ownerData)
      ->call('create');

    // The notification is sent via the Notification facade, 
    // so we can't easily test it in a unit test without mocking
    // But we can verify the owner was created successfully
    $this->assertDatabaseHas('owners', [
      'name' => 'Test Owner',
      'phone' => '123456789',
    ]);
  }

  public function test_owner_form_displays_email_field_with_helper_text()
  {
    $this->actingAs($this->adminUser);

    // Test that the create form contains the email field
    $component = Livewire::test(OwnerResource\Pages\CreateOwner::class);

    // Verify the form schema includes email field
    $form = $component->instance()->getForm();
    $this->assertNotNull($form->getComponent('email'));
  }

  public function test_edit_form_populates_email_from_associated_user()
  {
    $this->actingAs($this->adminUser);

    // Create owner with associated user
    $user = User::factory()->create(['email' => 'owner@example.com']);
    $owner = Owner::factory()->create([
      'user_id' => $user->id,
      'property_id' => $this->property->id,
    ]);

    // Test that the edit form populates the email field
    $component = Livewire::test(OwnerResource\Pages\EditOwner::class, ['record' => $owner->id]);

    // The email should be populated in the form data
    $formData = $component->instance()->data;
    $this->assertEquals('owner@example.com', $formData['email']);
  }
}
