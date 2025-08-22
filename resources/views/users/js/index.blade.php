@section('script')
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
const table = $('#example').DataTable({
  ajax: '{{ route('users.listData') }}',
  columns: [
    {data:'email'},
    {data:'fullname'},
    {data:'position_name'},
    {data:'department', defaultContent:'-'},
    {
      data:null,
      render: (row)=>{
        const label = row.is_receiver ? 'ยกเลิกผู้รับ' : 'ตั้งเป็นผู้รับ';
        return `<button class="btn btn-sm btn-outline-primary btn-toggle" data-id="${row.id}">${label}</button>`;
      }
    },
    {data:'action', orderable:false, searchable:false}
  ]
});

// toggle ผู้รับ
$(document).on('click','.btn-toggle',function(){
  const id = $(this).data('id');
  $.post(`{{ route('users.toggle_receiver') }}`, {id, _token:'{{ csrf_token() }}'})
    .done(res=>{
      Swal.fire('สำเร็จ', res.msg, 'success');
      table.ajax.reload(null,false);
    })
    .fail(xhr=>{
      const res = xhr.responseJSON || {};
      if (res.need_parent) {
        $('#modalPermissionId').val(res.permission_id);
        $('#modalPositionId').val(res.position_id);
        $('#modalUserIdForRetry').val(id);
        $('#parentSelect').empty();

        $.get(`{{ route('permissions.parent_options') }}`, {
          position_id: res.position_id,
          exclude: res.permission_id
        }).done(list=>{
          $('#parentSelect').append('<option value="">-- เลือก --</option>');
          list.forEach(it=>{
            $('#parentSelect').append(`<option value="${it.id}">${it.permission_name}</option>`);
          });
          new bootstrap.Modal(document.getElementById('parentModal')).show();
        });
      } else {
        Swal.fire('ผิดพลาด', res.msg || 'เกิดข้อผิดพลาด', 'error');
      }
    });
});

$('#btnSaveParent').on('click', function(){
  const permission_id = $('#modalPermissionId').val();
  const parent_id = $('#parentSelect').val();
  const user_id_retry = $('#modalUserIdForRetry').val();

  if (!parent_id) { Swal.fire('แจ้งเตือน','กรุณาเลือกผู้บังคับบัญชา','warning'); return; }

  $.post(`{{ route('permissions.set_parent') }}`, {
    _token: '{{ csrf_token() }}',
    permission_id,
    parent_id
  }).done(res=>{
    Swal.fire('สำเร็จ', res.msg, 'success');
    bootstrap.Modal.getInstance(document.getElementById('parentModal')).hide();

    $.post(`{{ route('users.toggle_receiver') }}`, {id: user_id_retry, _token:'{{ csrf_token() }}'})
      .done(res2=>{
        Swal.fire('สำเร็จ', res2.msg, 'success');
        table.ajax.reload(null,false);
      })
      .fail(()=> Swal.fire('ผิดพลาด','ไม่สามารถตั้งเป็นผู้รับได้','error'));
  }).fail(xhr=>{
    const msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'บันทึกไม่สำเร็จ';
    Swal.fire('ผิดพลาด', msg, 'error');
  });
});
</script>
@endsection
