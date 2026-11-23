<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\Product;
use App\Models\Route;
use App\Models\SalesDelivery;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDataCrudCompletionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@erp.com')->first();
        $this->companyA = Company::where('code', 'APEX-IND')->first();
        $this->companyB = Company::where('id', '!=', $this->companyA->id)->first();

        $this->branchA = Branch::where('company_id', $this->companyA->id)->first();
        $this->branchB = Branch::where('company_id', $this->companyB->id)->first();
    }

    protected function actingAsCompanyA()
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->companyA->id,
            'active_branch_id' => $this->branchA?->id,
        ]);
    }

    protected function actingAsCompanyB()
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->companyB->id,
            'active_branch_id' => $this->branchB?->id,
        ]);
    }

    /** @test */
    public function test_item_creation_with_product_selection_and_image_upload()
    {
        Storage::fake('public');

        $product = Product::where('company_id', $this->companyA->id)->first();
        $unit = Unit::where('company_id', $this->companyA->id)->first();
        $imageFile = UploadedFile::fake()->image('test_product.jpg', 400, 400);

        $payload = [
            'name' => 'Industrial Precision Sensor Pro',
            'code' => 'ITM-TEST-IMG-001',
            'product_id' => $product?->id,
            'unit_id' => $unit?->id,
            'item_type' => 'sales_item',
            'min_price' => 1200,
            'normal_price' => 1500,
            'max_price' => 1800,
            'gst_percentage' => 18,
            'reorder_quantity' => 10,
            'status' => 'active',
            'image' => $imageFile,
        ];

        $response = $this->actingAsCompanyA()
            ->post(route('master.items.store'), $payload);

        $response->assertRedirect(route('master.items.index'));
        $response->assertSessionHas('success');

        $item = Item::where('code', 'ITM-TEST-IMG-001')
            ->where('company_id', $this->companyA->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals($product?->id, $item->product_id);
        $this->assertNotNull($item->image);
        Storage::disk('public')->assertExists($item->image);
    }

    /** @test */
    public function test_item_update_image_replacement_and_removal()
    {
        Storage::fake('public');

        $initialImage = UploadedFile::fake()->image('initial.jpg');
        $item = Item::create([
            'company_id' => $this->companyA->id,
            'name' => 'Transformer Core 500kVA',
            'code' => 'ITM-TRANS-500',
            'item_type' => 'both',
            'min_price' => 5000,
            'normal_price' => 6000,
            'max_price' => 7000,
            'gst_percentage' => 18,
            'reorder_quantity' => 5,
            'status' => 'active',
            'image' => $initialImage->store('items', 'public'),
        ]);

        $this->assertNotNull($item->image);
        Storage::disk('public')->assertExists($item->image);
        $oldImagePath = $item->image;

        // Replace with new image
        $replacementImage = UploadedFile::fake()->image('replacement.png');
        $response = $this->actingAsCompanyA()
            ->put(route('master.items.update', $item), [
                'name' => 'Transformer Core 500kVA Upgraded',
                'code' => 'ITM-TRANS-500',
                'item_type' => 'both',
                'min_price' => 5500,
                'normal_price' => 6500,
                'max_price' => 7500,
                'gst_percentage' => 18,
                'reorder_quantity' => 5,
                'status' => 'active',
                'image' => $replacementImage,
            ]);

        $response->assertRedirect(route('master.items.index'));
        $item->refresh();

        $this->assertNotEquals($oldImagePath, $item->image);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($item->image);

        // Remove image completely
        $currentImagePath = $item->image;
        $responseRemove = $this->actingAsCompanyA()
            ->put(route('master.items.update', $item), [
                'name' => 'Transformer Core 500kVA Upgraded',
                'code' => 'ITM-TRANS-500',
                'item_type' => 'both',
                'min_price' => 5500,
                'normal_price' => 6500,
                'max_price' => 7500,
                'gst_percentage' => 18,
                'reorder_quantity' => 5,
                'status' => 'active',
                'remove_image' => '1',
            ]);

        $responseRemove->assertRedirect(route('master.items.index'));
        $item->refresh();

        $this->assertNull($item->image);
        Storage::disk('public')->assertMissing($currentImagePath);
    }

    /** @test */
    public function test_item_views_render_product_selector_and_image_elements()
    {
        $responseCreate = $this->actingAsCompanyA()
            ->get(route('master.items.create'));

        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('multipart/form-data');
        $responseCreate->assertSee('name="product_id"', false);
        $responseCreate->assertSee('name="image"', false);
        $responseCreate->assertSee('id="image-preview"', false);

        $item = Item::where('company_id', $this->companyA->id)->first();
        if ($item) {
            $responseEdit = $this->actingAsCompanyA()
                ->get(route('master.items.edit', $item));

            $responseEdit->assertStatus(200);
            $responseEdit->assertSee('multipart/form-data');
            $responseEdit->assertSee('name="product_id"', false);
            $responseEdit->assertSee('name="image"', false);
        }

        $responseIndex = $this->actingAsCompanyA()
            ->get(route('master.items.index'));

        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Items & Products Catalog');
    }

    /** @test */
    public function test_department_crud_uniqueness_and_deletion_guard()
    {
        // 1. Create Department
        $code = 'DEPT-QA-' . uniqid();
        $response = $this->actingAsCompanyA()
            ->post(route('master.departments.store'), [
                'name' => 'Quality Assurance Lab',
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);

        $response->assertSessionHas('success');
        $dept = Department::where('code', $code)->where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($dept);

        // 2. Uniqueness validation in same company
        $responseDup = $this->actingAsCompanyA()
            ->post(route('master.departments.store'), [
                'name' => 'Quality Assurance Lab Duplicate',
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);
        $responseDup->assertSessionHasErrors(['code']);

        // 3. Update Department
        $responseUpdate = $this->actingAsCompanyA()
            ->put(route('master.departments.update', $dept), [
                'name' => 'Quality Assurance & Testing Division',
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'department_head' => 'Dr. A. Sharma',
                'status' => 'active',
            ]);
        $responseUpdate->assertSessionHas('success');
        $dept->refresh();
        $this->assertEquals('Quality Assurance & Testing Division', $dept->name);

        // 4. Deletion Guard when Staff is linked
        $staff = Staff::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA?->id,
            'department_id' => $dept->id,
            'first_name' => 'QA',
            'last_name' => 'Inspector',
            'employee_id' => 'EMP-QA-' . uniqid(),
            'status' => 'active',
        ]);

        $responseDeleteBlocked = $this->actingAsCompanyA()
            ->delete(route('master.departments.destroy', $dept));

        $responseDeleteBlocked->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);

        // 5. Deletion when Staff removed
        $staff->delete();
        $responseDeleteOk = $this->actingAsCompanyA()
            ->delete(route('master.departments.destroy', $dept));

        $responseDeleteOk->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    /** @test */
    public function test_unit_crud_uniqueness_and_deletion_guard()
    {
        // 1. Create Unit
        $sym = 'MTR-' . substr(uniqid(), 0, 4);
        $response = $this->actingAsCompanyA()
            ->post(route('master.units.store'), [
                'name' => 'Special Metric ' . $sym,
                'symbol' => $sym,
                'unit_type' => 'length',
                'conversion_factor' => 1.0,
                'status' => 'active',
            ]);

        $response->assertSessionHas('success');
        $unit = Unit::where('symbol', $sym)->where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($unit);

        // 2. Uniqueness per company
        $responseDup = $this->actingAsCompanyA()
            ->post(route('master.units.store'), [
                'name' => 'Special Metric ' . $sym,
                'symbol' => $sym,
                'unit_type' => 'length',
                'conversion_factor' => 1.0,
                'status' => 'active',
            ]);
        $responseDup->assertSessionHasErrors(['symbol']);

        // 3. Update Unit
        $responseUpdate = $this->actingAsCompanyA()
            ->put(route('master.units.update', $unit), [
                'name' => 'Updated Metric ' . $sym,
                'symbol' => $sym,
                'unit_type' => 'length',
                'conversion_factor' => 2.5,
                'status' => 'active',
            ]);
        $responseUpdate->assertSessionHas('success');
        $unit->refresh();
        $this->assertEquals(2.5, (float)$unit->conversion_factor);

        // 4. Deletion Guard when Item references Unit
        $item = Item::create([
            'company_id' => $this->companyA->id,
            'name' => 'Metric Cable',
            'code' => 'CBL-' . uniqid(),
            'unit_id' => $unit->id,
            'item_type' => 'sales_item',
            'min_price' => 10,
            'normal_price' => 15,
            'max_price' => 20,
            'gst_percentage' => 18,
            'reorder_quantity' => 1,
            'status' => 'active',
        ]);

        $responseDeleteBlocked = $this->actingAsCompanyA()
            ->delete(route('master.units.destroy', $unit));

        $responseDeleteBlocked->assertSessionHas('error');
        $this->assertDatabaseHas('units', ['id' => $unit->id]);

        // 5. Delete after removing item
        $item->delete();
        $responseDeleteOk = $this->actingAsCompanyA()
            ->delete(route('master.units.destroy', $unit));

        $responseDeleteOk->assertSessionHas('success');
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    /** @test */
    public function test_godown_crud_uniqueness_and_deletion_guard()
    {
        // 1. Create Godown
        $code = 'WH-TST-' . substr(uniqid(), 0, 4);
        $response = $this->actingAsCompanyA()
            ->post(route('master.godowns.store'), [
                'name' => 'Cold Storage Facility ' . $code,
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);

        $response->assertSessionHas('success');
        $godown = Godown::where('code', $code)->where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($godown);

        // 2. Uniqueness
        $responseDup = $this->actingAsCompanyA()
            ->post(route('master.godowns.store'), [
                'name' => 'Cold Storage Duplicate',
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);
        $responseDup->assertSessionHasErrors(['code']);

        // 3. Update Godown
        $responseUpdate = $this->actingAsCompanyA()
            ->put(route('master.godowns.update', $godown), [
                'name' => 'Cold Storage Facility Updated ' . $code,
                'code' => $code,
                'branch_id' => $this->branchA?->id,
                'address' => 'Plot 42 Industrial Area',
                'postal_code' => '560001',
                'status' => 'active',
            ]);
        $responseUpdate->assertSessionHas('success');
        $godown->refresh();
        $this->assertEquals('Plot 42 Industrial Area', $godown->address);

        // 4. Deletion Guard when active StockBatch exists
        $item = Item::where('company_id', $this->companyA->id)->first();
        $batch = StockBatch::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA?->id,
            'godown_id' => $godown->id,
            'item_id' => $item->id,
            'batch_no' => 'BAT-' . uniqid(),
            'quantity' => 50,
            'cost_price' => 100,
            'status' => 'active',
        ]);

        $responseDeleteBlocked = $this->actingAsCompanyA()
            ->delete(route('master.godowns.destroy', $godown));

        $responseDeleteBlocked->assertSessionHas('error');
        $this->assertDatabaseHas('godowns', ['id' => $godown->id]);

        // 5. Delete after removing stock batch
        $batch->delete();
        $responseDeleteOk = $this->actingAsCompanyA()
            ->delete(route('master.godowns.destroy', $godown));

        $responseDeleteOk->assertSessionHas('success');
        $this->assertDatabaseMissing('godowns', ['id' => $godown->id]);
    }

    /** @test */
    public function test_vehicle_crud_uniqueness_and_deletion_guard()
    {
        // 1. Create Vehicle
        $reg = 'KA-01-EXP-' . rand(1000, 9999);
        $response = $this->actingAsCompanyA()
            ->post(route('master.vehicles.store'), [
                'vehicle_name' => 'Eicher Pro Delivery 14ft',
                'registration_number' => $reg,
                'vehicle_type' => 'Tempo',
                'capacity' => 3500,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);

        $response->assertSessionHas('success');
        $vehicle = Vehicle::where('registration_number', $reg)->where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($vehicle);

        // 2. Uniqueness
        $responseDup = $this->actingAsCompanyA()
            ->post(route('master.vehicles.store'), [
                'vehicle_name' => 'Duplicate Vehicle',
                'registration_number' => $reg,
                'vehicle_type' => 'Tempo',
                'capacity' => 3500,
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);
        $responseDup->assertSessionHasErrors(['registration_number']);

        // 3. Update Vehicle
        $responseUpdate = $this->actingAsCompanyA()
            ->put(route('master.vehicles.update', $vehicle), [
                'vehicle_name' => 'Eicher Pro Delivery 14ft Upgraded',
                'registration_number' => $reg,
                'vehicle_type' => 'Tempo',
                'capacity' => 4000,
                'assigned_driver' => 'Ramesh Kumar',
                'branch_id' => $this->branchA?->id,
                'status' => 'active',
            ]);
        $responseUpdate->assertSessionHas('success');
        $vehicle->refresh();
        $this->assertEquals(4000, (float)$vehicle->capacity);
        $this->assertEquals('Ramesh Kumar', $vehicle->assigned_driver);

        // 4. Deletion Guard when Route references Vehicle
        $route = Route::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA?->id,
            'vehicle_id' => $vehicle->id,
            'route_name' => 'Route-Test-' . uniqid(),
            'status' => 'active',
        ]);

        $responseDeleteBlocked = $this->actingAsCompanyA()
            ->delete(route('master.vehicles.destroy', $vehicle));

        $responseDeleteBlocked->assertSessionHas('error');
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);

        // 5. Delete after removing Route
        $route->delete();
        $responseDeleteOk = $this->actingAsCompanyA()
            ->delete(route('master.vehicles.destroy', $vehicle));

        $responseDeleteOk->assertSessionHas('success');
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    /** @test */
    public function test_cross_company_isolation_on_master_data_crud()
    {
        $deptA = Department::where('company_id', $this->companyA->id)->first();
        $godownA = Godown::where('company_id', $this->companyA->id)->first();
        $unitA = Unit::where('company_id', $this->companyA->id)->first();
        $vehicleA = Vehicle::where('company_id', $this->companyA->id)->first();
        $itemA = Item::where('company_id', $this->companyA->id)->first();

        // Company B attempts to update or delete Company A's resources -> 403 Forbidden
        if ($deptA) {
            $this->actingAsCompanyB()
                ->put(route('master.departments.update', $deptA), ['name' => 'Hack', 'code' => 'HACK', 'status' => 'active'])
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->delete(route('master.departments.destroy', $deptA))
                ->assertStatus(403);
        }

        if ($godownA) {
            $this->actingAsCompanyB()
                ->put(route('master.godowns.update', $godownA), ['name' => 'Hack', 'code' => 'HACK', 'status' => 'active'])
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->delete(route('master.godowns.destroy', $godownA))
                ->assertStatus(403);
        }

        if ($unitA) {
            $this->actingAsCompanyB()
                ->put(route('master.units.update', $unitA), ['name' => 'Hack', 'symbol' => 'HK', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active'])
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->delete(route('master.units.destroy', $unitA))
                ->assertStatus(403);
        }

        if ($vehicleA) {
            $this->actingAsCompanyB()
                ->put(route('master.vehicles.update', $vehicleA), ['vehicle_name' => 'Hack', 'registration_number' => 'HK-01-01', 'vehicle_type' => 'Van', 'capacity' => 100, 'status' => 'active'])
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->delete(route('master.vehicles.destroy', $vehicleA))
                ->assertStatus(403);
        }

        if ($itemA) {
            $this->actingAsCompanyB()
                ->get(route('master.items.edit', $itemA))
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->put(route('master.items.update', $itemA), ['name' => 'Hack', 'code' => 'HACK', 'item_type' => 'sales_item', 'min_price' => 1, 'normal_price' => 2, 'max_price' => 3, 'gst_percentage' => 0, 'reorder_quantity' => 1, 'status' => 'active'])
                ->assertStatus(403);

            $this->actingAsCompanyB()
                ->delete(route('master.items.destroy', $itemA))
                ->assertStatus(403);
        }
    }
}
