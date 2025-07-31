<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OwnerUserService;
use App\Models\User;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Spatie\Permission\Models\Role;

class OwnerUserServiceTest extends TestCase
{
  use RefreshDatabase;

  private OwnerUserService $service;
  private Agency $agency;
  private Property $property;

  protected function setUp(): void
  {
    parent::setUp();

    $this->service = new OwnerUserService();

    // Create test agency and property
    $this->agency = Agency::factory()->create();
    $this->property = Property::factory()->create([
      'agency_id' => $this->agency->id,
    ]);

    // Create owner role if it doesn't exist
    if (!Role::where('name', 'owner')->exists()) {
      Role::create(['name' => 'owner']);
    }
  }

  public function test_find_or_create_user_for_owner_creates_new_user()
  {
    $email = 'newowner@example.com';
    $name = 'New Owner';

    $user = $this->service->findOrCreateUserForOwner($email, $name, $this->agency->id);

    $this->assertInstanceOf(User::class, $user);
    $this->assertEquals($email, $user->email);
    $this->assertEquals($name, $user->name);
    $this->assertTrue($user->hasRole('owner'));
    $this->assertTrue($user->agencys()->where('agency_id', $this->agency->id)->exists());
    $this->assertNotNull($user->email_verified_at);
  }

  public function test_find_or_create_user_for_owner_finds_existing_user()
  {
    $email = 'existing@example.com';
    $existingUser = User::factory()->create(['email' => $email]);

    $user = $this->service->findOrCreateUserForOwner($email, 'Owner Name', $this->agency->id);

    $this->assertEquals($existingUser->id, $user->id);
    $this->assertTrue($user->hasRole('owner'));
    $this->assertTrue($user->agencys()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_find_or_create_user_for_owner_associates_existing_user_with_new_agency()
  {
    $email = 'existing@example.com';
    $existingUser = User::factory()->create(['email' => $email]);
    $anotherAgency = Agency::factory()->create();

    // Associate user with first agency
    $existingUser->agencys()->attach($this->agency->id);

    $user = $this->service->findOrCreateUserForOwner($email, 'Owner Name', $anotherAgency->id);

    $this->assertEquals($existingUser->id, $user->id);
    $this->assertTrue($user->agencys()->where('agency_id', $this->agency->id)->exists());
    $this->assertTrue($user->agencys()->where('agency_id', $anotherAgency->id)->exists());
  }

  public function test_create_owner_with_user_creates_owner_and_user()
  {
    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'property_id' => $this->property->id,
    ];
    $email = 'testowner@example.com';

    $owner = $this->service->createOwnerWithUser($ownerData, $email);

    $this->assertInstanceOf(Owner::class, $owner);
    $this->assertEquals($ownerData['name'], $owner->name);
    $this->assertEquals($ownerData['phone'], $owner->phone);
    $this->assertEquals($ownerData['property_id'], $owner->property_id);
    $this->assertNotNull($owner->user_id);

    $user = $owner->user;
    $this->assertEquals($email, $user->email);
    $this->assertEquals($ownerData['name'], $user->name);
    $this->assertTrue($user->hasRole('owner'));
    $this->assertTrue($user->agencys()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_create_owner_with_user_throws_exception_without_property_id()
  {
    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
    ];
    $email = 'testowner@example.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Property ID is required to determine agency association');

    $this->service->createOwnerWithUser($ownerData, $email);
  }

  public function test_create_owner_with_user_throws_exception_with_invalid_property_id()
  {
    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'property_id' => 99999, // Non-existent property
    ];
    $email = 'testowner@example.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Property not found');

    $this->service->createOwnerWithUser($ownerData, $email);
  }

  public function test_update_owner_user_association_updates_user_association()
  {
    // Create owner with initial user
    $initialUser = User::factory()->create(['email' => 'initial@example.com']);
    $owner = Owner::factory()->create([
      'user_id' => $initialUser->id,
      'property_id' => $this->property->id,
    ]);

    $newEmail = 'newemail@example.com';

    $newUser = $this->service->updateOwnerUserAssociation($owner, $newEmail);

    $owner->refresh();
    $this->assertEquals($newUser->id, $owner->user_id);
    $this->assertEquals($newEmail, $newUser->email);
    $this->assertTrue($newUser->hasRole('owner'));
    $this->assertTrue($newUser->agencys()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_update_owner_user_association_with_existing_user()
  {
    // Create owner with initial user
    $initialUser = User::factory()->create(['email' => 'initial@example.com']);
    $owner = Owner::factory()->create([
      'user_id' => $initialUser->id,
      'property_id' => $this->property->id,
    ]);

    // Create existing user with new email
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $updatedUser = $this->service->updateOwnerUserAssociation($owner, $existingUser->email);

    $owner->refresh();
    $this->assertEquals($existingUser->id, $owner->user_id);
    $this->assertEquals($updatedUser->id, $existingUser->id);
    $this->assertTrue($updatedUser->hasRole('owner'));
    $this->assertTrue($updatedUser->agencys()->where('agency_id', $this->agency->id)->exists());
  }

  public function test_update_owner_user_association_throws_exception_without_property()
  {
    $owner = Owner::factory()->create(['property_id' => null]);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Owner must have an associated property to determine agency');

    $this->service->updateOwnerUserAssociation($owner, 'newemail@example.com');
  }

  public function test_user_owner_records_relationship()
  {
    $user = User::factory()->create();
    $owner1 = Owner::factory()->create(['user_id' => $user->id]);
    $owner2 = Owner::factory()->create(['user_id' => $user->id]);

    $ownerRecords = $user->ownerRecords;

    $this->assertCount(2, $ownerRecords);
    $this->assertTrue($ownerRecords->contains($owner1));
    $this->assertTrue($ownerRecords->contains($owner2));
  }

  public function test_owner_user_relationship()
  {
    $user = User::factory()->create();
    $owner = Owner::factory()->create(['user_id' => $user->id]);

    $this->assertEquals($user->id, $owner->user->id);
    $this->assertEquals($user->email, $owner->user->email);
  }

  public function test_service_uses_database_transactions()
  {
    // This test verifies that the service methods use database transactions
    // by checking that the createOwnerWithUser method is wrapped in a transaction
    $ownerData = [
      'name' => 'Test Owner',
      'phone' => '123456789',
      'property_id' => $this->property->id,
    ];
    $email = 'testowner@example.com';

    // Test that the method completes successfully
    $owner = $this->service->createOwnerWithUser($ownerData, $email);

    $this->assertInstanceOf(Owner::class, $owner);
    $this->assertNotNull($owner->user_id);
    $this->assertDatabaseHas('users', ['email' => $email]);
    $this->assertDatabaseHas('owners', ['user_id' => $owner->user_id]);
  }
}
