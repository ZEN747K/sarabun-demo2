@section('script')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script>
    var id = '{{$id}}';
    $(document).ready(function() {
        $('#example').DataTable({
            processing: true,
            ajax: {
                url: "/tracking/dataReportDetail",
                data: {
                    id: id
                },
                type: "post",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                complete: function() {
                    $('.modalDetail').click(function(e) {
                        e.preventDefault();
                        var position_id = $(this).data('id');
                        $('#divModal').empty();
                        $.ajax({
                            type: "post",
                            url: "/tracking/getDetailAll",
                            data: {
                                id: id,
                                position_id: position_id
                            },
                            dataType: "json",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $('#divModal').html(response.data);
                                $('#modalDetail').modal('show');
                            }
                        });
                    });
                },
            },
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Thai.json"
            },
            order: [
                [0, 'desc']
            ],
            columns: [{
                    data: 'number_regis',
                    class: 'text-center',
                },
                {
                    data: 'orgPath',
                    class: 'text-center',
                },
                {
                    data: 'detail',
                    class: 'text-left',
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
</script>
<div class="modal modal-xl fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLabel">รายละเอียด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12" id="divModal">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>
@endsection