<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\CrmLead;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\LeaveApplication;
use App\Models\Location;
use App\Models\Overtime;
use App\Models\Product;
use App\Models\Role;
use App\Models\Route;
use App\Models\Shift;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;

class EleganceBoutiqueSeeder extends BaseIndustrySeeder
{
    public function run(): void
    {
        // 1. Company Profile
        $company = $this->initCompany('ELEG-BOUTIQUE', [
            'name' => 'Elegance Boutique',
            'registration_no' => 'U18101MH2021PTC356789',
            'gst_no' => '27AABCE1122F1Z4',
            'pan_no' => 'AABCE1122F',
            'address' => 'Flagship Studio, 24 Turner Road, Bandra West, Mumbai, Maharashtra 400050',
            'phone' => '+91 22 2640 5566',
            'email' => 'contact@eleganceboutique.com',
            'website' => 'https://eleganceboutique.example.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'business_type' => 'retail',
            'status' => 'active',
        ], ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'vehicle_route', 'crm']);

        // 2. Chart of Accounts & Financial Setup
        $ledgers = $this->initChartOfAccounts(
            $company,
            'ICICI Bank Ltd (Current A/c 001105024455)',
            '001105024455',
            'ICIC0000011'
        );

        // 3. Branches
        $branchMumbai = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'ELG-MUM-01'], [
            'name' => 'Bandra Flagship Studio & Boutique',
            'address' => '24 Turner Road, Bandra West',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400050',
            'contact_number' => '+91 22 2640 5567',
            'email' => 'mumbai@eleganceboutique.com',
            'branch_manager' => 'Natasha Singhania',
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        $branchDelhi = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'ELG-DEL-02'], [
            'name' => 'Greater Kailash Luxury Boutique',
            'address' => 'M-Block Market, GK-1',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'postal_code' => '110048',
            'contact_number' => '+91 11 4650 9922',
            'email' => 'delhi@eleganceboutique.com',
            'branch_manager' => 'Rhea Malhotra',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        $branchBangalore = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'ELG-BLR-03'], [
            'name' => 'Indiranagar Lifestyle Gallery',
            'address' => '100 Feet Road, Indiranagar',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'postal_code' => '560038',
            'contact_number' => '+91 80 4120 7711',
            'email' => 'bangalore@eleganceboutique.com',
            'branch_manager' => 'Arunachalam Pillai',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        // 4. Godowns / Stockrooms
        $godownBandraStock = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-ELG-MUM-STK'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Bandra Main Stockroom & Retail Display',
            'address' => 'Ground Floor, Turner Road Studio',
            'postal_code' => '400050',
            'responsible_staff' => 'Suman Roy',
            'status' => 'active',
        ]);

        $godownBandraVault = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-ELG-MUM-VLT'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Bridal & Haute Couture Vault',
            'address' => '1st Floor Studio, Bandra',
            'postal_code' => '400050',
            'responsible_staff' => 'Natasha Singhania',
            'status' => 'active',
        ]);

        $godownDelhi = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-ELG-DEL-01'], [
            'branch_id' => $branchDelhi->id,
            'name' => 'Delhi GK Storage & Display',
            'address' => 'M-Block, GK-1',
            'postal_code' => '110048',
            'responsible_staff' => 'Kunal Bhasin',
            'status' => 'active',
        ]);

        $godownBangalore = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-ELG-BLR-01'], [
            'branch_id' => $branchBangalore->id,
            'name' => 'Bangalore Gallery Stockroom',
            'address' => 'Indiranagar Gallery',
            'postal_code' => '560038',
            'responsible_staff' => 'Sneha Hegde',
            'status' => 'active',
        ]);

        // 5. Departments
        $deptSales = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-ELG-SAL'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Retail Sales & Client Styling',
            'department_head' => 'Natasha Singhania',
            'status' => 'active',
        ]);

        $deptProc = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-ELG-PROC'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Fashion Merchandising & Sourcing',
            'department_head' => 'Kareena Lalwani',
            'status' => 'active',
        ]);

        $deptTailor = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-ELG-TLR'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Boutique Alterations & Tailoring',
            'department_head' => 'Master Usman Khan',
            'status' => 'active',
        ]);

        $deptAcc = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-ELG-ACC'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Boutique Accounts & Cashiering',
            'department_head' => 'Varun Chokshi',
            'status' => 'active',
        ]);

        $deptHR = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-ELG-HR'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Store Operations & HR',
            'department_head' => 'Pooja Bhatt',
            'status' => 'active',
        ]);

        // 6. Territory, Cities, Locations & Vehicles
        $cityMumbai = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Mumbai'], [
            'branch_id' => $branchMumbai->id,
            'status' => 'active',
        ]);

        $cityDelhi = City::firstOrCreate(['company_id' => $company->id, 'name' => 'New Delhi'], [
            'branch_id' => $branchDelhi->id,
            'status' => 'active',
        ]);

        $cityBangalore = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Bangalore'], [
            'branch_id' => $branchBangalore->id,
            'status' => 'active',
        ]);

        $locBandra = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityMumbai->id, 'name' => 'Bandra West'], [
            'pincode' => '400050', 'status' => 'active',
        ]);
        $locJuhu = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityMumbai->id, 'name' => 'Juhu Tara'], [
            'pincode' => '400049', 'status' => 'active',
        ]);
        $locGK = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityDelhi->id, 'name' => 'Greater Kailash 1'], [
            'pincode' => '110048', 'status' => 'active',
        ]);
        $locIndira = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityBangalore->id, 'name' => 'Indiranagar'], [
            'pincode' => '560038', 'status' => 'active',
        ]);

        $vehicle1 = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'MH-02-EL-1100'], [
            'branch_id' => $branchMumbai->id,
            'vehicle_name' => 'Force Traveller Boutique Van',
            'vehicle_type' => 'Van',
            'capacity' => 1200.00,
            'assigned_driver' => 'Sunil Gaikwad',
            'status' => 'active',
        ]);

        $route1 = Route::firstOrCreate(['company_id' => $company->id, 'route_name' => 'South & West Mumbai Luxury Route'], [
            'branch_id' => $branchMumbai->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_driver' => 'Sunil Gaikwad',
            'status' => 'active',
        ]);
        $route1->cities()->syncWithoutDetaching([$cityMumbai->id]);
        $route1->locations()->syncWithoutDetaching([$locBandra->id, $locJuhu->id]);

        // 7. Units of Measure
        $uPcs = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'PCS'], [
            'name' => 'Pieces', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uSet = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'SET'], [
            'name' => 'Complete Set / Ensemble', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uMtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'MTR'], [
            'name' => 'Meters (Fabric)', 'unit_type' => 'length', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uBox = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'BOX'], [
            'name' => 'Box (Pack of 5)', 'unit_type' => 'quantity', 'conversion_factor' => 5, 'status' => 'active',
        ]);

        // 8. Categories & Subcategories
        $catSarees = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Heritage Sarees'], [
            'description' => 'Pure silk, handloom, organza, and festive sarees', 'status' => 'active',
        ]);
        $subSilk = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catSarees->id, 'name' => 'Pure Silk & Zari'], [
            'description' => 'Kanjeevaram, Banarasi, and Chanderi silks', 'status' => 'active',
        ]);
        $subOrganza = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catSarees->id, 'name' => 'Organza & Tussar'], [
            'description' => 'Embroidered organza and hand-painted tussar', 'status' => 'active',
        ]);

        $catKurtis = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Designer Kurtis & Kurtas'], [
            'description' => 'Casual, everyday, and formal handcrafted kurtis', 'status' => 'active',
        ]);
        $subCottonKurti = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catKurtis->id, 'name' => 'Pure Cotton Kurtis'], [
            'description' => 'Breathable floral and indigo block prints', 'status' => 'active',
        ]);
        $subAnarkali = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catKurtis->id, 'name' => 'Festive Anarkali Sets'], [
            'description' => 'Floor-length flared embroidered anarkali ensembles', 'status' => 'active',
        ]);

        $catDresses = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Women Designer Dresses'], [
            'description' => 'Western evening gowns, party wear, and maxi dresses', 'status' => 'active',
        ]);
        $subEvening = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catDresses->id, 'name' => 'Evening Dresses & Gowns'], [
            'description' => 'Georgette, velvet, and cape gowns', 'status' => 'active',
        ]);

        $catMens = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Mens Luxury Wear'], [
            'description' => 'Tailored formal shirts, linen shirts, and trousers', 'status' => 'active',
        ]);
        $subShirts = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catMens->id, 'name' => 'Formal & Linen Shirts'], [
            'description' => 'Egyptian cotton formal and pure linen casual shirts', 'status' => 'active',
        ]);
        $subTrousers = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catMens->id, 'name' => 'Chinos & Trousers'], [
            'description' => 'Slim fit tailored chinos and wool-blend trousers', 'status' => 'active',
        ]);

        $catBridal = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Bridal & Occasion Wear'], [
            'description' => 'Handcrafted wedding lehengas and accessories', 'status' => 'active',
        ]);
        $subLehenga = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBridal->id, 'name' => 'Bridal Lehengas'], [
            'description' => 'Zari, zardozi, and mirror-work lehenga sets', 'status' => 'active',
        ]);
        $subDupatta = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBridal->id, 'name' => 'Luxury Shawls & Dupattas'], [
            'description' => 'Pashmina shawls and bandhani silk dupattas', 'status' => 'active',
        ]);

        // 9. Products & Items (25 Items across clothing styles, sizes & colours)
        $itemsCatalog = [];

        // Cotton Kurtis (Sizes M, L, XL in Pastel Floral and Indigo)
        $pKurtiFloral = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KRT-FLR'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id,
            'name' => 'Elegance Handcrafted Cotton Kurti - Pastel Floral',
            'description' => '100% fine mulmul cotton with hand-block botanical motifs',
            'status' => 'active',
        ]);
        $itemsCatalog['kurti_floral_m'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-KRT-FLR-M'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id, 'product_id' => $pKurtiFloral->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Kurti - Pastel Floral (Size M)', 'item_type' => 'sales_item', 'weight' => 0.35,
            'min_price' => 1800.00, 'normal_price' => 2200.00, 'max_price' => 2500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 10, 'status' => 'active',
        ]);
        $itemsCatalog['kurti_floral_l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-KRT-FLR-L'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id, 'product_id' => $pKurtiFloral->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Kurti - Pastel Floral (Size L)', 'item_type' => 'sales_item', 'weight' => 0.38,
            'min_price' => 1800.00, 'normal_price' => 2200.00, 'max_price' => 2500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 10, 'status' => 'active',
        ]);
        $itemsCatalog['kurti_floral_xl'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-KRT-FLR-XL'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id, 'product_id' => $pKurtiFloral->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Kurti - Pastel Floral (Size XL)', 'item_type' => 'sales_item', 'weight' => 0.40,
            'min_price' => 1900.00, 'normal_price' => 2300.00, 'max_price' => 2600.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 8, 'status' => 'active',
        ]);

        $pKurtiIndigo = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KRT-IND'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id,
            'name' => 'Elegance Indigo Dabu Print Cotton Kurti',
            'description' => 'Natural dyed indigo dabu resist block print kurti',
            'status' => 'active',
        ]);
        $itemsCatalog['kurti_indigo_m'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-KRT-IND-M'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id, 'product_id' => $pKurtiIndigo->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Kurti - Indigo Print (Size M)', 'item_type' => 'sales_item', 'weight' => 0.35,
            'min_price' => 1950.00, 'normal_price' => 2400.00, 'max_price' => 2700.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 10, 'status' => 'active',
        ]);
        $itemsCatalog['kurti_indigo_l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-KRT-IND-L'], [
            'category_id' => $catKurtis->id, 'subcategory_id' => $subCottonKurti->id, 'product_id' => $pKurtiIndigo->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Kurti - Indigo Print (Size L)', 'item_type' => 'sales_item', 'weight' => 0.38,
            'min_price' => 1950.00, 'normal_price' => 2400.00, 'max_price' => 2700.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 10, 'status' => 'active',
        ]);

        // Silk Sarees (Red, Blue, Green, Gold, Pink)
        $pSareeKanj = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SAR-KAN'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id,
            'name' => 'Kanjeevaram Bridal Pure Mulberry Silk Saree',
            'description' => 'Heavy gold zari border traditional bridal Kanjeevaram silk saree',
            'status' => 'active',
        ]);
        $itemsCatalog['saree_kanj_red'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SAR-KAN-RED'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id, 'product_id' => $pSareeKanj->id, 'unit_id' => $uPcs->id,
            'name' => 'Silk Saree - Kanjeevaram Crimson Red', 'item_type' => 'sales_item', 'weight' => 0.85,
            'min_price' => 18000.00, 'normal_price' => 22500.00, 'max_price' => 26000.00, 'gst_percentage' => 12.00, 'hsn_code' => '5007', 'reorder_quantity' => 5, 'status' => 'active',
        ]);

        $pSareeBanarasi = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SAR-BAN'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id,
            'name' => 'Banarasi Katan Silk Floral Jaal Saree',
            'description' => 'Pure Katan silk woven with silver and gold zari jaal motifs',
            'status' => 'active',
        ]);
        $itemsCatalog['saree_ban_blue'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SAR-BAN-BLU'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id, 'product_id' => $pSareeBanarasi->id, 'unit_id' => $uPcs->id,
            'name' => 'Silk Saree - Banarasi Sapphire Blue', 'item_type' => 'sales_item', 'weight' => 0.75,
            'min_price' => 15000.00, 'normal_price' => 19500.00, 'max_price' => 22000.00, 'gst_percentage' => 12.00, 'hsn_code' => '5007', 'reorder_quantity' => 5, 'status' => 'active',
        ]);

        $pSareeChanderi = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SAR-CHN'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id,
            'name' => 'Chanderi Handloom Silk Cotton Saree',
            'description' => 'Lightweight handwoven Chanderi with ashrafi butis',
            'status' => 'active',
        ]);
        $itemsCatalog['saree_chn_green'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SAR-CHN-GRN'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subSilk->id, 'product_id' => $pSareeChanderi->id, 'unit_id' => $uPcs->id,
            'name' => 'Silk Saree - Chanderi Emerald Green', 'item_type' => 'sales_item', 'weight' => 0.50,
            'min_price' => 7500.00, 'normal_price' => 9500.00, 'max_price' => 11000.00, 'gst_percentage' => 12.00, 'hsn_code' => '5007', 'reorder_quantity' => 6, 'status' => 'active',
        ]);

        $pSareeTussar = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SAR-TUS'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subOrganza->id,
            'name' => 'Tussar Hand-Painted Kalamkari Saree',
            'description' => 'Authentic natural tussar silk with hand painted mythological art',
            'status' => 'active',
        ]);
        $itemsCatalog['saree_tus_gold'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SAR-TUS-GLD'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subOrganza->id, 'product_id' => $pSareeTussar->id, 'unit_id' => $uPcs->id,
            'name' => 'Tussar Silk Saree - Muted Gold', 'item_type' => 'sales_item', 'weight' => 0.60,
            'min_price' => 11000.00, 'normal_price' => 13500.00, 'max_price' => 15500.00, 'gst_percentage' => 12.00, 'hsn_code' => '5007', 'reorder_quantity' => 4, 'status' => 'active',
        ]);

        $pSareeOrganza = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SAR-ORG'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subOrganza->id,
            'name' => 'Sheer Organza Cutwork Saree',
            'description' => 'Pastel blush organza with scalloped cutwork and resham embroidery',
            'status' => 'active',
        ]);
        $itemsCatalog['saree_org_pink'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SAR-ORG-PNK'], [
            'category_id' => $catSarees->id, 'subcategory_id' => $subOrganza->id, 'product_id' => $pSareeOrganza->id, 'unit_id' => $uPcs->id,
            'name' => 'Organza Saree - Blush Rose Pink', 'item_type' => 'sales_item', 'weight' => 0.45,
            'min_price' => 8500.00, 'normal_price' => 11000.00, 'max_price' => 13000.00, 'gst_percentage' => 12.00, 'hsn_code' => '5007', 'reorder_quantity' => 5, 'status' => 'active',
        ]);

        // Designer Dresses & Gowns
        $pDressMaxi = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-DRS-FLR'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id,
            'name' => 'Women Designer Tiered Flared Maxi Dress',
            'description' => 'Georgette tiered evening dress with ruffled neckline',
            'status' => 'active',
        ]);
        $itemsCatalog['dress_maxi_wine'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-DRS-FLR-WIN'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id, 'product_id' => $pDressMaxi->id, 'unit_id' => $uPcs->id,
            'name' => 'Designer Maxi Dress - Ruby Wine', 'item_type' => 'sales_item', 'weight' => 0.65,
            'min_price' => 4800.00, 'normal_price' => 6200.00, 'max_price' => 7000.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 6, 'status' => 'active',
        ]);

        $pDressEvening = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-DRS-EVN'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id,
            'name' => 'A-Line Georgette Cocktail Evening Gown',
            'description' => 'Contemporary evening gown with hand-embellished crystal waist belt',
            'status' => 'active',
        ]);
        $itemsCatalog['dress_evn_black'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-DRS-EVN-BLK'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id, 'product_id' => $pDressEvening->id, 'unit_id' => $uPcs->id,
            'name' => 'Evening Dress - Midnight Black', 'item_type' => 'sales_item', 'weight' => 0.70,
            'min_price' => 6500.00, 'normal_price' => 8400.00, 'max_price' => 9500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 5, 'status' => 'active',
        ]);

        $pGownCape = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-GWN-CP'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id,
            'name' => 'Indo-Western Embroidered Cape Gown',
            'description' => 'Georgette pre-draped gown paired with detachable embroidered cape',
            'status' => 'active',
        ]);
        $itemsCatalog['gown_cape_beige'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-GWN-CP-BEG'], [
            'category_id' => $catDresses->id, 'subcategory_id' => $subEvening->id, 'product_id' => $pGownCape->id, 'unit_id' => $uSet->id,
            'name' => 'Indo-Western Cape Gown - Champagne Beige', 'item_type' => 'sales_item', 'weight' => 1.10,
            'min_price' => 9500.00, 'normal_price' => 12500.00, 'max_price' => 14500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 4, 'status' => 'active',
        ]);

        // Men's Wear (Shirts & Trousers)
        $pShirtFormal = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SHT-FRM'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id,
            'name' => 'Mens Egyptian Giza Cotton Formal Shirt',
            'description' => '120s 2-ply long-staple Egyptian cotton tailored business shirt',
            'status' => 'active',
        ]);
        $itemsCatalog['shirt_wht_40'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SHT-WHT-40'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id, 'product_id' => $pShirtFormal->id, 'unit_id' => $uPcs->id,
            'name' => 'Formal Shirt - Crisp White (Size 40)', 'item_type' => 'sales_item', 'weight' => 0.30,
            'min_price' => 2200.00, 'normal_price' => 2800.00, 'max_price' => 3200.00, 'gst_percentage' => 12.00, 'hsn_code' => '6205', 'reorder_quantity' => 10, 'status' => 'active',
        ]);
        $itemsCatalog['shirt_wht_42'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SHT-WHT-42'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id, 'product_id' => $pShirtFormal->id, 'unit_id' => $uPcs->id,
            'name' => 'Formal Shirt - Crisp White (Size 42)', 'item_type' => 'sales_item', 'weight' => 0.32,
            'min_price' => 2200.00, 'normal_price' => 2800.00, 'max_price' => 3200.00, 'gst_percentage' => 12.00, 'hsn_code' => '6205', 'reorder_quantity' => 10, 'status' => 'active',
        ]);
        $itemsCatalog['shirt_blu_40'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SHT-BLU-40'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id, 'product_id' => $pShirtFormal->id, 'unit_id' => $uPcs->id,
            'name' => 'Formal Shirt - Sky Blue Twill (Size 40)', 'item_type' => 'sales_item', 'weight' => 0.30,
            'min_price' => 2200.00, 'normal_price' => 2800.00, 'max_price' => 3200.00, 'gst_percentage' => 12.00, 'hsn_code' => '6205', 'reorder_quantity' => 10, 'status' => 'active',
        ]);

        $pShirtLinen = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-SHT-LIN'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id,
            'name' => 'Mens 100% Pure European Flax Linen Shirt',
            'description' => 'Breathable casual spread-collar garment-washed linen shirt',
            'status' => 'active',
        ]);
        $itemsCatalog['shirt_lin_olv'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SHT-LIN-OLV'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id, 'product_id' => $pShirtLinen->id, 'unit_id' => $uPcs->id,
            'name' => 'Pure Linen Shirt - Olive Green (Size 42)', 'item_type' => 'sales_item', 'weight' => 0.32,
            'min_price' => 2600.00, 'normal_price' => 3400.00, 'max_price' => 3800.00, 'gst_percentage' => 12.00, 'hsn_code' => '6205', 'reorder_quantity' => 8, 'status' => 'active',
        ]);
        $itemsCatalog['shirt_lin_khk'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-SHT-LIN-KHK'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subShirts->id, 'product_id' => $pShirtLinen->id, 'unit_id' => $uPcs->id,
            'name' => 'Pure Linen Shirt - Natural Khaki (Size 40)', 'item_type' => 'sales_item', 'weight' => 0.30,
            'min_price' => 2600.00, 'normal_price' => 3400.00, 'max_price' => 3800.00, 'gst_percentage' => 12.00, 'hsn_code' => '6205', 'reorder_quantity' => 8, 'status' => 'active',
        ]);

        $pChino = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-TRS-CHN'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subTrousers->id,
            'name' => 'Mens Stretch Cotton Tailored Chinos',
            'description' => 'Premium 4-way stretch twill cotton chinos',
            'status' => 'active',
        ]);
        $itemsCatalog['chino_nav_32'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-TRS-CHN-NAV'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subTrousers->id, 'product_id' => $pChino->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Chinos - Classic Navy (Size 32)', 'item_type' => 'sales_item', 'weight' => 0.48,
            'min_price' => 2400.00, 'normal_price' => 3100.00, 'max_price' => 3500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6203', 'reorder_quantity' => 8, 'status' => 'active',
        ]);
        $itemsCatalog['chino_gry_34'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-TRS-CHN-GRY'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subTrousers->id, 'product_id' => $pChino->id, 'unit_id' => $uPcs->id,
            'name' => 'Cotton Chinos - Charcoal Grey (Size 34)', 'item_type' => 'sales_item', 'weight' => 0.50,
            'min_price' => 2400.00, 'normal_price' => 3100.00, 'max_price' => 3500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6203', 'reorder_quantity' => 8, 'status' => 'active',
        ]);

        $pTrouserWool = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-TRS-WOL'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subTrousers->id,
            'name' => 'Mens Super 110s Wool-Blend Formal Trousers',
            'description' => 'Custom drape Italian wool blend business formal trousers',
            'status' => 'active',
        ]);
        $itemsCatalog['trouser_wol_blk'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-TRS-WOL-BLK'], [
            'category_id' => $catMens->id, 'subcategory_id' => $subTrousers->id, 'product_id' => $pTrouserWool->id, 'unit_id' => $uPcs->id,
            'name' => 'Formal Wool Trousers - Deep Black (Size 32)', 'item_type' => 'sales_item', 'weight' => 0.52,
            'min_price' => 3200.00, 'normal_price' => 4200.00, 'max_price' => 4800.00, 'gst_percentage' => 12.00, 'hsn_code' => '6203', 'reorder_quantity' => 6, 'status' => 'active',
        ]);

        // Bridal Lehengas & Accessories
        $pLehenga = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-LHG-BRD'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subLehenga->id,
            'name' => 'Raw Silk Bridal Zardozi Lehenga Set',
            'description' => 'Handcrafted pure raw silk lehenga with matching blouse and dual net dupattas',
            'status' => 'active',
        ]);
        $itemsCatalog['lehenga_maroon'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-LHG-ZAR-MRN'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subLehenga->id, 'product_id' => $pLehenga->id, 'unit_id' => $uSet->id,
            'name' => 'Bridal Lehenga Set - Imperial Maroon', 'item_type' => 'sales_item', 'weight' => 3.50,
            'min_price' => 38000.00, 'normal_price' => 48000.00, 'max_price' => 55000.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 3, 'status' => 'active',
        ]);
        $itemsCatalog['lehenga_mint'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-LHG-MIR-MNT'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subLehenga->id, 'product_id' => $pLehenga->id, 'unit_id' => $uSet->id,
            'name' => 'Party Lehenga Set - Mint Green Mirror Work', 'item_type' => 'sales_item', 'weight' => 2.80,
            'min_price' => 24000.00, 'normal_price' => 32000.00, 'max_price' => 36000.00, 'gst_percentage' => 12.00, 'hsn_code' => '6204', 'reorder_quantity' => 3, 'status' => 'active',
        ]);

        $pPashmina = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-ACC-PAS'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subDupatta->id,
            'name' => 'Kashmiri Hand-Spun Pure Pashmina Shawl',
            'description' => 'Authentic GI certified Cashmere Pashmina with needle embroidery border',
            'status' => 'active',
        ]);
        $itemsCatalog['pashmina_ivory'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ACC-PAS-IVO'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subDupatta->id, 'product_id' => $pPashmina->id, 'unit_id' => $uPcs->id,
            'name' => 'Hand-Woven Pashmina Shawl - Ivory', 'item_type' => 'sales_item', 'weight' => 0.25,
            'min_price' => 12000.00, 'normal_price' => 16500.00, 'max_price' => 19000.00, 'gst_percentage' => 12.00, 'hsn_code' => '6214', 'reorder_quantity' => 4, 'status' => 'active',
        ]);

        $pDupatta = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-ACC-DUP'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subDupatta->id,
            'name' => 'Heritage Bandhani Georgette Silk Dupatta',
            'description' => 'Traditional handcrafted tie-dye Bandhej dupatta with kundan border',
            'status' => 'active',
        ]);
        $itemsCatalog['dupatta_mustard'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ACC-DUP-MUS'], [
            'category_id' => $catBridal->id, 'subcategory_id' => $subDupatta->id, 'product_id' => $pDupatta->id, 'unit_id' => $uPcs->id,
            'name' => 'Designer Bandhani Dupatta - Mustard Gold', 'item_type' => 'sales_item', 'weight' => 0.30,
            'min_price' => 3500.00, 'normal_price' => 4800.00, 'max_price' => 5500.00, 'gst_percentage' => 12.00, 'hsn_code' => '6214', 'reorder_quantity' => 6, 'status' => 'active',
        ]);

        // 10. Suppliers (6 Apparel & Fabric Weavers / Mills)
        $suppliers = [
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-SURAT', 'name' => 'Surat Silk Weavers & Mills Pvt Ltd',
                'contact_person' => 'Jignesh Zariwala', 'phone' => '+91 261 245 8899', 'email' => 'sales@suratsilk.example.com',
                'address' => 'Ring Road Textile Market, Surat, Gujarat 395002', 'pincode' => '395002', 'city' => 'Surat',
                'gst_number' => '24AABCS8899S1Z2', 'pan_number' => 'AABCS8899S', 'opening_balance' => 45000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-RAYMOND', 'name' => 'Raymond Apparel & Shirting Ltd',
                'contact_person' => 'Vivek Bhatia', 'phone' => '+91 22 4036 7000', 'email' => 'b2b@raymond.example.com',
                'address' => 'Pokhran Road No 1, Jekegram, Thane West 400606', 'pincode' => '400606', 'city' => 'Thane',
                'gst_number' => '27AAACR1234R1Z1', 'pan_number' => 'AAACR1234R', 'opening_balance' => 38000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-JAIPUR', 'name' => 'Jaipur Royal Handlooms & Prints',
                'contact_person' => 'Mahendra Singh Sanganeri', 'phone' => '+91 141 278 1122', 'email' => 'orders@jaipurprints.example.com',
                'address' => 'Sanganer Industrial Craft Area, Jaipur, Rajasthan 302029', 'pincode' => '302029', 'city' => 'Jaipur',
                'gst_number' => '08AABCJ4455J1Z3', 'pan_number' => 'AABCJ4455J', 'opening_balance' => 22000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-TIRUPUR', 'name' => 'Tirupur Knitwear & Cotton Exports',
                'contact_person' => 'P. Sundaram', 'phone' => '+91 421 224 8833', 'email' => 'exports@tirupurknit.example.com',
                'address' => 'Avinashi Road, Tirupur, Tamil Nadu 641602', 'pincode' => '641602', 'city' => 'Tirupur',
                'gst_number' => '33AAACT9988T1Z8', 'pan_number' => 'AAACT9988T', 'opening_balance' => 15000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-VARANASI', 'name' => 'Varanasi Zari & Brocade Artisans',
                'contact_person' => 'Mukhtar Ansari Weavers', 'phone' => '+91 542 238 9011', 'email' => 'varanasi.zari@example.com',
                'address' => 'Chowk Silk Mandi, Varanasi, Uttar Pradesh 221001', 'pincode' => '221001', 'city' => 'Varanasi',
                'gst_number' => '09AABCV7766V1Z5', 'pan_number' => 'AABCV7766V', 'opening_balance' => 50000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-ELG-ARVIND', 'name' => 'Arvind Lifestyle Fabrics & Textiles',
                'contact_person' => 'Siddharth Dave', 'phone' => '+91 79 6676 4000', 'email' => 'info@arvindtextiles.example.com',
                'address' => 'Naroda Road, Ahmedabad, Gujarat 380025', 'pincode' => '380025', 'city' => 'Ahmedabad',
                'gst_number' => '24AAACA3322A1Z9', 'pan_number' => 'AAACA3322A', 'opening_balance' => 30000.00,
            ]),
        ];

        // 11. Customers (12 Boutique Shoppers & Stylists)
        $customers = [
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-001', 'name' => 'Priya Singhania', 'shop_name' => 'Singhania Wardrobe',
                'phone' => '+91 9820112233', 'email' => 'priya.s@example.com', 'address' => 'Altamount Road, Cumballa Hill, Mumbai',
                'pincode' => '400026', 'city' => 'Mumbai', 'territory' => 'South Mumbai', 'route_id' => $route1->id, 'location_id' => $locBandra->id,
                'gst_number' => '27ABCDS9988S1Z0', 'pan_number' => 'ABCDS9988S', 'price_level' => 'normal', 'opening_balance' => 45000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-002', 'name' => 'Ananya Kapoor', 'shop_name' => 'Kapoor Bridal Registry',
                'phone' => '+91 9820223344', 'email' => 'ananya.k@example.com', 'address' => 'Juhu Tara Road, Juhu, Mumbai',
                'pincode' => '400049', 'city' => 'Mumbai', 'territory' => 'Western Suburbs', 'route_id' => $route1->id, 'location_id' => $locJuhu->id,
                'price_level' => 'maximum', 'opening_balance' => 65000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-003', 'name' => 'Vikramaditya Oberoi', 'shop_name' => 'Oberoi Bespoke Wardrobe',
                'phone' => '+91 9811334455', 'email' => 'vikram.o@example.com', 'address' => 'Panchsheel Park, New Delhi',
                'pincode' => '110017', 'city' => 'New Delhi', 'price_level' => 'normal', 'opening_balance' => 30000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-004', 'name' => 'Neha Sharma Stylist', 'shop_name' => 'Studio Neha Sharma',
                'phone' => '+91 9820445566', 'email' => 'neha.styling@example.com', 'address' => 'Bandra Kurla Complex, Mumbai',
                'pincode' => '400051', 'city' => 'Mumbai', 'price_level' => 'minimum', 'opening_balance' => 55000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-005', 'name' => 'Ritu Dalmia Events', 'shop_name' => 'Dalmia Destination Weddings',
                'phone' => '+91 9810556677', 'email' => 'events@dalmia.example.com', 'address' => 'Friends Colony West, New Delhi',
                'pincode' => '110065', 'city' => 'New Delhi', 'price_level' => 'minimum', 'opening_balance' => 90000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-006', 'name' => 'Meera Nambiar', 'shop_name' => 'Meera Silk Connoisseur',
                'phone' => '+91 9845667788', 'email' => 'meera.n@example.com', 'address' => 'Lavelle Road, Bangalore',
                'pincode' => '560001', 'city' => 'Bangalore', 'price_level' => 'normal', 'opening_balance' => 40000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-007', 'name' => 'Siddharth Malhotra', 'shop_name' => 'SM Sartorial Studio',
                'phone' => '+91 9820778899', 'email' => 'siddharth.m@example.com', 'address' => 'Pali Hill, Bandra West, Mumbai',
                'pincode' => '400050', 'city' => 'Mumbai', 'price_level' => 'normal', 'opening_balance' => 25000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-008', 'name' => 'Shalini Deshmukh', 'shop_name' => 'Deshmukh Family Wardrobe',
                'phone' => '+91 9820889900', 'email' => 'shalini.d@example.com', 'address' => 'Prabhat Road, Pune',
                'pincode' => '411004', 'city' => 'Pune', 'price_level' => 'normal', 'opening_balance' => 32000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-009', 'name' => 'Tara Mehta Wedding Planning', 'shop_name' => 'Tara Luxe Weddings',
                'phone' => '+91 9811990011', 'email' => 'tara@luxeweddings.example.com', 'address' => 'Golf Links, New Delhi',
                'pincode' => '110003', 'city' => 'New Delhi', 'price_level' => 'minimum', 'opening_balance' => 80000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-010', 'name' => 'Rohan Varma', 'shop_name' => 'Varma Corporate Apparel',
                'phone' => '+91 9845001122', 'email' => 'rohan.v@example.com', 'address' => 'Koramangala 4th Block, Bangalore',
                'pincode' => '560034', 'city' => 'Bangalore', 'price_level' => 'normal', 'opening_balance' => 18000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-011', 'name' => 'Kavita Rao', 'shop_name' => 'Rao Heritage Collections',
                'phone' => '+91 9845112233', 'email' => 'kavita.rao@example.com', 'address' => 'Sadashivnagar, Bangalore',
                'pincode' => '560080', 'city' => 'Bangalore', 'price_level' => 'normal', 'opening_balance' => 28000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-ELG-012', 'name' => 'Dr. Suniti Patel', 'shop_name' => 'Patel Designer Wardrobe',
                'phone' => '+91 9820223355', 'email' => 'suniti.patel@example.com', 'address' => 'Worli Seaface, Mumbai',
                'pincode' => '400030', 'city' => 'Mumbai', 'price_level' => 'normal', 'opening_balance' => 22000.00,
            ]),
        ];

        // 12. Staff & HR (16 Employees across 3 stores)
        $staffRoster = [
            ['first_name' => 'Natasha', 'last_name' => 'Singhania', 'designation' => 'Boutique General Manager', 'dept' => $deptSales, 'salary_amount' => 75000.00, 'emp_id' => 'EMP-ELG-001', 'gender' => 'Female', 'branch' => $branchMumbai],
            ['first_name' => 'Rhea', 'last_name' => 'Malhotra', 'designation' => 'Delhi Store Manager', 'dept' => $deptSales, 'salary_amount' => 70000.00, 'emp_id' => 'EMP-ELG-002', 'gender' => 'Female', 'branch' => $branchDelhi],
            ['first_name' => 'Arunachalam', 'last_name' => 'Pillai', 'designation' => 'Bangalore Gallery Manager', 'dept' => $deptSales, 'salary_amount' => 68000.00, 'emp_id' => 'EMP-ELG-003', 'gender' => 'Male', 'branch' => $branchBangalore],
            ['first_name' => 'Kareena', 'last_name' => 'Lalwani', 'designation' => 'Lead Fashion Merchandiser', 'dept' => $deptProc, 'salary_amount' => 65000.00, 'emp_id' => 'EMP-ELG-004', 'gender' => 'Female', 'branch' => $branchMumbai],
            ['first_name' => 'Usman', 'last_name' => 'Khan', 'designation' => 'Master Tailor & Fitter', 'dept' => $deptTailor, 'salary_amount' => 52000.00, 'emp_id' => 'EMP-ELG-005', 'gender' => 'Male', 'branch' => $branchMumbai],
            ['first_name' => 'Varun', 'last_name' => 'Chokshi', 'designation' => 'Senior Boutique Accountant', 'dept' => $deptAcc, 'salary_amount' => 60000.00, 'emp_id' => 'EMP-ELG-006', 'gender' => 'Male', 'branch' => $branchMumbai],
            ['first_name' => 'Pooja', 'last_name' => 'Bhatt', 'designation' => 'HR & Operations Executive', 'dept' => $deptHR, 'salary_amount' => 48000.00, 'emp_id' => 'EMP-ELG-007', 'gender' => 'Female', 'branch' => $branchMumbai],
            ['first_name' => 'Suman', 'last_name' => 'Roy', 'designation' => 'Inventory & Storage Lead', 'dept' => $deptProc, 'salary_amount' => 42000.00, 'emp_id' => 'EMP-ELG-008', 'gender' => 'Male', 'branch' => $branchMumbai],
            ['first_name' => 'Kunal', 'last_name' => 'Bhasin', 'designation' => 'Delhi Assistant Store Lead', 'dept' => $deptSales, 'salary_amount' => 45000.00, 'emp_id' => 'EMP-ELG-009', 'gender' => 'Male', 'branch' => $branchDelhi],
            ['first_name' => 'Sneha', 'last_name' => 'Hegde', 'designation' => 'Bangalore Senior Stylist', 'dept' => $deptSales, 'salary_amount' => 46000.00, 'emp_id' => 'EMP-ELG-010', 'gender' => 'Female', 'branch' => $branchBangalore],
            ['first_name' => 'Ankita', 'last_name' => 'Joshi', 'designation' => 'Bridal Fashion Consultant', 'dept' => $deptSales, 'salary_amount' => 45000.00, 'emp_id' => 'EMP-ELG-011', 'gender' => 'Female', 'branch' => $branchMumbai],
            ['first_name' => 'Sohail', 'last_name' => 'Shaikh', 'designation' => 'Alteration & Seam Technician', 'dept' => $deptTailor, 'salary_amount' => 35000.00, 'emp_id' => 'EMP-ELG-012', 'gender' => 'Male', 'branch' => $branchMumbai],
            ['first_name' => 'Deepika', 'last_name' => 'Nair', 'designation' => 'Boutique POS Cashier', 'dept' => $deptAcc, 'salary_amount' => 34000.00, 'emp_id' => 'EMP-ELG-013', 'gender' => 'Female', 'branch' => $branchMumbai],
            ['first_name' => 'Sunil', 'last_name' => 'Gaikwad', 'designation' => 'Logistics & Van Driver', 'dept' => $deptSales, 'salary_amount' => 30000.00, 'emp_id' => 'EMP-ELG-014', 'gender' => 'Male', 'branch' => $branchMumbai],
            ['first_name' => 'Manish', 'last_name' => 'Tiwari', 'designation' => 'Delhi Store Stylist', 'dept' => $deptSales, 'salary_amount' => 38000.00, 'emp_id' => 'EMP-ELG-015', 'gender' => 'Male', 'branch' => $branchDelhi],
            ['first_name' => 'Divya', 'last_name' => 'Swaminathan', 'designation' => 'Bangalore Cashier & Billing', 'dept' => $deptAcc, 'salary_amount' => 34000.00, 'emp_id' => 'EMP-ELG-016', 'gender' => 'Female', 'branch' => $branchBangalore],
        ];

        foreach ($staffRoster as $s) {
            $staff = $this->createStaff($company, $s['branch'], $s['dept'], [
                'employee_id' => $s['emp_id'],
                'first_name' => $s['first_name'],
                'last_name' => $s['last_name'],
                'email' => strtolower($s['first_name'] . '.' . substr($s['last_name'], 0, 1)) . '@eleganceboutique.com',
                'designation' => $s['designation'],
                'salary_amount' => $s['salary_amount'],
                'gender' => $s['gender'],
            ]);
            $this->processStaffAttendanceAndPayroll($company, $staff, $ledgers);
        }

        // Shifts & Holidays
        Shift::firstOrCreate(['company_id' => $company->id, 'shift_name' => 'Boutique Retail Shift (10am - 8pm)'], [
            'branch_id' => $branchMumbai->id,
            'start_time' => '10:00:00',
            'end_time' => '20:00:00',
            'break_duration_minutes' => 60,
            'status' => 'active',
        ]);
        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Diwali Festive Holiday'], [
            'branch_id' => $branchMumbai->id, 'holiday_date' => '2026-11-08', 'holiday_type' => 'Festival', 'description' => 'Diwali Day',
        ]);

        // 13. Opening Stock Batches for all 25 items
        foreach ($itemsCatalog as $key => $item) {
            $qty = str_contains($key, 'lehenga') ? 12 : (str_contains($key, 'saree') ? 25 : 40);
            $cost = $item->min_price * 0.70;
            $this->recordOpeningStock($company, $branchMumbai, $godownBandraStock, $item, $qty, $cost, 'BATCH-ELG-OPN');
        }

        // 14. Real Purchase Transactions (10 Purchases from Weavers & Mills)
        $purchasePlan = [
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['saree_kanj_red'], 'qty' => 15, 'rate' => 14000.00, 'batch' => 'BATCH-KAN-26A'], ['item' => $itemsCatalog['saree_ban_blue'], 'qty' => 15, 'rate' => 12000.00, 'batch' => 'BATCH-BAN-26A']], 'inv' => 'SRT/2026/881', 'date' => '2026-08-10', 'paid' => 300000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['shirt_wht_40'], 'qty' => 30, 'rate' => 1600.00, 'batch' => 'BATCH-SHT-26A'], ['item' => $itemsCatalog['shirt_wht_42'], 'qty' => 30, 'rate' => 1600.00, 'batch' => 'BATCH-SHT-26B'], ['item' => $itemsCatalog['shirt_blu_40'], 'qty' => 25, 'rate' => 1650.00, 'batch' => 'BATCH-SHT-26C']], 'inv' => 'RAY/26/1029', 'date' => '2026-08-18', 'paid' => 150000.00],
            ['supp' => $suppliers[2], 'items' => [['item' => $itemsCatalog['kurti_floral_m'], 'qty' => 35, 'rate' => 1200.00, 'batch' => 'BATCH-KRT-26A'], ['item' => $itemsCatalog['kurti_floral_l'], 'qty' => 35, 'rate' => 1200.00, 'batch' => 'BATCH-KRT-26B'], ['item' => $itemsCatalog['kurti_floral_xl'], 'qty' => 25, 'rate' => 1250.00, 'batch' => 'BATCH-KRT-26C']], 'inv' => 'JPR/BLK/552', 'date' => '2026-08-25', 'paid' => 120000.00],
            ['supp' => $suppliers[3], 'items' => [['item' => $itemsCatalog['kurti_indigo_m'], 'qty' => 30, 'rate' => 1350.00, 'batch' => 'BATCH-IND-26A'], ['item' => $itemsCatalog['kurti_indigo_l'], 'qty' => 30, 'rate' => 1350.00, 'batch' => 'BATCH-IND-26B']], 'inv' => 'TRP/2026/902', 'date' => '2026-09-02', 'paid' => 90000.00],
            ['supp' => $suppliers[4], 'items' => [['item' => $itemsCatalog['lehenga_maroon'], 'qty' => 8, 'rate' => 28000.00, 'batch' => 'BATCH-LHG-26A'], ['item' => $itemsCatalog['lehenga_mint'], 'qty' => 8, 'rate' => 18000.00, 'batch' => 'BATCH-LHG-26B']], 'inv' => 'VNS/ZAR/441', 'date' => '2026-09-10', 'paid' => 250000.00],
            ['supp' => $suppliers[5], 'items' => [['item' => $itemsCatalog['chino_nav_32'], 'qty' => 25, 'rate' => 1750.00, 'batch' => 'BATCH-CHN-26A'], ['item' => $itemsCatalog['chino_gry_34'], 'qty' => 25, 'rate' => 1750.00, 'batch' => 'BATCH-CHN-26B']], 'inv' => 'ARV/CHN/781', 'date' => '2026-09-18', 'paid' => 98000.00],
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['saree_chn_green'], 'qty' => 20, 'rate' => 5800.00, 'batch' => 'BATCH-CHN-26C'], ['item' => $itemsCatalog['saree_org_pink'], 'qty' => 20, 'rate' => 6800.00, 'batch' => 'BATCH-ORG-26A']], 'inv' => 'SRT/2026/994', 'date' => '2026-09-28', 'paid' => 200000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['shirt_lin_olv'], 'qty' => 20, 'rate' => 1950.00, 'batch' => 'BATCH-LIN-26A'], ['item' => $itemsCatalog['shirt_lin_khk'], 'qty' => 20, 'rate' => 1950.00, 'batch' => 'BATCH-LIN-26B'], ['item' => $itemsCatalog['trouser_wol_blk'], 'qty' => 18, 'rate' => 2400.00, 'batch' => 'BATCH-WOL-26A']], 'inv' => 'RAY/26/1155', 'date' => '2026-10-05', 'paid' => 120000.00],
            ['supp' => $suppliers[2], 'items' => [['item' => $itemsCatalog['dress_maxi_wine'], 'qty' => 18, 'rate' => 3600.00, 'batch' => 'BATCH-DRS-26A'], ['item' => $itemsCatalog['dress_evn_black'], 'qty' => 15, 'rate' => 5000.00, 'batch' => 'BATCH-DRS-26B']], 'inv' => 'JPR/DRS/601', 'date' => '2026-10-15', 'paid' => 140000.00],
            ['supp' => $suppliers[4], 'items' => [['item' => $itemsCatalog['pashmina_ivory'], 'qty' => 12, 'rate' => 9500.00, 'batch' => 'BATCH-PAS-26A'], ['item' => $itemsCatalog['dupatta_mustard'], 'qty' => 25, 'rate' => 2800.00, 'batch' => 'BATCH-DUP-26A'], ['item' => $itemsCatalog['gown_cape_beige'], 'qty' => 10, 'rate' => 7200.00, 'batch' => 'BATCH-GWN-26A']], 'inv' => 'VNS/ACC/711', 'date' => '2026-10-22', 'paid' => 200000.00],
        ];

        $purchaseEntries = [];
        foreach ($purchasePlan as $idx => $p) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($p['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => $it['batch'], 'tax_percent' => 12];
            }
            $pe = $this->recordPurchaseWorkflow($company, $branchMumbai, $godownBandraStock, $p['supp'], $itemRows, [
                'po_number' => "PO-ELG-2026-{$num}",
                'grn_number' => "GRN-ELG-2026-{$num}",
                'purchase_number' => "PUR-ELG-2026-{$num}",
                'invoice_number' => $p['inv'],
                'date' => $p['date'],
                'paid_amount' => $p['paid'],
                'payment_number' => "PAY-ELG-2026-{$num}",
            ], $ledgers);
            $purchaseEntries[] = $pe;
        }

        // Defective Fabric Purchase Return
        if (!empty($purchaseEntries[0])) {
            $this->recordPurchaseReturn(
                $company, $branchMumbai, $godownBandraStock, $suppliers[0], $purchaseEntries[0],
                [['item' => $itemsCatalog['saree_ban_blue'], 'quantity' => 2, 'rate' => 12000.00, 'batch_no' => 'BATCH-BAN-26A']],
                $ledgers, 'PRT-ELG-2026-0001', '2026-08-20', 'Zari weave defect noticed during boutique inspection'
            );
        }

        // 15. Real Sales Transactions (16 Sales to Clients, Stylists & Walk-in Patrons)
        $salesPlan = [
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['saree_kanj_red'], 'qty' => 1, 'rate' => 22500.00], ['item' => $itemsCatalog['dupatta_mustard'], 'qty' => 2, 'rate' => 4800.00]], 'date' => '2026-08-22', 'paid' => 32100.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['lehenga_maroon'], 'qty' => 1, 'rate' => 48000.00], ['item' => $itemsCatalog['gown_cape_beige'], 'qty' => 1, 'rate' => 12500.00]], 'date' => '2026-08-26', 'paid' => 60000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['shirt_wht_40'], 'qty' => 3, 'rate' => 2800.00], ['item' => $itemsCatalog['shirt_blu_40'], 'qty' => 2, 'rate' => 2800.00], ['item' => $itemsCatalog['chino_nav_32'], 'qty' => 2, 'rate' => 3100.00]], 'date' => '2026-09-01', 'paid' => 20000.00],
            ['cust' => $customers[3], 'items' => [['item' => $itemsCatalog['kurti_floral_m'], 'qty' => 5, 'rate' => 2200.00], ['item' => $itemsCatalog['kurti_indigo_m'], 'qty' => 4, 'rate' => 2400.00], ['item' => $itemsCatalog['dress_maxi_wine'], 'qty' => 2, 'rate' => 6200.00]], 'date' => '2026-09-05', 'paid' => 30000.00],
            ['cust' => $customers[4], 'items' => [['item' => $itemsCatalog['lehenga_mint'], 'qty' => 2, 'rate' => 32000.00], ['item' => $itemsCatalog['saree_ban_blue'], 'qty' => 2, 'rate' => 19500.00]], 'date' => '2026-09-12', 'paid' => 80000.00],
            ['cust' => $customers[5], 'items' => [['item' => $itemsCatalog['saree_chn_green'], 'qty' => 2, 'rate' => 9500.00], ['item' => $itemsCatalog['saree_tus_gold'], 'qty' => 1, 'rate' => 13500.00], ['item' => $itemsCatalog['pashmina_ivory'], 'qty' => 1, 'rate' => 16500.00]], 'date' => '2026-09-19', 'paid' => 45000.00],
            ['cust' => $customers[6], 'items' => [['item' => $itemsCatalog['shirt_lin_olv'], 'qty' => 2, 'rate' => 3400.00], ['item' => $itemsCatalog['shirt_lin_khk'], 'qty' => 2, 'rate' => 3400.00], ['item' => $itemsCatalog['trouser_wol_blk'], 'qty' => 2, 'rate' => 4200.00]], 'date' => '2026-09-24', 'paid' => 24000.00],
            ['cust' => $customers[7], 'items' => [['item' => $itemsCatalog['saree_org_pink'], 'qty' => 2, 'rate' => 11000.00], ['item' => $itemsCatalog['kurti_floral_l'], 'qty' => 3, 'rate' => 2200.00]], 'date' => '2026-09-30', 'paid' => 28000.00],
            ['cust' => $customers[8], 'items' => [['item' => $itemsCatalog['dress_evn_black'], 'qty' => 3, 'rate' => 8400.00], ['item' => $itemsCatalog['dress_maxi_wine'], 'qty' => 2, 'rate' => 6200.00]], 'date' => '2026-10-04', 'paid' => 35000.00],
            ['cust' => $customers[9], 'items' => [['item' => $itemsCatalog['shirt_wht_42'], 'qty' => 4, 'rate' => 2800.00], ['item' => $itemsCatalog['chino_gry_34'], 'qty' => 3, 'rate' => 3100.00]], 'date' => '2026-10-08', 'paid' => 22000.00],
            ['cust' => $customers[10], 'items' => [['item' => $itemsCatalog['saree_kanj_red'], 'qty' => 1, 'rate' => 22500.00], ['item' => $itemsCatalog['pashmina_ivory'], 'qty' => 1, 'rate' => 16500.00]], 'date' => '2026-10-14', 'paid' => 39000.00],
            ['cust' => $customers[11], 'items' => [['item' => $itemsCatalog['kurti_indigo_l'], 'qty' => 4, 'rate' => 2400.00], ['item' => $itemsCatalog['kurti_floral_xl'], 'qty' => 3, 'rate' => 2300.00]], 'date' => '2026-10-18', 'paid' => 18000.00],
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['dress_evn_black'], 'qty' => 1, 'rate' => 8400.00], ['item' => $itemsCatalog['dupatta_mustard'], 'qty' => 1, 'rate' => 4800.00]], 'date' => '2026-10-22', 'paid' => 13200.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['saree_tus_gold'], 'qty' => 2, 'rate' => 13500.00], ['item' => $itemsCatalog['gown_cape_beige'], 'qty' => 1, 'rate' => 12500.00]], 'date' => '2026-10-25', 'paid' => 40000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['shirt_lin_olv'], 'qty' => 3, 'rate' => 3400.00], ['item' => $itemsCatalog['chino_nav_32'], 'qty' => 2, 'rate' => 3100.00]], 'date' => '2026-10-28', 'paid' => 18000.00],
            ['cust' => $customers[3], 'items' => [['item' => $itemsCatalog['kurti_floral_m'], 'qty' => 4, 'rate' => 2200.00], ['item' => $itemsCatalog['saree_chn_green'], 'qty' => 1, 'rate' => 9500.00]], 'date' => '2026-10-30', 'paid' => 20000.00],
        ];

        $salesInvoices = [];
        foreach ($salesPlan as $idx => $s) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($s['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => 'BATCH-ELG-OPN', 'tax_percent' => 12];
            }
            $si = $this->recordSalesWorkflow($company, $branchMumbai, $godownBandraStock, $s['cust'], $itemRows, [
                'order_number' => "SO-ELG-2026-{$num}",
                'invoice_number' => "INV-ELG-2026-{$num}",
                'delivery_number' => "DEL-ELG-2026-{$num}",
                'date' => $s['date'],
                'paid_amount' => $s['paid'],
                'collection_number' => "COL-ELG-2026-{$num}",
            ], $ledgers, $vehicle1, $route1);
            $salesInvoices[] = $si;
        }

        // Sizing Exchange Sales Return
        if (!empty($salesInvoices[2])) {
            $this->recordSalesReturn(
                $company, $branchMumbai, $godownBandraStock, $customers[2], $salesInvoices[2],
                [['item' => $itemsCatalog['shirt_wht_40'], 'quantity' => 1, 'rate' => 2800.00, 'batch_no' => 'BATCH-ELG-OPN']],
                $ledgers, 'SRT-ELG-2026-0001', '2026-09-04', 'Client sizing adjustment - Exchanged for alternate cut'
            );
        }

        // 16. Operational Expenses
        $this->recordExpenseVoucher($company, $branchMumbai, $ledgers['rentLedger'], $ledgers['bankLedger'], 180000.00, '2026-09-01', 'September Boutique Studio Rent - Turner Road Bandra');
        $this->recordExpenseVoucher($company, $branchMumbai, $ledgers['rentLedger'], $ledgers['bankLedger'], 180000.00, '2026-10-01', 'October Boutique Studio Rent - Turner Road Bandra');
        $this->recordExpenseVoucher($company, $branchMumbai, $ledgers['utilityLedger'], $ledgers['bankLedger'], 32000.00, '2026-10-10', 'Boutique Studio Power & Ambient Lighting Bill - Adani Electricity');
        $this->recordExpenseVoucher($company, $branchMumbai, $ledgers['packagingLedger'], $ledgers['bankLedger'], 24000.00, '2026-10-12', 'Luxury Branded Cloth Bags, Garment Covers & Ribbon Boxes');

        // 17. CRM Leads
        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'Meenakshi Sundaram'], [
            'company_name' => 'Sundaram Destination Wedding',
            'phone' => '+91 9820998877',
            'email' => 'meenakshi@example.com',
            'source' => 'Instagram Fashion Campaign',
            'status' => 'Qualified',
            'notes' => 'Looking for 15 bridal bridesmaid ensembles for Udaipur palace wedding.',
        ]);

        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'Aditya Singhal'], [
            'company_name' => 'Singhal Executive Wardrobe',
            'phone' => '+91 9811443322',
            'email' => 'aditya.s@example.com',
            'source' => 'Walk-in Studio Inquiry',
            'status' => 'Contacted',
            'notes' => 'Custom linen shirts and formal blazers styling enquiry.',
        ]);

        // 18. User Accounts
        $compAdminRole = Role::where('slug', 'company-admin')->first();
        $accountantRole = Role::where('slug', 'accounts-manager')->first();

        $compAdminUser = User::firstOrCreate(['email' => 'admin@eleganceboutique.com'], [
            'name' => 'Natasha Singhania (Boutique Admin)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $compAdminRole?->id,
            'phone' => '+91 9820112299',
            'is_active' => true,
        ]);
        $compAdminUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchMumbai->id]]);

        $accountsUser = User::firstOrCreate(['email' => 'accounts@eleganceboutique.com'], [
            'name' => 'Varun Chokshi (Boutique Accountant)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $accountantRole?->id,
            'phone' => '+91 9820667788',
            'is_active' => true,
        ]);
        $accountsUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchMumbai->id]]);

        // Attach Super Admin
        $superAdmin = User::where('email', 'admin@erp.com')->first();
        if ($superAdmin) {
            $superAdmin->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchMumbai->id]]);
        }
    }
}

