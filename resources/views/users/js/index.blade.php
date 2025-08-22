@section('script')
  {{-- DataTables + Select2 --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  @if(session('success'))
  <script>
    Swal.fire({ title: 'บันทึกข้อมูลเรียบร้อย', icon: 'success' });
  </script>
  @endif

  <script>
  (function () {
    const CSRF = '{{ csrf_token() }}';

    // ===== DataTable =====
    const table = $('#example').DataTable({
      ajax: '{{ route('users.listData') }}',
      columns: [
        { data: 'email' },
        { data: 'fullname' },
        { data: 'role_name' },          
        { data: 'department', defaultContent: '-' }, 
        {
          data: null,
          orderable: false,
          searchable: false,
          render: (row) => {
            const label = row.is_receiver ? 'ยกเลิกผู้รับ' : 'ตั้งเป็นผู้รับ';
            return `<button class="btn btn-sm btn-outline-primary btn-toggle" data-id="${row.id}">${label}</button>`;
          }
        },
        { data: 'action', orderable: false, searchable: false }
      ]
    });

    function reloadTable() {
      table.ajax.reload(null, false);
    }

    // ===== Toggle ผู้รับแทงเรื่อง =====
    $(document).on('click', '.btn-toggle', function () {
      const id = $(this).data('id');

      $.post(`{{ route('users.toggle_receiver') }}`, { id, _token: CSRF })
        .done(res => {
          Swal.fire('สำเร็จ', res.msg, 'success');
          reloadTable();
        })
        .fail(xhr => {
          const res = xhr.responseJSON || {};
          if (res.need_parent) {
            $('#modalPermissionId').val(res.permission_id);
            $('#modalPositionId').val(res.position_id);
            $('#modalUserIdForRetry').val(id);

            $.get(`{{ route('permissions.parent_options') }}`, {
              position_id: res.position_id,  
              exclude: res.permission_id     
            }).done(list => {
              // group by department
              const byDept = list.reduce((acc, it) => {
                const key = it.department || 'ไม่ระบุหน่วยงาน';
                (acc[key] ||= []).push(it);
                return acc;
              }, {});

              const $sel = $('#parentSelect').empty();
              Object.entries(byDept).forEach(([dept, items]) => {
                const $group = $(`<optgroup label="${dept}"></optgroup>`);
                items.forEach(it => {
                  $group.append(
                    `<option value="${it.id}">${it.permission_name} — [${it.id}]</option>`
                  );
                });
                $sel.append($group);
              });

              if ($sel.data('select2')) $sel.select2('destroy');
              $sel.select2({ dropdownParent: $('#parentModal') });

              new bootstrap.Modal(document.getElementById('parentModal')).show();
            });
          } else {
            Swal.fire('ผิดพลาด', res.msg || 'เกิดข้อผิดพลาด', 'error');
          }
        });
    });

    $('#btnSaveParent').on('click', function () {
      const permission_id = $('#modalPermissionId').val();
      const parent_id     = $('#parentSelect').val();
      const user_id_retry = $('#modalUserIdForRetry').val();

      if (!parent_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกผู้บังคับบัญชา', 'warning');
        return;
      }

      $.post(`{{ route('permissions.set_parent') }}`, {
        _token: CSRF,
        permission_id,
        parent_id
      })
      .done(res => {
        Swal.fire('สำเร็จ', res.msg, 'success');
        bootstrap.Modal.getInstance(document.getElementById('parentModal')).hide();

        $.post(`{{ route('users.toggle_receiver') }}`, { id: user_id_retry, _token: CSRF })
          .done(res2 => {
            Swal.fire('สำเร็จ', res2.msg, 'success');
            reloadTable();
          })
          .fail(() => Swal.fire('ผิดพลาด', 'ไม่สามารถตั้งเป็นผู้รับได้', 'error'));
      })
      .fail(xhr => {
        const msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'บันทึกไม่สำเร็จ';
        Swal.fire('ผิดพลาด', msg, 'error');
      });
    });
  })();
  </script>
@endsection
