@section('script')
<script>
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
</script>
@endsection