<?php

namespace Database\Seeders;

use App\Models\InspectionTemplate;
use Illuminate\Database\Seeder;

/**
 * ຕົວຢ່າງ ແມ່ແບບ ກວດກາ (starter) — admin ແກ້/ເພີ່ມ ໄດ້ ເຕັມ. idempotent (ຕາມ ຊື່).
 */
class InspectionTemplateSeeder extends Seeder
{
    /** ຊື່ ແມ່ແບບ Forklift ເກົ່າ 2 ອັນ ທີ່ ຖືກ ລວມ ແລ້ວ → ປິດ (is_active=false), ບໍ່ ລຶບ (ຮັກສາ ປະຫວັດ). */
    private const LEGACY_FORKLIFT = [
        'ກວດ Forklift ປະຈຳວັນ (Forklift Daily Inspection)',
        'ແບບຟອມກວດສອບລົດຍົກກ່ອນນຳໃຊ້ປະຈຳວັນ (WH-FLT-001)',
    ];

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
                    'ບໍ່ ມີ ຄວາມ ເສຍຫາຍ ຈາກ ຄວາມ ຮ້ອນ/ສານ ເຄມີ',
                    'ຮອຍ ຫຍິບ (stitching) ຄົບ, ບໍ່ ຫລຸດ',
                    'ປ້າຍ SWL/ນ້ຳໜັກ ຮັບ ໄດ້ ຍັງ ອ່ານ ໄດ້ ຊັດ',
                    'ຫ່ວງ/ຕະຂໍ/shackle ບໍ່ ບິດ ບ້ຽວ/ບໍ່ ແຕກ',
                    'ບໍ່ ມີ ຮອຍ ເປື້ອນ ນ້ຳມັນ/ຂີ້ ໝ້ຽງ ຫຼາຍ ເກີນ',
                    'ຢູ່ ໃນ ໄລຍະ ກວດ ປະຈຳ ປີ (ບໍ່ ໝົດ ອາຍຸ)',
                ],
            ],
            // ══ Forklift — ແມ່ແບບ ລວມ (ໄຟຟ້າ/ນ້ຳມັນ · ຕາມ ຮອບ) = ລວມ ຈາກ 2 ອັນ ເກົ່າ ══
            [
                'name' => 'ກວດກາ Forklift — ລວມ (ໄຟຟ້າ/ນ້ຳມັນ · ຕາມຮອບ)',
                'category' => 'Forklift',
                'method' => 'ລວມ 2 ແມ່ແບບ ເກົ່າ ເປັນ 1. ເລືອກ ຊະນິດ ລົດ (ໄຟຟ້າ/ນ້ຳມັນ) ແລະ ຮອບ ກວດ '
                    .'(ກ່ອນໃຊ້·ວັນ / ເດືອນ / ໄຕມາດ / 6ເດືອນ / ປີ) → ລາຍການ ຈະ ຂຶ້ນ ຕາມ ທີ່ ເລືອກ. '
                    .'ມາດຕະຖານ ISO 9001 / 14001 / 45001. S/OK = ໃຊ້ໄດ້ ປອດໄພ (ຜ່ານ) · R/NG = ຕ້ອງ ສ້ອມ (ບໍ່ຜ່ານ). '
                    .'ພົບ ຂໍ້ ບົກພ່ອງ ໃຫ້ ແຈ້ງ ຫົວໜ້າ ທັນທີ ກ່ອນ ນຳ ໃຊ້.',
                'items' => [
                    // ── A. ຈັກ / ພະລັງງານ (Engine / Power) ──
                    ['label' => 'ນ້ຳມັນເຄື່ອງ ແລະ ນ້ຳມັນເຊື້ອໄຟ — ລະດັບ (Engine oil & fuel level)', 'applies' => 'engine', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ນ້ຳຫລໍ່ເຢັນ ແລະ ໝໍ້ນ້ຳ (Coolant & radiator)', 'applies' => 'engine', 'freqs' => ['pre_use', 'monthly', 'semi_annual']],
                    ['label' => 'ສາຍພານ ແລະ ຄວາມຕຶງມູ່ເລ່ (Belts & pulley tension)', 'applies' => 'engine', 'freqs' => ['monthly', 'quarterly']],
                    ['label' => 'ການຮົ່ວ ນ້ຳມັນ/LPG ແລະ ທໍ່ໄອເສຍ (Fuel/LPG leakage & exhaust)', 'applies' => 'engine', 'freqs' => ['pre_use', 'quarterly', 'annual']],
                    ['label' => 'ຫ້ອງຈັກ ສະອາດ ບໍ່ມີເສດ (Engine compartment clean)', 'applies' => 'engine', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ແບັດເຕີຣີ — ລະດັບ / ການສາກ (Battery — level / charge)', 'applies' => 'ev', 'freqs' => ['pre_use', 'monthly', 'quarterly']],
                    ['label' => 'ຂົ້ວຕໍ່ ແລະ ສາຍໄຟ ແຮງດັນສູງ (Connector & HV cables)', 'applies' => 'ev', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ທໍ່ໄຮໂດຼລິກ ແລະ ການຮົ່ວ (Hydraulic lines & leakage)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly', 'quarterly']],
                    ['label' => 'ສາຍຢາງ — ສະພາບ (Hoses condition)', 'applies' => 'both', 'freqs' => ['monthly', 'quarterly']],
                    ['label' => 'ສາຍໄຟ ຫຼວມ / ຈຸດຕໍ່ໄຟຟ້າ (Loose wires / connections)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    // ── B. ຕົວຖັງ / ໂຄງສ້າງ (Chassis / Structure) ──
                    ['label' => 'ໂຄງສ້າງ ຕົວຖັງ ບໍ່ເສຍຫາຍ (Structural damage)', 'applies' => 'both', 'freqs' => ['pre_use', 'annual']],
                    ['label' => 'ລໍ້ ແລະ ຢາງ (Tyres & wheels)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly', 'quarterly']],
                    ['label' => 'ນ້ຳໜັກຖ່ວງ ຕິດແໜ້ນ (Counterweight secure)', 'applies' => 'both', 'freqs' => ['pre_use', 'annual']],
                    ['label' => 'ຫຼັງຄາກັນຕົກ / ກົງກັນຄວ່ຳ ROPS (Overhead guard / ROPS)', 'applies' => 'both', 'freqs' => ['pre_use', 'semi_annual', 'annual']],
                    ['label' => 'ເສົາຍົກ, ງ່າມ ແລະ ແຜງຮັບສິນຄ້າ (Mast, forks & load backrest)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly', 'annual']],
                    // ── C. ຫ້ອງຂັບ / ຄວາມປອດໄພ (Cab / Safety) ──
                    ['label' => 'ຫ້ອງຂັບ ສະອາດ ບໍ່ມີເສດ (Cab clean & clear)', 'applies' => 'both', 'freqs' => ['pre_use']],
                    ['label' => 'ອຸປະກອນ / ສະລິງ ເກັບມັດແໜ້ນ (Loose gear stored & secured)', 'applies' => 'both', 'freqs' => ['pre_use']],
                    ['label' => 'ຄູ່ມືຄົນຂັບ ຢູ່ໃນຫ້ອງຂັບ (Operator manual present)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ສະຕິກເກີເຕືອນ / ປ້າຍນ້ຳໜັກບັນທຸກ (Warning & capacity labels)', 'applies' => 'both', 'freqs' => ['pre_use', 'annual']],
                    ['label' => 'ຄັນບັງຄັບ ຢູ່ຕຳແໜ່ງກາງ (Controls in neutral)', 'applies' => 'both', 'freqs' => ['pre_use']],
                    ['label' => 'ສາຍຮັດນິລະໄພ ແລະ ບ່ອນນັ່ງ (Seat belt & seat)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ແວ່ນແຍງ (Mirrors)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ແກ (Horn)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ໄຟສ່ອງ ແລະ ໄຟສັນຍານ (Lights & indicators)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ສຽງເຕືອນຖອຍຫຼັງ (Back-up alarm)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ຖັງດັບເພີງ (Fire extinguisher)', 'applies' => 'both', 'freqs' => ['pre_use', 'semi_annual']],
                    ['label' => 'ໜ້າປັດ ແລະ ເຄື່ອງວັດ (Gauges & dashboard)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    // ── D. ໃຊ້ງານ / ທົດສອບ ຟັງຊັນ (Operation / Function) ──
                    ['label' => 'ຄົນຂັບ ມີໃບອະນຸຍາດ / ຝຶກຜ່ານ (Operator certified)', 'applies' => 'both', 'freqs' => ['pre_use', 'annual']],
                    ['label' => 'ພວງມາໄລ / ບັງຄັບລ້ຽວ (Steering)', 'applies' => 'both', 'freqs' => ['pre_use', 'quarterly']],
                    ['label' => 'ເບກ ແລະ ເບກຈອດ (Brake & parking brake)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly', 'quarterly']],
                    ['label' => 'ຄັນເລັ່ງ / ເບກ (Accelerator / brake pedal)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ງ່າມ ຍົກ ຂຶ້ນ/ລົງ ສຸດ (Forks lift full up/down)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ເລື່ອນຂ້າງ ຊ້າຍ-ຂວາ (Side shift)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ອຽງເສົາ ໜ້າ/ຫຼັງ (Mast tilt)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly']],
                    ['label' => 'ປ້າຍສາມຫຼ່ຽມສະທ້ອນແສງ — ພ້ອມໃຊ້ທາງ (Reflective triangle)', 'applies' => 'both', 'freqs' => ['pre_use']],
                    ['label' => 'ສະພາບໂດຍລວມ ພ້ອມໃຊ້ງານ (Overall condition — ready)', 'applies' => 'both', 'freqs' => ['pre_use', 'monthly', 'quarterly', 'semi_annual', 'annual']],
                ],
            ],
        ];

        foreach ($templates as $t) {
            // Forklift ລວມ + WH-FLT-style = updateOrCreate (refresh items/freqs); starter ອື່ນ = firstOrCreate.
            if ($t['category'] === 'Forklift') {
                InspectionTemplate::updateOrCreate(['name' => $t['name']], $t + ['is_active' => true]);

                continue;
            }
            InspectionTemplate::firstOrCreate(['name' => $t['name']], $t);
        }

        // ປິດ 2 ແມ່ແບບ Forklift ເກົ່າ ທີ່ ຖືກ ລວມ ແລ້ວ (ຖ້າ ມີ) — ບໍ່ ລຶບ, ຮັກສາ ປະຫວັດ ການ ກວດ ເກົ່າ.
        InspectionTemplate::whereIn('name', self::LEGACY_FORKLIFT)->update(['is_active' => false]);
    }
}
