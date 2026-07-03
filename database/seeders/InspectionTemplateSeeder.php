<?php

namespace Database\Seeders;

use App\Models\InspectionTemplate;
use Illuminate\Database\Seeder;

/**
 * ຕົວຢ່າງ ແມ່ແບບ ກວດກາ (starter) — admin ແກ້/ເພີ່ມ ໄດ້ ເຕັມ. idempotent (ຕາມ ຊື່).
 */
class InspectionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'ເຄື່ອງມື ໄຟຟ້າ ພົກພາ (Portable power tools)',
                'category' => 'Power tool',
                'method' => 'ກວດ ດ້ວຍ ຕາ + ທົດລອງ ເປີດ ໃຊ້ ກ່ອນ ນຳ ໄປ ໃຊ້ ວຽກ.',
                'items' => [
                    'ໂຄງ/ຝາ ຄອບ ບໍ່ ແຕກ/ບໍ່ ຮ້າວ',
                    'ສາຍ ໄຟ + ປລັກ ບໍ່ ຊຳລຸດ, ບໍ່ ມີ ສາຍ ເປືອຍ',
                    'ສະວິດ ເປີດ/ປິດ ໃຊ້ ໄດ້ ປົກກະຕິ',
                    'ຝາ ກັນ (guard) ຕິດ ຄົບ ແລະ ໝັ້ນ',
                    'ບໍ່ ມີ ຮ່ອງຮອຍ ຄວາມ ຮ້ອນ/ໄໝ້/ກິ່ນ ໄໝ້',
                    'ໃບ ຕັດ/ຫົວ ເຈາະ ຄົມ ແລະ ຕິດ ໝັ້ນ',
                    'ປ້າຍ ກວດ ໄຟຟ້າ (PAT) ຍັງ ບໍ່ ໝົດ ອາຍຸ',
                    'ສະອາດ, ບໍ່ ມີ ນ້ຳມັນ/ຝຸ່ນ ອຸດ ຊ່ອງ ລະບາຍ ອາກາດ',
                ],
            ],
            [
                'name' => 'ສະລິງ/ອຸປະກອນ ຍົກ (Lifting sling / rigging)',
                'category' => 'Sling',
                'method' => 'ກວດ ດ້ວຍ ຕາ ກ່ອນ ໃຊ້ ທຸກ ຄັ້ງ; ຫ້າມ ໃຊ້ ຖ້າ ພົບ ຄວາມ ເສຍຫາຍ.',
                'items' => [
                    'ບໍ່ ມີ ຮອຍ ຕັດ/ຂາດ/ດຶງ ເສັ້ນໃຍ',
                    'ບໍ່ ມີ ຄວາມ ເສຍຫาย ຈาก ຄວາມ ຮ້ອນ/ສານ ເຄມີ',
                    'ຮອຍ ຫຍິບ (stitching) ຄົບ, ບໍ່ ຫລຸດ',
                    'ປ້າຍ SWL/ນ້ຳໜັກ ຮັບ ໄດ້ ຍັງ ອ່ານ ໄດ້ ຊັດ',
                    'ຫ່ວງ/ຕະຂໍ/shackle ບໍ່ ບິດ ບ້ຽວ/ບໍ່ ແຕກ',
                    'ບໍ່ ມີ ຮອຍ ເປື້ອນ ນ້ຳມັນ/ຂີ້ ໝ້ຽງ ຫຼາຍ ເກີນ',
                    'ຢູ່ ໃນ ໄລຍະ ກວດ ປະຈຳ ປີ (ບໍ່ ໝົດ ອາຍຸ)',
                ],
            ],
            [
                'name' => 'ກວດ Forklift ປະຈຳວັນ (Forklift Daily Inspection)',
                'category' => 'Forklift',
                'method' => 'S = ພໍໃຈ / ໃຊ້ ໄດ້ ປອດໄພ (=ຜ່ານ) · R = ຕ້ອງ ສ້ອມ/ປ່ຽນ (=ບໍ່ຜ່ານ, ໃສ່ ໝາຍເຫດ). '
                    .'ກວດ ກ່ອນ ໃຊ້ ທຸກ ວັນ ໂດຍ ຄົນ ຂັບ ທີ່ ມີ ໃບ ອະນຸຍາດ; ລາຍການ ທີ່ ເປັນ R ໃຫ້ ແຈ້ງ ຫົວໜ້າ ທັນທີ.',
                'items' => [
                    // ── ຈັກ (Engine) ──
                    'ຈັກ: ລະດັບ ນ້ຳມັນ/ນ້ຳ (Fluid level)',
                    'ຈັກ: ສາຍ ພານ ແລະ ຄວາມ ຕຶງ ມູ່ເລ່ (Belts & pulley tension)',
                    'ຈັກ: ຫ້ອງ ຈັກ ສະອາດ ບໍ່ ມີ ເສດ ຂີ້ເຫຍື້ອ (Compartment free of debris)',
                    'ຈັກ: ທໍ່ ໄຮໂດຼລິກ — ຮົ່ວ/ສະພາບ (Hydraulic lines)',
                    'ຈັກ: ສາຍ ຢາງ — ສະພາບ (Hoses condition)',
                    'ຈັກ: ສາຍ ໄຟ ຫຼວມ/ຈຸດ ຕໍ່ ໄຟຟ້າ (Loose wires/connections)',
                    // ── ຕົວ ຖັງ (Body) ──
                    'ຕົວ ຖັງ: ໂຄງ ສ້າງ ບໍ່ ເສຍຫາຍ (Structural damage)',
                    'ຕົວ ຖັງ: ລໍ້ — ສຶກ/ດອກ ຢາງ/ເສຍຫາຍ (Wheels)',
                    'ຕົວ ຖັງ: ນ້ຳໜັກ ຖ່ວງ ບໍ່ ມີ ເສດ/ອຸປະກອນ (Counterweight)',
                    'ຕົວ ຖັງ: ກົງ ກັນ ຄວ່ຳ ROPS ບໍ່ ເສຍຫາຍ (Roll-over cage)',
                    'ຕົວ ຖັງ: ເສົາ ຍົກ ແລະ ງ່າມ — ໄຮໂດຼລິກ/ສະລັກ/ນັອດ (Mast & forks)',
                    // ── ຫ້ອງ ຄົນ ຂັບ (Operator compartment) ──
                    'ຫ້ອງ ຂັບ: ສະອາດ ບໍ່ ມີ ຂີ້ເຫຍື້ອ (Free of trash)',
                    'ຫ້ອງ ຂັບ: ອຸປະກອນ/ວັດສະດຸ/ສະລິງ ເກັບ ມັດ ແໜ້ນ (Stored & secured)',
                    'ຫ້ອງ ຂັບ: ຄູ່ມື ຄົນ ຂັບ ຢູ່ ໃນ ຫ້ອງ ຂັບ (Manual in cab)',
                    'ຫ້ອງ ຂັບ: ສະຕິກເກີ ເຕືອນ/ປ້າຍ ນ້ຳໜັກ ບັນທຸກ ຕິດ ຄົບ (Warnings/capacity)',
                    'ຫ້ອງ ຂັບ: ຄັນ ບັງຄັບ ກັບ ຕຳແໜ່ງ ກາງ (Controls neutral)',
                    'ຫ້ອງ ຂັບ: ສາຍ ຮັດ ບ່ອນ ນັ່ງ (Seat belt)',
                    'ຫ້ອງ ຂັບ: ແວ່ນ ແຍງ (Mirrors)',
                    'ຫ້ອງ ຂັບ: ແກ (Horn)',
                    'ຫ້ອງ ຂັບ: ໄຟ ສ່ອງ (Lights)',
                    'ຫ້ອງ ຂັບ: ສຽງ ເຕືອນ ຖອຍ ຫຼັງ (Back-up alarm)',
                    'ຫ້ອງ ຂັບ: ຖັງ ດັບ ເພີງ (Fire extinguisher)',
                    'ຫ້ອງ ຂັບ: ໜ້າ ປັດ ແລະ ເຄື່ອງ ວັດ — ໃຊ້ໄດ້/ເສຍຫາຍ (Gauges)',
                    'ຫ້ອງ ຂັບ: ເບກ ຈອດ ໃສ່ ໄດ້ ແລະ ໃຊ້ ໄດ້ (Parking brake)',
                    // ── ການ ໃຊ້ງານ (Operation) ──
                    'ໃຊ້ງານ: ຄົນ ຂັບ ມີ ໃບ ອະນຸຍາດ/ຜ່ານ ການ ຝຶກ (Operator certified)',
                    'ໃຊ້ງານ: ພວງມາໄລ/ບັງຄັບ ລ້ຽວ (Steering)',
                    'ໃຊ້ງານ: ງ່າມ ຍົກ ຂຶ້ນ/ລົງ ສຸດ (Forks up/down)',
                    'ໃຊ້ງານ: ເລື່ອນ ຂ້າງ ຊ້າຍ-ຂວາ ສຸດ (Side shift)',
                    'ໃຊ້ງານ: ອຽງ ເສົາ ໜ້າ/ຫຼັງ (Tilt)',
                    'ໃຊ້ງານ: ຄັນ ເລັ່ງ/ເບກ ໃຊ້ ໄດ້ (Accelerator/brake pedal)',
                    'ໃຊ້ງານ: ພ້ອມ ໃຊ້ ທາງ — ປ້າຍ ສາມ ຫຼ່ຽມ ສະທ້ອນ ແສງ (Reflective triangle)',
                ],
            ],
            [
                'name' => 'ແບບຟອມກວດສອບລົດຍົກກ່ອນນຳໃຊ້ປະຈຳວັນ (WH-FLT-001)',
                'category' => 'Forklift',
                'method' => 'ມາດຕະຖານ ISO 9001 / 14001 / 45001 · ເອກະສານ WH-FLT-001 Rev.00. '
                    .'ເລືອກ ປະເພດ ລົດ (ໄຟຟ້າ/ນ້ຳມັນ) ກ່ອນ → ລາຍການ ຈະ ຂຶ້ນ ຕາມ ປະເພດ. '
                    .'S/OK = ໃຊ້ໄດ້ ປອດໄພ (ຜ່ານ) · R/NG = ຕ້ອງ ສ້ອມ (ບໍ່ຜ່ານ). ພົບ ຂໍ້ ບົກພ່ອງ ໃຫ້ ແຈ້ງ ຫົວໜ້າ ທັນທີ ກ່ອນ ນຳ ໃຊ້.',
                'items' => [
                    ['label' => 'ງ່າມ, ເສົາ ຍົກ ແລະ ແຜງ ຮັບ ສິນຄ້າ (Forks, Mast & Load Backrest)', 'applies' => 'both'],
                    ['label' => 'ລະບົບ ໄຮໂດຼລິກ / ບໍ່ ມີ ຮົ່ວ (Hydraulic System — Leakage)', 'applies' => 'both'],
                    ['label' => 'ຢາງ ແລະ ລໍ້ (Tyres & Wheels)', 'applies' => 'both'],
                    ['label' => 'ລະບົບ ບັງຄັບ ລ້ຽວ (Steering System)', 'applies' => 'both'],
                    ['label' => 'ເບກ ແລະ ເບກ ຈອດ (Brake & Parking Brake)', 'applies' => 'both'],
                    ['label' => 'ແກ, ໄຟ ແລະ ສຽງ ຖອຍ ຫຼັງ (Horn, Lights & Reverse Alarm)', 'applies' => 'both'],
                    ['label' => 'ສາຍ ຄາດ ນິລະໄພ ແລະ ບ່ອນ ນັ່ງ (Seat Belt & Operator Seat)', 'applies' => 'both'],
                    ['label' => 'ຫຼັງຄາ ກັນ ຕົກ ແລະ ປ້າຍ ຄວາມ ປອດໄພ (Overhead Guard & Safety Labels)', 'applies' => 'both'],
                    ['label' => 'ໜ້າ ປັດ ແລະ ໄຟ ເຕືອນ (Dashboard & Warning Indicators)', 'applies' => 'both'],
                    ['label' => 'ແບັດເຕີຣີ ແລະ ຂົ້ວ ຕໍ່ (Battery & Connector)', 'applies' => 'ev'],
                    ['label' => 'ນ້ຳມັນ ເຄື່ອງ ແລະ ນ້ຳມັນ ເຊື້ອ ໄຟ (Engine Oil & Fuel)', 'applies' => 'engine'],
                    ['label' => 'ນ້ຳ ຫລໍ່ ເຢັນ ແລະ ໝໍ້ ນ້ຳ (Coolant & Radiator)', 'applies' => 'engine'],
                    ['label' => 'ການ ຮົ່ວ ນ້ຳມັນ/LPG ແລະ ທໍ່ ໄອ ເສຍ (Fuel/LPG Leakage & Exhaust)', 'applies' => 'engine'],
                    ['label' => 'ຟັງຊັນ ເຄື່ອນ ທີ່, ຍົກ ແລະ ອຽງ (Travel, Lift & Tilt Function)', 'applies' => 'both'],
                    ['label' => 'ສະພາບ ໂດຍ ລວມ (Overall Condition)', 'applies' => 'both'],
                ],
            ],
        ];

        foreach ($templates as $t) {
            InspectionTemplate::firstOrCreate(['name' => $t['name']], $t);
        }
    }
}
