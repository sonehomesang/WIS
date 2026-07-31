<?php

namespace Database\Seeders;

use App\Models\MaintenanceTemplate;
use Illuminate\Database\Seeder;

/**
 * ແມ່ແບບ ບຳລຸງ ມາດຕະຖານ ຟ໋ອກລິບ — master (ບໍ່ ຜູກ ເຄື່ອງ ໜຶ່ງ ໜ່ວຍ, ຂຶ້ນ ຕາມ ປະເພດ Vehicles).
 * ຖອດ ຈາກ ໃບ ບຳລຸງ ມາດຕະຖານ forklift + ໃບ ກວດ ປະຈຳວັນ, ທຽບກວດ OSHA / ໂຮງງານ.
 * C = ກວດ · X = ປ່ຽນ · ຮອບ 8/200/600/1200/2400h.
 *
 * firstOrCreate ຕາມ ຊື່ — ຣັນ ຊ້ຳ ບໍ່ ຊ້ຳ ຂໍ້ມູນ ແລະ ບໍ່ ທັບ ການ ແກ້ໄຂ ຂອງ ຜູ້ໃຊ້.
 */
class MaintenanceTemplateSeeder extends Seeder
{
    /** ຊື່ ປັດຈຸບັນ (master) ແລະ ຊື່ ເກົ່າ (ໃຫ້ migration ຫາ record ເກົ່າ ມາ rename). */
    public const NAME = 'VC-FLPMCM · ແບບຟອມ ກວດ ບຳລຸງຮັກສາ ຟ໋ອກລິບ (ມາສເຕີ້)';

    public const OLD_NAME = 'TCM FD30T3Z — ບຳລຸງ ຕາມ ຮອບ';

    /** ປະເພດ ເຄື່ອງ ທີ່ ແມ່ແບບ ນີ້ ຂຶ້ນ (master scope). */
    public const CATEGORY = 'Vehicles';

    /**
     * ລາຍການ ເຊັກລິສ ມາດຕະຖານ (label · remark · cycles · group).
     * static ເພື່ອ ໃຫ້ migration backfill ໃຊ້ ນິຍາມ ດຽວກັນ (ບໍ່ ຊ້ຳ).
     *
     * @return array<int,array{group:string,label:string,remark:string,cycles:array<string,string>}>
     */
    public static function items(): array
    {
        // C = ກວດ, X = ປ່ຽນ. ຮອບ: daily·monthly·quarterly·semi_annual·annual.
        $items = [
            // ── ເຄື່ອງຈັກ · Engine ──
            ['group' => 'engine', 'label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຄື່ອງ · ປ່ຽນ', 'remark' => 'SAE 15W-40 · 1.4 gal · I:50h', 'cycles' => ['daily' => 'C', 'monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'engine', 'label' => 'ປ່ຽນ ໝໍ້ກອງ ນ້ຳມັນ ເຄື່ອງ', 'remark' => '20801-01271 · 1pc', 'cycles' => ['monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'engine', 'label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ປ່ຽນ ໝໍ້ກອງ ເຊື້ອໄຟ', 'remark' => '20801-02141 · 1pc', 'cycles' => ['quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'engine', 'label' => 'ກວດ ຄວາມຕຶງ/ເສຍຫາຍ ສາຍພານ (ປັບ ຖ້າ ຈຳເປັນ)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳໝໍ້ນ້ຳ · ປ່ຽນ', 'remark' => '2.9 gal', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'engine', 'label' => 'ກວດ ສີ ຄວັນ ໄອເສຍ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ທຳຄວາມສະອາດ/ປ່ຽນ ໝໍ້ກອງ ອາກາດ', 'remark' => '20801-03521', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'engine', 'label' => 'ລະບາຍ ນ້ຳ ອອກ ຈາກ ຖັງ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ທຳຄວາມສະອາດ ຖັງ ເຊື້ອໄຟ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ກວດ ໄລຍະ ວາລ໌ວ (valve clearance)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'engine', 'label' => 'ຂັນ ນັອດ ຝາສູບ', 'remark' => '', 'cycles' => ['annual' => 'C']],
            ['group' => 'engine', 'label' => 'ກວດ ກຳລັງອັດ (compression)', 'remark' => '', 'cycles' => ['annual' => 'C']],

            // ── ລະບົບ ສົ່ງກຳລັງ · Power Train ──
            ['group' => 'powertrain', 'label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ ເກຍ (transmission)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'powertrain', 'label' => 'ກວດ ລະດັບ ນ້ຳມັນ ເກຍ · ປ່ຽນ', 'remark' => 'SAE 10W · 2.4 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'powertrain', 'label' => 'ປ່ຽນ line filter element', 'remark' => '115G3-82242 · 1pc · M:200h', 'cycles' => ['monthly' => 'X']],
            ['group' => 'powertrain', 'label' => 'ທຳຄວາມສະອາດ suction strainer · ປ່ຽນ', 'remark' => '12N53-89811 · 1pc', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'powertrain', 'label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ ເຟືອງ (differential)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'powertrain', 'label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເຟືອງ · ປ່ຽນ', 'remark' => 'SAE 90 · 1.58 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'powertrain', 'label' => 'ກວດ ຄວາມຫຼວມ & ສຽງ ຜິດປົກກະຕິ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'powertrain', 'label' => 'ຖອດ & ອັດ ຈາລະບີ ລູກປືນລໍ້ (wheel bearing)', 'remark' => '', 'cycles' => ['semi_annual' => 'X', 'annual' => 'X']],

            // ── ບັງຄັບລ້ຽວ · Steering ──
            ['group' => 'steering', 'label' => 'ກວດ ການເຮັດວຽກ ປົກກະຕິ (ບັງຄັບລ້ຽວ)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'steering', 'label' => 'ກວດ ຮົ່ວ ນ້ຳມັນ orbitrol · ກະບອກ · ທໍ່', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'steering', 'label' => 'ກວດ king pins · ຂໍ້ຕໍ່ (joints)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ລະບົບ ຍົກ · Loading / Mast ──
            ['group' => 'mast', 'label' => 'ກວດ ຄວາມຕຶງ/ຮູບຊົງ/ເສຍຫາຍ ຂອງ ໂສ້', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'mast', 'label' => 'ກວດ roller · pin · ຮອຍ ເຊື່ອມ ແຕກ/ເສຍຫາຍ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'mast', 'label' => 'ອັດ ຈາລະບີ ໂສ້ (lubricate chains)', 'remark' => '', 'cycles' => ['monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'mast', 'label' => 'ກວດ chain anchor pins · ງາ (forks) ເສຍຮູບ/ສຶກ', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ໄຮໂດຼລິກ · Hydraulic ──
            ['group' => 'hydraulic', 'label' => 'ກວດ ລະດັບ ນ້ຳມັນ ໄຮໂດຼລິກ · ປ່ຽນ', 'remark' => 'ISO VG32 · 8.5 gal', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'hydraulic', 'label' => 'ທຳຄວາມສະອາດ suction strainer · ປ່ຽນ', 'remark' => '216G7-52051 · 1pc', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'hydraulic', 'label' => 'ປ່ຽນ return filter', 'remark' => '271A7-52301 · 1pc', 'cycles' => ['semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'hydraulic', 'label' => 'ກວດ ການເຮັດວຽກ ປົກກະຕິ · main relief pressure', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'hydraulic', 'label' => 'ກວດ ຮົ່ວ (hydraulic leakage)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ໄຟຟ້າ · Electrical ──
            ['group' => 'electrical', 'label' => 'ກວດ ການ ສະຕາດ & ຊາດ (charging)', 'remark' => '', 'cycles' => ['quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'electrical', 'label' => 'ກວດ ຄ່າ ຄວາມຖ່ວງ ຈຳເພາະ ນ້ຳກົດ', 'remark' => '', 'cycles' => ['semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'electrical', 'label' => 'ກວດ ລະດັບ ນ້ຳກົດ (electrolyte)', 'remark' => '', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],

            // ── ອື່ນໆ · Others ──
            ['group' => 'other', 'label' => 'ກວດ ໂຄງກັນຫຼັງຄາ (overhead guard) ແຕກ/ເສຍຮູບ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'other', 'label' => 'ກວດ ໂຮນ · ໄຟ ທຸກ ໜ່ວຍ', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'other', 'label' => 'ກວດ ລະດັບ/ຮົ່ວ ນ້ຳມັນ ເບຣກ · ປ່ຽນ', 'remark' => '0.2 L', 'cycles' => ['monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'X', 'annual' => 'X']],
            ['group' => 'other', 'label' => 'ກວດ master/wheel cylinder · ປ່ຽນ ຖ້າ ຈຳເປັນ', 'remark' => '', 'cycles' => ['annual' => 'X']],
            ['group' => 'other', 'label' => 'ກວດ ລົມ ຢາງ (tire pressure)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
            ['group' => 'other', 'label' => 'ຍ່າງ ກວດ ຮອບ ຄັນ (walk-around)', 'remark' => '', 'cycles' => ['daily' => 'C', 'monthly' => 'C', 'quarterly' => 'C', 'semi_annual' => 'C', 'annual' => 'C']],
        ];

        // ປະເພດ ລົດ: ໝວດ ເຄື່ອງຈັກ/ສົ່ງກຳລັງ = engine (ດີເຊລ) · ອື່ນໆ = both. ຍົກເວັ້ນ ລູກປືນລໍ້ = both (ມີ ທັງ 2).
        return array_map(function ($it) {
            $applies = in_array($it['group'], ['engine', 'powertrain'], true) ? 'engine' : 'both';
            if (str_contains($it['label'], 'ລູກປືນລໍ້')) {
                $applies = 'both';
            }

            return $it + ['applies' => $applies];
        }, $items);
    }

    public function run(): void
    {
        MaintenanceTemplate::firstOrCreate(
            ['name' => self::NAME],
            [
                'equipment_id' => null,        // master — ບໍ່ ຜູກ ເຄື່ອງ ໜຶ່ງ ໜ່ວຍ
                'category' => self::CATEGORY,  // ຂຶ້ນ ຕາມ ປະເພດ ເຄື່ອງ (ລົດ)
                'method' => 'ໃບ ບຳລຸງ ຕາມ ຮອບ ຊົ່ວໂມງ (Daily/8h · 200h · 600h · 1200h · 2400h). C = ກວດ · X = ປ່ຽນ. ຖອດ ຈາກ ໃບ ບຳລຸງ ມາດຕະຖານ forklift, ທຽບກວດ OSHA/ໂຮງງານ.',
                'items' => self::items(),
                'is_active' => true,
            ]
        );
    }
}
