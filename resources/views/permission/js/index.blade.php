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
    $('#example').DataTable({
        processing: true,
        ajax: {
            url: "/permission/listData",
            type: "GET",
        },
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Thai.json"
        },
        columns: [{
                data: 'position_name',
                class: 'text-left',
            },
            {
                data: 'action',
                class: 'text-center',
                orderable: false,
            }
        ]
    });
</script>
@endsection