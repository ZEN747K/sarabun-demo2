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
    var id = '{{$id}}';
    $('#example').DataTable({
        processing: true,
        ajax: {
            url: "/users/listDataPermission",
            type: "GET",
            data: {
                id: id
            }
        },
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Thai.json"
        },
        columns: [{
                data: 'permission_name',
                class: 'text-center',
            },
            {
                data: 'position_name',
                class: 'text-center',
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