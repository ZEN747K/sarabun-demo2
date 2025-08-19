@section('script')
<script>
    $(function() {
        $('#select_position').on('change', function(e) {
            e.preventDefault();
            $.ajax({
                type: "post",
                url: "/users/getPermission",
                data: {
                    id: $(this).val()
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                dataType: "json",
                success: function(response) {
                    $('#select_permission').html(response).trigger('change');
                }
            });
        });
    });
</script>
@endsection