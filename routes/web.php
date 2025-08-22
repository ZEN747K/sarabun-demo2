<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BooksenderController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| - หน้า login / logout
| - กลุ่มที่ต้องล็อกอิน (auth.admin): หนังสือ, สมาชิก, สิทธิ์, ติดตาม, แฟ้มเอกสาร
| - ผู้รับแทงเรื่อง (toggle receiver) + ตั้ง parent_id ทำบนเว็บได้
*/

Route::get('/', fn () => view('login'));
Route::post('/login/auth', [LoginController::class, 'auth'])->name('login.auth');             
Route::get('/login/logout', [LoginController::class, 'logout'])->name('login.logout');        

// --------------------------- Protected (ต้องผ่าน middleware auth.admin) ---------------------------
Route::middleware('auth.admin')->group(function () {

    // --------------------------- หนังสือเข้า (Book) ---------------------------
    Route::get('/book', [BookController::class, 'index'])->name('book.index');                               // หน้ารับหนังสือ (แอดมิน)
    Route::get('/book/getEmail', [BookController::class, 'getEmail'])->name('book.getEmail');                // ดึงอีเมล/ไฟล์แนบจาก IMAP
    Route::get('/book/show', [BookController::class, 'show'])->name('book.show');                            // หน้าแสดงรายการหนังสือตามสิทธิ์
    Route::post('/book/bookType', [BookController::class, 'bookType'])->name('book.bookType');               // เปลี่ยนประเภทเพื่อรันเลขรับ
    Route::post('/book/save', [BookController::class, 'save'])->name('book.save');                           // บันทึกนำเข้าเอกสาร
    Route::post('/book/dataListSearch', [BookController::class, 'dataListSearch'])->name('book.dataListSearch'); // ค้นหารายการ (Ajax)
    Route::post('/book/dataList', [BookController::class, 'dataList'])->name('book.dataList');               // ดึงรายการแบ่งหน้า (Ajax)

    Route::post('/book/save_stamp', [BookController::class, 'save_stamp'])->name('book.save_stamp');         // ประทับรับเข้า (ส่วนกลาง)
    Route::post('/book/send_to_admin', [BookController::class, 'send_to_admin'])->name('book.send_to_admin');// แทงเรื่องไปหน่วยงาน/ตำแหน่งที่เลือก
    Route::post('/book/send_to_adminParent', [BookController::class, 'send_to_adminParent'])->name('book.send_to_adminParent'); // แทงเรื่องไปผู้บังคับบัญชา

    Route::post('/book/admin_stamp', [BookController::class, 'admin_stamp'])->name('book.admin_stamp');      // หน่วยงานประทับรับ
    Route::post('/book/admin_stampParent', [BookController::class, 'admin_stampParent'])->name('book.admin_stampParent'); // (มีใน route เดิม)

    Route::post('/book/checkbox_send', [BookController::class, 'checkbox_send'])->name('book.checkbox_send');        // โหลดผู้รับในสายบังคับบัญชา (เช็คบ็อกซ์)
    Route::post('/book/_checkbox_send', [BookController::class, '_checkbox_send'])->name('book._checkbox_send');     // โหลดผู้รับทุกตำแหน่ง (เช็คบ็อกซ์)

    Route::post('/book/send_to_save', [BookController::class, 'send_to_save'])->name('book.send_to_save');   // บันทึกผลการแทงเรื่อง (ไปบุคคล)
    Route::post('/book/confirm_signature', [BookController::class, 'confirm_signature'])->name('book.confirm_signature'); // ยืนยันรหัสผ่านสำหรับลายเซ็น
    Route::post('/book/signature_stamp', [BookController::class, 'signature_stamp'])->name('book.signature_stamp');     // ประทับลายเซ็น/ความเห็น
    Route::post('/book/manager_stamp', [BookController::class, 'manager_stamp'])->name('book.manager_stamp');           // ผู้บริหารเซ็น
    Route::post('/book/uploadPdf', [BookController::class, 'uploadPdf'])->name('bookSender.uploadPdf');       // อัพโหลดไฟล์ PDF แทน
    Route::post('/book/number_save', [BookController::class, 'number_save'])->name('bookSender.number_save'); // ประทับเลขที่จอง
    Route::post('/book/directory_save', [BookController::class, 'directory_save'])->name('bookSender.directory_save'); // จัดเก็บเข้าแฟ้ม/ไดเรกทอรี
    Route::post('/book/reject', [BookController::class, 'reject'])->name('book.reject');                     // ปฏิเสธหนังสือ/ตีกลับ
    Route::post('/book/edit_stamp', [BookController::class, 'edit_stamp'])->name('book.edit_stamp');         // แก้ไขตำแหน่ง/เวลาในตรารับ
    Route::get('/book/stamp_position/{id}', [BookController::class, 'get_stamp_position'])->name('book.stamp_position'); // ดึงพิกัดตรารับล่าสุด
    Route::post('/book/check_admin', [BookController::class, 'check_admin'])->name('book.check_admin');       // ตรวจสิทธิ์แอดมินด้วยรหัสผ่าน
    Route::get('/book/created_position/{id}', [BookController::class, 'created_position'])->name('book.created_position'); // หาตำแหน่งของผู้ที่สร้างหนังสือ

    // --------------------------- จัดการสมาชิก (Users) ---------------------------
    Route::get('/users/listUsers', [UsersController::class, 'listUsers'])->name('users.listUsers');           // หน้า “ข้อมูลสมาชิก”
    Route::get('/users/add', [UsersController::class, 'add'])->name('users.add');                             // หน้าเพิ่มผู้ใช้
    Route::get('/users/add/form', [UsersController::class, 'addForm'])->name('users.addForm');                // ฟอร์มเพิ่มผู้ใช้
    Route::post('/users/add/save', [UsersController::class, 'insertUser'])->name('users.insertUser');         // บันทึกผู้ใช้ใหม่
    Route::get('/users/listData', [UsersController::class, 'listData'])->name('users.listData');              // ข้อมูล DataTable “ผู้ใช้”
    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('users.edit');                     // หน้าแก้ไขผู้ใช้
    Route::post('/users/save', [UsersController::class, 'save'])->name('users.save');                         // บันทึกแก้ไขผู้ใช้

    Route::get('/users/change_role/{id}', [UsersController::class, 'change_role'])->name('users.change_role');// สลับบทบาท (role) ตามสิทธิ์รอง
    Route::get('/users/permission/{id}', [UsersController::class, 'edit_permission'])->name('users.permission'); // หน้าแก้ไขสิทธิ์รองของผู้ใช้
    Route::get('/users/listDataPermission', [UsersController::class, 'listDataPermission'])->name('users.listDataPermission'); // DataTable สิทธิ์รอง
    Route::get('/users/create_permission/{id}', [UsersController::class, 'create_permission'])->name('users.create_permission'); // ฟอร์มเพิ่มสิทธิ์รอง
    Route::get('/users/form_permission/{id}', [UsersController::class, 'form_permission'])->name('users.form_permission');       // ฟอร์มแก้ไขสิทธิ์รอง
    Route::post('/users/updateCanStatus', [UsersController::class, 'updateCanStatus'])->name('users.updateCanStatus'); // อัพเดต can_status ของ permission
    Route::post('/users/insertPermission', [UsersController::class, 'insertPermission'])->name('users.insertPermission');       // บันทึกสิทธิ์รองใหม่
    Route::post('/users/updatePermission', [UsersController::class, 'updatePermission'])->name('users.updatePermission');       // บันทึกแก้ไขสิทธิ์รอง
    Route::post('/users/getPermission', [UsersController::class, 'getPermission'])->name('users.getPermission');                 // ดึงรายการ permission ตามตำแหน่ง
    Route::get('/users/delete/{id}', [UsersController::class, 'deletePermission'])->name('users.deletePermission');              // ลบสิทธิ์รอง
    Route::get('/users/sync', [UsersController::class, 'sync'])->name('users.sync');                                            // sync สิทธิ์รองตัวแรกมาเป็นสิทธิ์หลัก

    // --- ตั้ง/ยกเลิก “ผู้รับแทงเรื่อง” จากหน้าเว็บ (ไม่ต้องกรอก DB ล่วงหน้า) ---
    Route::post('/users/toggle-receiver', [UsersController::class, 'toggleReceiver'])->name('users.toggle_receiver');           // ปุ่ม “ตั้งเป็นผู้รับ/ยกเลิกผู้รับ”

    // --- กำหนดสายบังคับบัญชา (parent_id) ผ่านหน้าเว็บ ---
    Route::get('/permissions/parent-options', [UsersController::class, 'parentOptions'])->name('permissions.parent_options');   // ตัวเลือก parent ตาม position_id (ใช้ใน modal)
    Route::post('/permissions/set-parent', [UsersController::class, 'setParent'])->name('permissions.set_parent');               // บันทึก parent_id ให้ permission

    // --------------------------- ติดตามงานเอกสาร (Tracking) ---------------------------
    Route::get('/tracking', [TrackController::class, 'index'])->name('tracking.index');                         // หน้ารวมรายงาน
    Route::get('/tracking/detail/{id}', [TrackController::class, 'detail'])->name('tracking.detail');           // รายละเอียดเอกสาร
    Route::post('/tracking/dataReportMain', [TrackController::class, 'dataReportMain'])->name('tracking.dataReportMain'); // รายงานหลัก (Ajax)
    Route::post('/tracking/dataReportDetail', [TrackController::class, 'dataReportDetail'])->name('tracking.dataReportDetail'); // รายงานย่อย (Ajax)
    Route::post('/tracking/getDetailAll', [TrackController::class, 'getDetailAll'])->name('tracking.getDetailAll');             // รวมรายละเอียดทั้งหมด (Ajax)

    // --------------------------- หนังสือออก (Sender) ---------------------------
    Route::get('/bookSender', [BooksenderController::class, 'index'])->name('bookSender.index');                // หน้าออกหนังสือ
    Route::get('/listSender', [BooksenderController::class, 'listSender'])->name('bookSender.listSender');      // รายการหนังสือออก
    Route::post('/listSender/listData', [BooksenderController::class, 'listData'])->name('bookSender.listData');// DataTable หนังสือออก
    Route::post('/bookSender/bookType', [BooksenderController::class, 'bookType'])->name('bookSender.bookType');// ประเภทหนังสือออก
    Route::post('/bookSender/getPosition', [BooksenderController::class, 'getPosition'])->name('bookSender.getPosition'); // ดึงตำแหน่งผู้รับ
    Route::post('/bookSender/save', [BooksenderController::class, 'save'])->name('bookSender.save');           // บันทึกหนังสือออก

    // --------------------------- สิทธิ์/ตำแหน่ง (Permission) ---------------------------
    Route::get('/permission', [PermissionController::class, 'index'])->name('permission.index');                // หน้าจัดการสิทธิ์
    Route::get('/permission/create/{id}', [PermissionController::class, 'create'])->name('permission.create');  // สร้างสิทธิ์ใหม่ (ผูก position)
    Route::get('/permission/detail/{id}', [PermissionController::class, 'detail'])->name('permission.detail');  // รายละเอียดสิทธิ์
    Route::get('/permission/edit/{id}', [PermissionController::class, 'edit'])->name('permission.edit');        // แก้ไขสิทธิ์
    Route::get('/permission/listData', [PermissionController::class, 'listData'])->name('permission.listData'); // DataTable สิทธิ์
    Route::get('/permission/listDataPermission', [PermissionController::class, 'listDataPermission'])->name('permission.listDataPermission'); // DataTable สิทธิ์-ตำแหน่ง
    Route::post('/permission/save', [PermissionController::class, 'save'])->name('permission.save');            // บันทึก/อัพเดตสิทธิ์

    // --------------------------- แฟ้มจัดเก็บ (Directory) ---------------------------
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');                   // หน้าแฟ้มเอกสาร
    Route::get('/directory/create_directory', [DirectoryController::class, 'create_directory'])->name('directory.create_directory'); // ฟอร์มสร้างแฟ้ม
    Route::post('/directory/listData', [DirectoryController::class, 'listData'])->name('directory.listData');   // DataTable แฟ้มเอกสาร
});

// --------------------------- อีเมล (หน้า public) ---------------------------
Route::get('/email', [EmailController::class, 'index'])->name('email.index');          

require __DIR__ . '/auth.php';
