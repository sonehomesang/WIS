<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** ໃບ ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ (Disposal). */
class DisposalRecord extends Model
{
    use SoftDeletes;

    /** ເຫດຜົນ ຈຳໜ່າຍ ສຳເລັດຮູບ (ຕໍ່ ລາຍການ) — dropdown + ພິມ ເພີ່ມ. */
    public const REASONS = ['ຊຳລຸດ / ເສຍຫາຍ', 'ໝົດ ອາຍຸ ໃຊ້ງານ', 'ລ້າສະໄໝ / ຕົກລຸ້ນ', 'ສູນຫາຍ', 'ອື່ນໆ'];

    /** ຄຳແນະນຳ ສຳເລັດຮູບ. */
    public const RECOMMENDATIONS = ['ທຳລາຍ', 'ຂາຍ ເສດ', 'ບໍລິຈາກ', 'ສົ່ງ ຄືນ ຜູ້ສະໜອງ', 'ອື່ນໆ'];

    /** ປ້າຍ ສະຖານະ (Lao). in_review = ຮອບ ຮັບຮອງ ແບບ ຂະໜານ (assigned endorsers).
     *  committee/technical/manager/executive_review = ຄ້າງ ຈາກ ຮູບ ແບບ linear ເກົ່າ (backward-compat). */
    public const STATUS_LABELS = [
        'draft' => 'ຮ່າງ', 'in_review' => 'ກຳລັງ ຮັບຮອງ',
        'committee_review' => 'ລໍ ຄະນະ ກວດ', 'technical_review' => 'ລໍ ວິຊາການ',
        'manager_review' => 'ລໍ ຜູ້ຈັດການ', 'executive_review' => 'ລໍ ຜູ້ບໍລິຫານ', 'approved' => 'ອະນຸມັດ ແລ້ວ',
        'disposed' => 'ຈຳໜ່າຍ ແລ້ວ', 'rejected' => 'ຕີ ກັບ', 'cancelled' => 'ຍົກເລີກ',
    ];

    /** 5 ຂັ້ນ ເຊັນ — order + ຊື່. */
    public const STAGES = [
        'preparer' => ['order' => 1, 'label' => 'ຜູ້ ເຮັດລິສ'],
        'committee' => ['order' => 2, 'label' => 'ຄະນະກຳມະການ ຮ່ວມກວດ'],
        'technical' => ['order' => 3, 'label' => 'ວິຊາການ / ວິສະວະກອນ'],
        'manager' => ['order' => 4, 'label' => 'ຜູ້ ຈັດການ'],
        'executive' => ['order' => 5, 'label' => 'ຜູ້ ບໍລິຫານ'],
    ];

    // ກັນ overposting: ຄໍລັມ workflow/ສິດ ຕັ້ງ ໄດ້ ສະເພາະ server (forceFill/direct assignment) — ບໍ່ ຮັບ ຈາກ mass-assign.
    protected $guarded = ['id', 'status', 'prepared_by_user_id', 'registers_updated_by', 'registers_updated_at', 'deleted_by', 'deleted_reason'];

    protected $casts = [
        'prepared_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'registers_updated_at' => 'datetime',
        'original_deposit_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DisposalItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(DisposalSignoff::class, 'record_id')->orderBy('stage_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DisposalHistory::class, 'record_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** role_key ຂອງ ຂັ້ນ ທີ່ ຖ້າ ເຊັນ ຢູ່ (null = ບໍ່ ຢູ່ ໃນ ຂັ້ນ ເຊັນ). */
    public function currentStageKey(): ?string
    {
        return match ($this->status) {
            'committee_review' => 'committee',
            'technical_review' => 'technical',
            'manager_review' => 'manager',
            'executive_review' => 'executive',
            default => null,
        };
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** ຍັງ ແກ້ໄຂ/ສົ່ງ ໄດ້ ບໍ (draft). */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    // ── assigned-endorser model ─────────────────────────────────────
    /** signoff ທີ່ ມອບໝາຍ ໃຫ້ ຄົນ ແລ້ວ (user_id ≠ null). */
    public function assignedSignoffs()
    {
        return $this->signoffs->filter(fn ($s) => $s->user_id !== null);
    }

    /** ຮັບຮອງ ຄົບ ບໍ — ມີ ຢ່າງໜ້ອຍ 1 ຜູ້ ຮັບຮອງ ແລະ ທຸກ ຄົນ ເຊັນ ຮັບຮອງ ແລ້ວ. */
    public function endorsementComplete(): bool
    {
        $assigned = $this->assignedSignoffs();

        return $assigned->isNotEmpty() && $assigned->every(fn ($s) => $s->isSigned());
    }
}
