<?php

namespace Database\Seeders;

use App\Models\IndustryProcessTemplate;
use Illuminate\Database\Seeder;

class IndustryTemplatesSeeder extends Seeder
{
    /**
     * Seed standardized operational process templates for the 12 target industries.
     */
    public function run(): void
    {
        $templates = [
            // 1. Food Manufacturing & Processing
            ['industry_type' => 'food_processing', 'department_name' => 'Cleaning & Sorting', 'process_name' => 'Raw Spice De-stoning & Air Cleaning', 'process_code' => 'FP-CLN', 'default_uom' => 'KG', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'food_processing', 'department_name' => 'Roasting Department', 'process_name' => 'Thermal Batch Roasting & Moisture Reduction', 'process_code' => 'FP-RST', 'default_uom' => 'KG', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'food_processing', 'department_name' => 'Grinding & Pulverizing', 'process_name' => 'Micro-Pulverizer Fine Grinding', 'process_code' => 'FP-GRN', 'default_uom' => 'KG', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'food_processing', 'department_name' => 'Blending Department', 'process_name' => 'Formulation Spice Ribbon Blending', 'process_code' => 'FP-MIX', 'default_uom' => 'KG', 'requires_qc' => true, 'display_order' => 4],
            ['industry_type' => 'food_processing', 'department_name' => 'Packing Department', 'process_name' => 'Pouch Packing & Carton Boxing', 'process_code' => 'FP-PCK', 'default_uom' => 'PKT', 'requires_qc' => true, 'display_order' => 5],

            // 2. Water & Beverage Businesses
            ['industry_type' => 'beverages', 'department_name' => 'Water Treatment', 'process_name' => 'Reverse Osmosis & Mineral Fortification', 'process_code' => 'BV-RO', 'default_uom' => 'LTR', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'beverages', 'department_name' => 'Blow Molding', 'process_name' => 'PET Preform Heating & Bottle Blowing', 'process_code' => 'BV-BLW', 'default_uom' => 'PCS', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'beverages', 'department_name' => 'Filling & Carbonation', 'process_name' => 'Automated Filling, Carbonation & Capping', 'process_code' => 'BV-FIL', 'default_uom' => 'BTL', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'beverages', 'department_name' => 'Labelling & Packaging', 'process_name' => 'Shrink Sleeve Labelling & Outer Bundling', 'process_code' => 'BV-LBL', 'default_uom' => 'BOX', 'requires_qc' => true, 'display_order' => 4],
            ['industry_type' => 'beverages', 'department_name' => 'Can Logistics', 'process_name' => '20L Can Sanitization & Refill Capping', 'process_code' => 'BV-CAN', 'default_uom' => 'CAN', 'requires_qc' => true, 'display_order' => 5],

            // 3. Tailoring, Stitching & Garment Businesses
            ['industry_type' => 'garments', 'department_name' => 'Pattern & Cutting', 'process_name' => 'Fabric Laying & Precision Laser/Knife Cutting', 'process_code' => 'GM-CUT', 'default_uom' => 'MTR', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'garments', 'department_name' => 'Embroidery & Embellishment', 'process_name' => 'Zari & Computerized Embroidery Stitching', 'process_code' => 'GM-EMB', 'default_uom' => 'PCS', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'garments', 'department_name' => 'Stitching & Tailoring', 'process_name' => 'Garment Assembly & Seam Stitching', 'process_code' => 'GM-STC', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'garments', 'department_name' => 'Finishing & Pressing', 'process_name' => 'Thread Trimming, Steam Pressing & Tagging', 'process_code' => 'GM-FIN', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 4],
            ['industry_type' => 'garments', 'department_name' => 'Garment Packaging', 'process_name' => 'Boutique Box & Polybag Packing', 'process_code' => 'GM-PCK', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 5],

            // 4. Furniture Businesses
            ['industry_type' => 'furniture', 'department_name' => 'Sawmill & Timber Yard', 'process_name' => 'Timber Sizing, Planking & Kiln Seasoning', 'process_code' => 'FN-SAW', 'default_uom' => 'CFT', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'furniture', 'department_name' => 'Carpentry & Joinery', 'process_name' => 'Precision Tenon & Mortise Joinery Assembly', 'process_code' => 'FN-CRP', 'default_uom' => 'PCS', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'furniture', 'department_name' => 'Sanding & Polishing', 'process_name' => 'Surface Sanding, Sealer Coat & PU Polish', 'process_code' => 'FN-POL', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'furniture', 'department_name' => 'Upholstery Fitting', 'process_name' => 'Foam Padding & Fabric/Leather Upholstery', 'process_code' => 'FN-UPH', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 4],
            ['industry_type' => 'furniture', 'department_name' => 'Final Assembly & QC', 'process_name' => 'Hardware Fitting, Structural Inspection & Wrapping', 'process_code' => 'FN-ASM', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 5],

            // 5. Construction & Building Materials
            ['industry_type' => 'construction', 'department_name' => 'Site Batching', 'process_name' => 'Ready-Mix Concrete Batching & Pouring', 'process_code' => 'CN-CON', 'default_uom' => 'CUM', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'construction', 'department_name' => 'Masonry & Blockwork', 'process_name' => 'AAC Block Laying & Structural Framing', 'process_code' => 'CN-MAS', 'default_uom' => 'SQM', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'construction', 'department_name' => 'Plastering & Flooring', 'process_name' => 'Internal Cement Plaster & Tile Flooring', 'process_code' => 'CN-FLR', 'default_uom' => 'SQFT', 'requires_qc' => true, 'display_order' => 3],

            // 6. Automobile Businesses
            ['industry_type' => 'automobile', 'department_name' => 'Mechanical Workshop', 'process_name' => 'Engine Servicing, Oil & Filter Replacement', 'process_code' => 'AU-MEC', 'default_uom' => 'JOB', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'automobile', 'department_name' => 'Electrical & Diagnostics', 'process_name' => 'OBD Diagnostic Scan & Wiring Harness Repair', 'process_code' => 'AU-ELE', 'default_uom' => 'JOB', 'requires_qc' => true, 'display_order' => 2],
            ['industry_type' => 'automobile', 'department_name' => 'Bodyshop & Paint', 'process_name' => 'Denting, Panel Primer & Spray Booth Painting', 'process_code' => 'AU-PNT', 'default_uom' => 'PNL', 'requires_qc' => true, 'display_order' => 3],

            // 7. Agriculture & Agricultural Products
            ['industry_type' => 'agriculture', 'department_name' => 'Field Cultivation', 'process_name' => 'Land Tilling, Bedding & Seed Sowing', 'process_code' => 'AG-SOW', 'default_uom' => 'ACR', 'requires_qc' => false, 'display_order' => 1],
            ['industry_type' => 'agriculture', 'department_name' => 'Irrigation & Nutrition', 'process_name' => 'Drip Fertigation & Organic Pest Control', 'process_code' => 'AG-NUT', 'default_uom' => 'ACR', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'agriculture', 'department_name' => 'Harvesting & Grading', 'process_name' => 'Crop Harvesting, Sorting & Cold Storage Intake', 'process_code' => 'AG-HRV', 'default_uom' => 'KG', 'requires_qc' => true, 'display_order' => 3],

            // 8. Medical & Healthcare Businesses
            ['industry_type' => 'healthcare', 'department_name' => 'Clinical Diagnostics', 'process_name' => 'Hematology & Biochemical Sample Analysis', 'process_code' => 'HC-LAB', 'default_uom' => 'SMP', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'healthcare', 'department_name' => 'Hospital Pharmacy', 'process_name' => 'Inpatient Prescription Compounding & Dispensing', 'process_code' => 'HC-PHR', 'default_uom' => 'DOS', 'requires_qc' => true, 'display_order' => 2],
            ['industry_type' => 'healthcare', 'department_name' => 'Central Sterilization', 'process_name' => 'Autoclave Instrument Sterilization & Pack Prep', 'process_code' => 'HC-CSSD', 'default_uom' => 'SET', 'requires_qc' => true, 'display_order' => 3],

            // 9. Printing & Packaging
            ['industry_type' => 'printing', 'department_name' => 'Pre-Press', 'process_name' => 'CTP Plate Imaging & Color Proofing', 'process_code' => 'PR-PLT', 'default_uom' => 'SET', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'printing', 'department_name' => 'Offset Press', 'process_name' => 'Multi-Color Offset Printing Impression', 'process_code' => 'PR-IMP', 'default_uom' => 'SHT', 'requires_qc' => true, 'display_order' => 2],
            ['industry_type' => 'printing', 'department_name' => 'Die-Cutting & Lamination', 'process_name' => 'Thermal Film Lamination & Emboss Die Cutting', 'process_code' => 'PR-DIE', 'default_uom' => 'SHT', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'printing', 'department_name' => 'Folder Gluer', 'process_name' => 'Carton Folding, Side-Seam Gluing & Bundling', 'process_code' => 'PR-GLU', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 4],

            // 10. Wholesale & Distribution
            ['industry_type' => 'wholesale', 'department_name' => 'Receiving & Cross-Dock', 'process_name' => 'Container Inward Unloading & Pallet Tagging', 'process_code' => 'WS-RCV', 'default_uom' => 'PLT', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'wholesale', 'department_name' => 'Order Picking & Sort', 'process_name' => 'Pick-to-Carton Route Fulfillment', 'process_code' => 'WS-PCK', 'default_uom' => 'ORD', 'requires_qc' => false, 'display_order' => 2],
            ['industry_type' => 'wholesale', 'department_name' => 'Van Fleet Loading', 'process_name' => 'Territory Delivery Van Load Auditing', 'process_code' => 'WS-VAN', 'default_uom' => 'VAN', 'requires_qc' => true, 'display_order' => 3],

            // 11. Beauty & Personal Care Manufacturing
            ['industry_type' => 'cosmetics', 'department_name' => 'Raw Compounding', 'process_name' => 'Lipid & Aqueous Phase Emulsion Blending', 'process_code' => 'BC-CMP', 'default_uom' => 'KG', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'cosmetics', 'department_name' => 'Sterile Filling', 'process_name' => 'Cleanroom Tube & Bottle Dispense Capping', 'process_code' => 'BC-FIL', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 2],
            ['industry_type' => 'cosmetics', 'department_name' => 'Secondary Packaging', 'process_name' => 'Mono-Carton Tuck-In & Batch Coding', 'process_code' => 'BC-PCK', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 3],

            // 12. Jewellery Businesses
            ['industry_type' => 'jewellery', 'department_name' => 'Melting & Alloying', 'process_name' => 'Gold Bullion Induction Melting & 916 Alloying', 'process_code' => 'JW-MLT', 'default_uom' => 'GM', 'requires_qc' => true, 'display_order' => 1],
            ['industry_type' => 'jewellery', 'department_name' => 'Goldsmith Handcraft', 'process_name' => 'Filigree Shaping, Soldering & Stone Setting', 'process_code' => 'JW-GLD', 'default_uom' => 'GM', 'requires_qc' => true, 'display_order' => 2],
            ['industry_type' => 'jewellery', 'department_name' => 'Polishing & Ultrasonic', 'process_name' => 'Magnetic Polishing, Ultrasonic Wash & Rhodium Dip', 'process_code' => 'JW-POL', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 3],
            ['industry_type' => 'jewellery', 'department_name' => 'Hallmarking & Purity', 'process_name' => 'XRF Assay Purity Testing & Laser Hallmarking', 'process_code' => 'JW-HLM', 'default_uom' => 'PCS', 'requires_qc' => true, 'display_order' => 4],
        ];

        foreach ($templates as $tpl) {
            IndustryProcessTemplate::firstOrCreate(
                [
                    'industry_type' => $tpl['industry_type'],
                    'process_code' => $tpl['process_code'],
                ],
                $tpl
            );
        }
    }
}

