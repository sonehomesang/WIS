<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

/**
 * Delete-with-reason + Deleted Log for components that manage SEVERAL entity
 * types in one screen (e.g. Organization = units + departments, Facilities =
 * locations + buildings + rooms + building_types).
 *
 * The single-model {@see SoftDeletesWithReason} keys everything off one model;
 * this variant keys off a `type` string. It reuses the same shared modal
 * (resources/views/partials/_delete-modal.blade.php) — that partial only needs
 * $deletingId, deleteReason, deleteRecord() and deletingRecord().
 *
 * The component MUST implement deletableTypes():
 *   ['unit' => ['model' => Unit::class, 'perm' => 'units', 'noun' => 'ໜ່ວຍງານ'], …]
 * It MAY override deleteBlockReason() (guard, e.g. "still has children") and the
 * afterDelete()/afterRestore() hooks (fix up selected ids). Each model must use
 * SoftDeletes and have deleted_reason + deleted_by + a deletedBy() relation.
 */
trait MultiSoftDeletesWithReason
{
    /** ພາກ ທີ່ ກຳລັງ ໂຊ Deleted Log ຢູ່ (type) — '' = ບໍ່ ມີ. */
    public string $showDeletedType = '';

    public string $deletingType = '';

    public ?int $deletingId = null;

    public string $deleteReason = '';

    /** @return array<string, array{model:class-string,perm:string,noun:string}> */
    abstract protected function deletableTypes(): array;

    /** Override: return an error string to block deletion (e.g. has children), or null to allow. */
    protected function deleteBlockReason(string $type, Model $record): ?string
    {
        return null;
    }

    /** Hook after a successful soft-delete (e.g. move selection off the deleted row). */
    protected function afterDelete(string $type, Model $record): void {}

    /** Hook after a successful restore. */
    protected function afterRestore(string $type, Model $record): void {}

    protected function typeConfig(string $type): array
    {
        return $this->deletableTypes()[$type] ?? abort(404);
    }

    /** ຜູ້ ໃຊ້ ຈັດການ Deleted Log ຂອງ type ນີ້ ໄດ້ ບໍ (perm.delete). */
    public function canManageDeletedType(string $type): bool
    {
        $u = auth()->user();
        $perm = $this->deletableTypes()[$type]['perm'] ?? null;

        return $perm !== null && ($u->is_super_admin || $u->can("{$perm}.delete"));
    }

    /** ສະຫຼັບ ໂຊ Deleted Log ຂອງ ພາກ ໜຶ່ງ (ພາກ ອື່ນ ກັບ ໄປ ປົກກະຕິ). */
    public function toggleDeletedLog(string $type): void
    {
        abort_unless($this->canManageDeletedType($type), 403);
        $this->showDeletedType = $this->showDeletedType === $type ? '' : $type;
    }

    /** ເປີດ modal ຖາມ ເຫດຜົນ ກ່ອນ ລຶບ. */
    public function openDelete(string $type, int $id): void
    {
        abort_unless($this->canManageDeletedType($type), 403);
        $record = $this->typeConfig($type)['model']::findOrFail($id);
        if ($msg = $this->deleteBlockReason($type, $record)) {
            $this->addError('row', $msg);

            return;
        }
        $this->deletingType = $type;
        $this->deletingId = $id;
        $this->deleteReason = '';
        $this->resetValidation();
    }

    /** ຢືນຢັນ ລຶບ — ບັນທຶກ ເຫດຜົນ + ຜູ້ລຶບ ແລ້ວ soft-delete. */
    public function deleteRecord(): void
    {
        abort_unless($this->deletingType !== '' && $this->canManageDeletedType($this->deletingType), 403);
        $this->validate(
            ['deleteReason' => ['required', 'string', 'max:500']],
            ['deleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']
        );
        $cfg = $this->typeConfig($this->deletingType);
        $record = $cfg['model']::findOrFail($this->deletingId);
        if ($msg = $this->deleteBlockReason($this->deletingType, $record)) {
            $this->addError('row', $msg);

            return;
        }
        $type = $this->deletingType;
        $record->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => auth()->id()])->save();
        $record->delete();
        $this->afterDelete($type, $record);
        session()->flash('ok', '✓ ລຶບ '.$cfg['noun'].' '.$record->name.' (ຍ້າຍ ໄປ Deleted Log)');
        $this->deletingType = '';
        $this->deletingId = null;
        $this->deleteReason = '';
    }

    /** ກູ້ຄືນ ຈາກ Deleted Log. */
    public function restoreRecord(string $type, int $id): void
    {
        abort_unless($this->canManageDeletedType($type), 403);
        $cfg = $this->typeConfig($type);
        $record = $cfg['model']::onlyTrashed()->find($id);
        if ($record) {
            $record->restore();
            $record->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $this->afterRestore($type, $record);
            session()->flash('ok', '✓ ກູ້ຄືນ '.$cfg['noun'].' '.$record->name);
        }
    }

    /** ບັນທຶກ ທີ່ ກຳລັງ ຈະ ລຶບ — ໃຊ້ ໃນ modal partial. */
    #[Computed]
    public function deletingRecord(): ?Model
    {
        if (! $this->deletingId || $this->deletingType === '') {
            return null;
        }

        return $this->typeConfig($this->deletingType)['model']::find($this->deletingId);
    }
}
