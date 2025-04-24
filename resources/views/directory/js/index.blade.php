@section('script')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script>
    function toggleFolder(folderId, element, id) {
        $('.folder-container').css('background-color', 'white');
        $(element).css('background-color', '#18d5ff');
        $('#id').val(id);
        // let arrow = element.querySelector(".arrow");

        // if (folder) {
        //     folder.classList.toggle("hidden");

        //     // เปลี่ยนไอคอน ▶ เป็น ▼ เมื่อเปิดโฟลเดอร์
        //     if (folder.classList.contains("hidden")) {
        //         arrow.style.transform = "rotate(0deg)";
        //     } else {
        //         arrow.style.transform = "rotate(90deg)";
        //     }
        // }
        $('#example').DataTable().ajax.reload(null, false);
        Swal.showLoading();
    }
    $('#search').click(function(e) {
        e.preventDefault();
        $('#example').DataTable().ajax.reload(null, false);
        Swal.showLoading();
    });

    $(document).ready(function() {
        $('#example').DataTable({
            processing: true,
            ajax: {
                url: "/directory/listData",
                type: "post",
                data: function(d) {
                    d.id = $('#id').val();
                    d.keyword = $('#keyword').val();
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                complete: function(data) {
                    Swal.close();
                }
            },
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Thai.json"
            },
            searching: false,
            columns: [{
                    data: 'date',
                    class: 'text-center',
                    width: '20%'
                },
                {
                    data: 'number_regis',
                    class: 'text-center',
                    width: '10%'
                },
                {
                    data: 'number_book',
                    class: 'text-center',
                    width: '20%'
                },
                {
                    data: 'title',
                    class: 'text-center',
                },
            ]
        });
    });
</script>
@endsection