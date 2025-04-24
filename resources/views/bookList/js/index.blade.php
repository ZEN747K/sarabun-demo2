@section('script')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#example').DataTable({
            processing: true,
            ajax: {
                url: "/listSender/listData",
                type: "post",
                data: function(d) {
                    d.keyword = $('#keyword').val();
                    d.selectBookregist = $('#selectBookregist').val();
                    d.selectBookregist_parent = $('#selectBookregist_parent').val();
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            },
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Thai.json"
            },
            columns: [{
                    data: null,
                    render: function(data, type, row) {
                        return row.number_regis + '<br>' + row.type_regis;
                    },
                    class: 'text-center',
                },
                {
                    data: 'number_book',
                    class: 'text-center',
                },
                {
                    data: 'title',
                    class: 'text-left',
                },
                {
                    data: 'position_name',
                    class: 'text-center',
                },
                {
                    data: 'date',
                    class: 'text-center',
                },
                {
                    data: 'action',
                    class: 'text-center',
                    orderable: false,
                }
            ]
        });
    });
    $('#search').click(function(e) {
        e.preventDefault();
        $('#example').DataTable().ajax.reload(null, false);
    });
</script>
@endsection