<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\MaintenanceTemplate;
use Illuminate\Database\Seeder;

/**
 * ແມ່ແບບ ບຳລຸງ ມາດຕະຖານ — TCM FD30T3Z Forklift.
 * ຖອດ ຈາກ Preventive Maintenance Service Schedule ຂອງ TCM + ໃບ ກວດ ປະຈຳວັນ,
 * ທຽບກວດ ກັບ OSHA / ໂຮງງານ. C = ກວດ · X = ປ່ຽນ · ຮອບ 8/200/600/1200/2400h.
 *
 * firstOrCreate ຕາມ ຊື່ — ຣັນ ຊ້ຳ ບໍ່ ຊ້ຳ ຂໍ້ມູນ ແລະ ບໍ່ ທັບ ການ ແກ້ໄຂ ຂອງ ຜູ້ໃຊ້.
 */
class MaintenanceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // C = ກວດ, X = ປ່ຽນ. ຮອບ: daily·monthly·quarterly·semi_annual·annual.
        $items = [
            // ── ເຄື່ອງຈັກ · Engine ──
            ['label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຄື່ອງ · ປ່ຽນ', 'remark' => 'SAE 15W-40 · 1.4 gal · I:50h', 'cycles' => ['daily' => 'C', 'monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ປ່ຽນ ໝໍ້ກອງ ນ້ຳມັນ ເຄື່ອງ', 'remark' => '20801-01271 · 1pc', 'cycles' => ['monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ປ່ຽນ ໝໍ້ກອງ ເຊື້ອໄຟ', 'remark' => '20801-02141 · 1pc', 'cycles' => ['quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ຄວາມຕຶງ/ເສຍຫາຍ ສາຍພານ (ປັບ ຖ້າ ຈຳເປັນ)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳໝໍ້ນ້ຳ · ປ່ຽນ', 'remark' => '2.9 gal', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ສີ ຄວັນ ໄອເສຍ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ທຳຄວາມສະອາດ/ປ່ຽນ ໝໍ້ກອງ ອາກາດ', 'remark' => '20801-03521', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ລະບາຍ ນ້ຳ ອອກ ຈາກ ຖັງ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ທຳຄວາມສະອາດ ຖັງ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ໄລຍະ ວາລ໌ວ (valve clearance)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ຂັນ ນັອດ ຝາສູບ', 'remark' => '', 'cycles' => ['annual' => 'C']],
            ['label' => 'ກວດ ກຳລັງອັດ (compression)', 'remark' => '', 'cycles' => ['annual' => 'C']],

            // ── ລະບົບ ສົ່ງກຳລັງ · Power Train ──
            ['label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ ເກຍ (transmission)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ລະດັບ ນ້ຳມັນ ເກຍ · ປ່ຽນ', 'remark' => 'SAE 10W · 2.4 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ປ່ຽນ line filter element', 'remark' => '115G3-82242 · 1pc · M:200h', 'cycles' => ['monthly' => 'X']],
            ['label' => 'ທຳຄວາມສະອາດ suction strainer · ປ່ຽນ', 'remark' => '12N53-89811 · 1pc', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ ເຟືອງ (differential)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຟືອງ · ປ່ຽນ', 'remark' => 'SAE 90 · 1.58 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ຄວາມຫຼວມ & ສຽງ ຜິດປົກກະຕິ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ຖອດ & ອັດ ຈາລະບີ ລູກປືນລໍ້ (wheel bearing)', 'remark' => '', 'cycles' => ['semi_annual' => 'X', 'annual' => 'X']],

            // ── ບັງຄັບລ້ຽວ · Steering ──
            ['label' => 'ກວດ ການເຮັດວຽກ ປົກກະຕິ (ບັງຄັບລ້ຽວ)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ orbitrol · ກະບອກ · ທໍ່', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ king pins · ຂໍ້ຕໍ່ (joints)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ລະບົບ ຍົກ · Loading / Mast ──
            ['label' => 'ກວດ ຄວາມຕຶງ/ຮູບຊົງ/ເສຍຫາຍ ຂອງ ໂສ້', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ roller · pin · ຮອຍ ເຊື່ອມ ແຕກ/ເສຍຫາຍ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ອັດ ຈາລະບີ ໂສ້ (lubricate chains)', 'remark' => '', 'cycles' => ['monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ chain anchor pins · ງາ (forks) ເສຍຮູບ/ສຶກ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ໄຮໂດຼລິກ · Hydraulic ──
            ['label' => 'ກວດ ລະດັບ ນ້ຳມັນ ໄຮໂດຼລິກ · ປ່ຽນ', 'remark' => 'ISO VG32 · 8.5 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ທຳຄວາມສະອາດ suction strainer · ປ່ຽນ', 'remark' => '216G7-52051 · 1pc', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ປ່ຽນ return filter', 'remark' => '271A7-52301 · 1pc', 'cycles' => ['semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ ການເຮັດວຽກ ປົກກະຕິ · main relief pressure', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ຮົ່ວ (hydraulic leakage)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ໄຟຟ້າ · Electrical ──
            ['label' => 'ກວດ ການ ສະຕາດ & ຊາດ (charging)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ຄ່າ ຄວາມຖ່ວງ ຈຳເພາະ ນ້ຳກົດ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ລະດັບ ນ້ຳກົດ (electrolyte)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ອື່ນໆ · Others ──
            ['label' => 'ກວດ ໂຄງກັນຫຼັງຄາ (overhead guard) ແຕກ/ເສຍຮູບ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ໂຮນ · ໄຟ ທຸກ ໜ່ວຍ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເບຣກ · ປ່ຽນ', 'remark' => '0.2 L', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['label' => 'ກວດ master/wheel cylinder · ປ່ຽນ ຖ້າ ຈຳເປັນ', 'remark' => '', 'cycles' => ['annual' => 'X']],
            ['label' => 'ກວດ ລົມ ຢາງ (tire pressure)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['label' => 'ຍ່າງ ກວດ ຮອບ ຄັນ (walk-around)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
        ];

        // ຫາ ລົດ forklift ໃນ ທະບຽນ ເພື່ອ ຜູກ ໃຫ້ (ຖ້າ ບໍ່ ມີ → ປະ ວ່າງ ໃຫ້ ຜູ້ໃຊ້ ເລືອກ ພາຍຫຼັງ).
        $eq = Equipment::query()
            ->where(fn ($q) => $q->where('name', 'like', '%FD30%')
                ->orWhere('brand_model', 'like', '%FD30%')
                ->orWhere('serial_no', 'like', '%FD30%'))
            ->orderBy('id')->first()
            ?? Equipment::query()
                ->where(fn ($q) => $q->where('name', 'like', '%forklift%')
                    ->orWhere('brand_model', 'like', '%forklift%')
                    ->orWhere('category', 'like', '%forklift%')
                    ->orWhere('name', 'like', '%ຍົກ%'))
                ->orderBy('id')->first();

        MaintenanceTemplate::firstOrCreate(
            ['name' => 'TCM FD30T3Z — ບຳລຸງ ຕາມ ຮອບ'],
            [
                'equipment_id' => $eq?->id,
                'category' => $eq?->category,
                'method' => 'ໃບ ບຳລຸງ ຕາມ ຮອບ ຊົ່ວໂມງ (Daily/8h · 200h · 600h · 1200h · 2400h). C = ກວດ · X = ປ່ຽນ. ຖອດ ຈາກ ໃບ TCM FD30T3Z, ທຽບກວດ OSHA/ໂຮງງານ.',
                'items' => $items,
                'is_active' => true,
            ]
        );
    }
}
