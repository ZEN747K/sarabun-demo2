@section('script')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
@if(session('success'))
<script>
    Swal.fire({
        title: "บันทึกข้อมูลเรียบร้อย",
        icon: "success",
    });
</script>
@endif
<script>
$(function () {
  $('#example').DataTable({
    ajax: {
      url: '/users/listData',
      type: 'GET'
    },
    columns: [
      { data: 'email' },
      { data: 'fullname' },
      { data: 'permission_name' },
      { data: 'position_name' },
      { data: 'receiver_button', orderable:false, searchable:false },
      { data: 'action', orderable:false, searchable:false }
    ]
  });

  // toggle ผู้รับแทงเรื่อง
  $(document).on('click','.btn-toggle-receiver', function () {
    const id = $(this).data('id');
    $.ajax({
      url: '/users/toggle-receiver',
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      data: { id },
      success: (res) => {
        $('#example').DataTable().ajax.reload(null,false);
        Swal.fire('', res.msg, 'success');
      },
      error: (xhr) => {
        Swal.fire('', (xhr.responseJSON && xhr.responseJSON.msg) || 'เกิดข้อผิดพลาด', 'error');
      }
    });
  });
});
</script>
@endsection