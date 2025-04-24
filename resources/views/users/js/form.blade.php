@section('script')
<script>
    var select_position = $('#select_position').val();
    var position_id = '<?= isset($info) ? $info->position_id : '' ?>';

    $('#select_position').change(function(e) {
        e.preventDefault();
        $.ajax({
            type: "post",
            url: "/users/getPermission",
            data: {
                id: $('#select_position').val()
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            success: function(response) {
                $('#select_permission').html(response);
            }
        });
    });
    $(document).ready(function() {
        if (select_position) {
            $.ajax({
                type: "post",
                url: "/users/getPermission",
                data: {
                    id: select_position,
                    position_id: position_id
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                dataType: "json",
                success: function(response) {
                    $('#select_permission').html(response);
                }
            });
        }
    });
</script>
@endsection