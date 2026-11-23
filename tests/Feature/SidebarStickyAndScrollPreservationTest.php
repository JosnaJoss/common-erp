<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SidebarStickyAndScrollPreservationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@erp.com')->first();
        $this->company = Company::where('code', 'APEX-IND')->first();
        $this->branch = Branch::where('company_id', $this->company->id)->first();
    }

    protected function actingAsActiveCompany()
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->id,
            'active_branch_id' => $this->branch?->id,
        ]);
    }

    /** @test */
    public function test_sidebar_has_sticky_positioning_and_viewport_height_css()
    {
        $response = $this->actingAsActiveCompany()
            ->get(route('dashboard'));

        $response->assertStatus(200);

        // Verify sticky sidebar CSS rules
        $response->assertSee('position: sticky', false);
        $response->assertSee('top: 0', false);
        $response->assertSee('height: 100vh', false);
        $response->assertSee('align-self: flex-start', false);
        $response->assertSee('overflow-y: auto', false);
        $response->assertSee('overflow-x: hidden', false);

        // Verify responsive mobile overlay CSS
        $response->assertSee('@media (max-width: 767.98px)', false);
        $response->assertSee('position: fixed', false);
    }

    /** @test */
    public function test_sidebar_includes_synchronous_and_deferred_scroll_restoration_scripts()
    {
        $response = $this->actingAsActiveCompany()
            ->get(route('master.vehicles.index'));

        $response->assertStatus(200);

        // Verify immediate synchronous restoration script
        $response->assertSee('sessionStorage.getItem(\'common_erp_sidebar_scroll\')', false);
        $response->assertSee('nav.scrollTop = parseInt(saved, 10)', false);

        // Verify full scroll preservation & visibility handler
        $response->assertSee('restoreSidebarScroll', false);
        $response->assertSee('activeItem.scrollIntoView', false);
        $response->assertSee('sessionStorage.setItem(\'common_erp_sidebar_scroll\'', false);
    }

    /** @test */
    public function test_sidebar_preserves_active_menu_highlighting_across_different_modules()
    {
        // 1. Dashboard
        $dashResponse = $this->actingAsActiveCompany()->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('class="nav-link-custom active"', false);

        // 2. Master Data - Vehicles
        $vehicleResponse = $this->actingAsActiveCompany()->get(route('master.vehicles.index'));
        $vehicleResponse->assertStatus(200);
        $vehicleResponse->assertSee('class="nav-link-custom active"', false);
        $vehicleResponse->assertSee('collapse show', false);

        // 3. Daily Operations
        $opsResponse = $this->actingAsActiveCompany()->get(route('daily_operations.index'));
        $opsResponse->assertStatus(200);
        $opsResponse->assertSee('collapse show', false);

        // 4. Accounts - Ledgers
        $acctResponse = $this->actingAsActiveCompany()->get(route('accounts.ledgers.index'));
        $acctResponse->assertStatus(200);
        $acctResponse->assertSee('collapse show', false);
    }
}
