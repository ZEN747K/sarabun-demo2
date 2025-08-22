@extends('include.main')
@section('style')
<style>
    body { font-family: 'Noto Sans Thai'; }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
    <div class="card w-75 mt-5">
        <div class="card-body">
            <table id="example" class="display w-100">
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
@endsection

@section('script')
<script>
    // CSRF
    const CSRF_TOKEN = '{{ csrf_token() }}';

    
    $(function () {
        const table = $('#example').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("users.listData") }}',
                type: 'GET'
            },
            columns: [
                { data: 'email', name: 'email' },
                { data: 'fullname', name: 'fullname' },
                { data: 'permission_name', name: 'permission_name' },
                { data: 'position_name', name: 'position_name' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (row) {
                        const isReceiver = row.is_receiver ? true : false;
                        const cls = isReceiver ? 'btn-success' : 'btn-outline-secondary';
                        const text = isReceiver ? 'เป็นผู้รับอยู่' : 'ตั้งเป็นผู้รับ';
                        return `<button class="btn btn-sm ${cls} btn-toggle-receiver" data-id="${row.id}" data-state="${isReceiver ? 1:0}">${text}</button>`;
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });

        // คลิก toggle
        $('#example').on('click', '.btn-toggle-receiver', function () {
            const $btn = $(this);
            const id = $btn.data('id');
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("users.toggleReceiver") }}',
                method: 'POST',
                data: { _token: CSRF_TOKEN, id },
            })
            .done(function (res) {
                if (res.ok) {
                    // อัปเดตปุ่มตามสถานะใหม่
                    if (res.is_receiver) {
                        $btn.removeClass('btn-outline-secondary').addClass('btn-success').text('เป็นผู้รับอยู่').data('state', 1);
                    } else {
                        $btn.removeClass('btn-success').addClass('btn-outline-secondary').text('ตั้งเป็นผู้รับ').data('state', 0);
                    }
                } else {
                    alert(res.msg || 'เกิดข้อผิดพลาด');
                }
            })
            .fail(function (xhr) {
                alert('สลับสถานะไม่สำเร็จ ('+xhr.status+')');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
        });
    });
</script>
@endsection

{{-- ถ้ายังใช้ include เดิมอยู่ให้คงไว้ด้านล่าง --}}
@extends('users.js.index')
