### 2. Approval only to Director
- Mapping & label:
  - `sekre` / `sekretariat` → `TOP_LEVEL_ROLES` ✓
  - `getRoleLabelAttribute()` ✓
  - `UserSeeder` role map `SEKRETARIAT => sekre` ✓
- Perubahan yang dilakukan:
  - `ApprovalFlowService::ROLE_GROUPS` → `'sekre' => 'direktur', 'sekretariat' => 'direktur'`
  - `ApprovalFlowService::TOP_LEVEL_ROLES` → `'sekre', 'sekretariat'`
  - `ApprovalFlowService::assignableRoles()` otomatis termasuk karena gabung `ROLE_GROUPS` + `APPROVER_STAGES`
  - `User::getRoleLabelAttribute()` → `'sekretariat' => 'SEKRETARIAT'`
  - `UserSeeder` role map → `'SEKRETARIAT' => 'sekre'`
  - Form admin user (`hrd/users/approval`) daftar role dropdown otomatis mengikut karena memakai `ApprovalFlowService::assignableRoles()`.
  - **Lihat catatan di bagian ringkasan bawah dokumen tentang `hrd` yang saat ini tidak muncul di dropdown form approval user.**

Status sekre/sekretariat sekarang: tanpa tahap PJ, langsung ke Direktur, label tampilan tersedia, seeder siap pakai.