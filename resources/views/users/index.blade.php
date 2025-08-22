@extends('include.main')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
  <div class="card w-75 mt-5">
    <div class="card-body">
      <table id="example" class="display">
        <thead>
          <tr>
            <th>email</th>
            <th>ชื่อ</th>
            <th>ตำแหน่ง</th>
            <th>หน่วยงาน</th>
            <th>ผู้รับแทงเรื่อง</th>
            <th>แก้ไข</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="parentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">กำหนดผู้บังคับบัญชา</h5></div>
      <div class="modal-body">
        <div class="mb-2">เลือกสิทธิ์ที่จะเป็นผู้บังคับบัญชา</div>
        <select id="parentSelect" class="form-select"></select>
        <input type="hidden" id="modalPermissionId">
        <input type="hidden" id="modalPositionId">
        <input type="hidden" id="modalUserIdForRetry">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button id="btnSaveParent" class="btn btn-primary">บันทึก</button>
      </div>
    </div>
  </div>
</div>
@endsection

{{-- เรียกใช้ไฟล์ JS แยก --}}
@extends('users.js.index')
