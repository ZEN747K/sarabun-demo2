<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Str;

class PermissionParentSeeder extends Seeder
{
    public function run()
    {
        // เคลียร์ค่าเดิมก่อน (ถ้าอยาก)
        // Permission::query()->update(['parent_id' => null]);

        // 1) หา "นายก" (ท็อปสุดขององค์กร)
        $mayors = Permission::query()
            ->where(function($q){
                $q->where('permission_name', 'like', '%นายก%')
                  ->where('permission_name', 'not like', '%รอง%');   // กัน รองนายก
            })
            ->get();

        if ($mayors->isEmpty()) {
            $this->command->warn('ไม่พบนายก (permission_name like "%นายก%" แต่ไม่ใช่รองนายก)');
        }

        // สำหรับ lookup แบบเร็ว
        $mayorId = optional($mayors->first())->id;

        // 2) หา “ปลัด”
        $permanentSecretaries = Permission::query()
            ->where('permission_name', 'like', '%ปลัด%')
            ->where('permission_name', 'not like', '%รอง%') // กันรองปลัด
            ->get();
        $permSecIds = $permanentSecretaries->pluck('id')->all();
        $permSecId = $permanentSecretaries->first()->id ?? null;

        // 3) ตั้ง parent_id ของระดับต่าง ๆ ตามกฎ
        // 3.1 รองนายก -> นายก
        Permission::query()
            ->where('permission_name', 'like', '%รองนายก%')
            ->update(['parent_id' => $mayorId]);

        // 3.2 ปลัด -> นายก
        if ($permSecId) {
            Permission::query()
                ->whereIn('id', $permSecIds)
                ->update(['parent_id' => $mayorId]);
        }

        // 3.3 รองปลัด -> ปลัด
        Permission::query()
            ->where(function($q){
                $q->where('permission_name', 'like', '%รองปลัด%')
                  ->orWhere(function($q2){
                      // กันชื่อเขียนแบบอื่น ๆ เช่น "รอง ปลัด"
                      $q2->where('permission_name', 'like', '%รอง%')
                         ->where('permission_name', 'like', '%ปลัด%');
                  });
            })
            ->update(['parent_id' => $permSecId]);

        // 3.4 หัวหน้าส่วน/ผอ./ผู้อำนวยการกอง -> ปลัด
        Permission::query()
            ->where(function($q){
                $q->where('permission_name', 'like', '%หัวหน้าส่วน%')
                  ->orWhere('permission_name', 'like', '%ผอ.%')
                  ->orWhere('permission_name', 'like', '%ผู้อำนวยการ%')
                  ->orWhere('permission_name', 'like', '%หัวหน้ากอง%');
            })
            ->update(['parent_id' => $permSecId]);

        // 3.5 เจ้าหน้าที่/นักวิชาการ/ผู้ช่วย/พนักงาน ฯลฯ -> หัวหน้าส่วนของ “กองเดียวกัน”
        //    ถ้าไม่พบหัวหน้าส่วน ให้ fallback ไปที่ “ปลัด”
        $all = Permission::query()->get();

        // สร้างดัชนีหัวหน้าส่วนตามกอง/หน่วย (ใช้ position_id ถ้ามีในตาราง)
        $heads = Permission::query()
            ->where(function($q){
                $q->where('permission_name', 'like', '%หัวหน้าส่วน%')
                  ->orWhere('permission_name', 'like', '%ผอ.%')
                  ->orWhere('permission_name', 'like', '%ผู้อำนวยการ%')
                  ->orWhere('permission_name', 'like', '%หัวหน้ากอง%');
            })
            ->get()
            ->groupBy('position_id'); // ต้องมีคอลัมน์ position_id ตามโค้ดของคุณ

        foreach ($all as $p) {
            $name = $p->permission_name;

            // ข้ามระดับบนที่ตั้งไปแล้ว
            if (
                Str::contains($name, 'นายก') && !Str::contains($name, 'รอง')
            ) continue;
            if (
                Str::contains($name, 'ปลัด') && !Str::contains($name, 'รอง')
            ) continue;
            if (Str::contains($name, 'รองนายก')) continue;
            if (Str::contains($name, 'รองปลัด')) continue;

            // จับกลุ่ม "เจ้าหน้าที่/นักวิชาการ/ผู้ช่วย/พนักงาน/จนท."
            $isStaff =
                Str::contains($name, 'เจ้าหน้าที่') ||
                Str::contains($name, 'นักวิชาการ') ||
                Str::contains($name, 'ผู้ช่วย') ||
                Str::contains($name, 'พนักงาน') ||
                Str::contains($name, 'จนท') ||
                Str::contains($name, 'เจ้าพนักงาน');

            if ($isStaff) {
                $parentId = $permSecId; // ค่า fallback

                // ถ้าในกอง/หน่วยมีหัวหน้า ก็ชี้ไปที่หัวหน้านั้น
                $headInSameUnit = ($heads[$p->position_id] ?? collect())->first();
                if ($headInSameUnit) {
                    $parentId = $headInSameUnit->id;
                }

                if ($p->parent_id !== $parentId) {
                    $p->parent_id = $parentId;
                    $p->save();
                }
            }
        }

        $this->command->info('อัปเดต parent_id เรียบร้อย');
    }
}
