@extends('include.main')

@section('style')
<style>
  body { font-family: 'Noto Sans Thai'; }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
  <div class="card w-75 mt-5">
    <div class="card-header d-flex justify-content-end">
      <a href="{{ url('/users/add/form') }}" class="btn btn-outline-primary">เพิ่ม User</a>
    </div>

    <div class="card-body">
      <table id="users-table" class="display" style="width:100%">
        <thead>
          <tr>
            <th>email</th>
            <th>ชื่อ</th>
            <th>ตำแหน่ง</th>
            <th>หน่วยงาน</th>
            <th>แก้ไข</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@section('script')
  {{-- DataTables + Select2 --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  @if(session('success'))
  <script> Swal.fire({ title:'บันทึกข้อมูลเรียบร้อย', icon:'success' }); </script>
  @endif

  <script>
  $(function () {
    const table = $('#users-table').DataTable({
      ajax: '{{ route('users.listData') }}',   
      processing: true,
      serverSide: false,
      paging: true,
      searching: true,
      order: [[0, 'asc']],
      language: {
        search: 'ค้นหา:',
        lengthMenu: 'แสดง _MENU_ รายการ',
        info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
        infoEmpty: 'ไม่พบข้อมูล',
        zeroRecords: 'ไม่พบข้อมูล',
        paginate: { first:'แรก', last:'สุดท้าย', next:'ถัดไป', previous:'ก่อนหน้า' }
      },
      columns: [
        { data: 'email' },                     
        { data: 'fullname' },                  
        { data: 'role_name' },                 
        { data: 'department', defaultContent: '-' }, 
        {
          data: null,
          orderable: false,
          searchable: false,
          render: row => {
            return `
              <a href="/users/${row.id}/edit" class="btn btn-sm btn-outline-secondary">แก้ไข</a>
            `;
          }
        }
      ]
    });
  });
  </script>
@endsection
