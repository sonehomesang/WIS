<?php

namespace Database\Seeders;

use App\Models\AreaInspectionTemplate;
use Illuminate\Database\Seeder;

class AreaInspectionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $chemical = [
            ['label' => 'Ventilation', 'requirement' => 'Area is well-ventilated and airflow unobstructed.'],
            ['label' => 'Lighting', 'requirement' => 'Lighting adequate and operational.'],
            ['label' => 'Temperature Control', 'requirement' => 'Temperature within SDS requirement.'],
            ['label' => 'Chemical Segregation', 'requirement' => 'Incompatible chemicals stored separately.'],
            ['label' => 'Labelling', 'requirement' => 'All containers properly labeled (name, hazard, responsible person).'],
            ['label' => 'SDS Available', 'requirement' => 'SDS available and accessible near storage area.'],
            ['label' => 'PPE Available', 'requirement' => 'Required PPE available and in good condition.'],
            ['label' => 'Spill', 'requirement' => 'Spill kit complete and accessible.'],
            ['label' => 'Fire Extinguisher', 'requirement' => 'Correct extinguisher type present and inspected.'],
            ['label' => 'Storage Condition', 'requirement' => 'No leaks, corrosion, or damaged containers.'],
            ['label' => 'Housekeeping', 'requirement' => 'Area clean, dry, and free from obstructions.'],
            ['label' => 'Emergency Contact Info', 'requirement' => 'Emergency contact list posted and readable.'],
        ];

        AreaInspectionTemplate::updateOrCreate(
            ['name' => 'Monthly Chemical Storage Area Inspection'],
            ['frequency' => 'monthly', 'items' => $chemical, 'is_active' => true]
        );

        $daily = [
            ['label' => 'Walkways / Aisles', 'requirement' => 'Clear, no obstructions or trip hazards.'],
            ['label' => 'Housekeeping', 'requirement' => 'Area clean and tidy.'],
            ['label' => 'Fire Extinguisher', 'requirement' => 'Present, accessible, not obstructed.'],
            ['label' => 'Emergency Exits', 'requirement' => 'Unlocked and clearly marked.'],
            ['label' => 'PPE', 'requirement' => 'Staff wearing required PPE.'],
            ['label' => 'Spills / Leaks', 'requirement' => 'No spills or leaks present.'],
        ];

        AreaInspectionTemplate::updateOrCreate(
            ['name' => 'Daily Workplace Safety Walk'],
            ['frequency' => 'daily', 'items' => $daily, 'is_active' => true]
        );
    }
}
