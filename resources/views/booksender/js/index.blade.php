@section('script')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script>
    $('.selct2').select2();
    const fileInput = document.getElementById('file-input');
    const uploadArea = document.getElementById('upload-area');
    const pdfContainer = document.getElementById('pdf-container');
    const browseBtn = document.getElementById('browse-btn');

    browseBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        console.log(file);
        if (file && file.type === 'application/pdf') {
            handlePDF(file);
        } else {
            Swal.fire({
                title: "เฉพาะไฟล์นามสกุลที่เป็น .pdf",
                icon: "info",
                confirmButtonText: "ตกลง",
            });
        }
    });

    function handlePDF(file) {
        uploadArea.style.opacity = '0';
        uploadArea.style.position = 'absolute';
        const fileURL = URL.createObjectURL(file);
        const loadingTask = pdfjsLib.getDocument(fileURL);
        loadingTask.promise.then(function(pdf) {
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                pdf.getPage(pageNumber).then(function(page) {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const viewport = page.getViewport({
                        scale: 1.5
                    });

                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    page.render(renderContext);
                    pdfContainer.appendChild(canvas);
                });
            }
            pdfContainer.classList.remove('hidden');
        });
    }
    uploadArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadArea.classList.remove('dragover');

        const file = event.dataTransfer.files[0];
        if (file && file.type === 'application/pdf') {
            handlePDF(file);
        } else {
            alert('Please upload a PDF file.');
        }
    });
</script>
<script>
    $('#selectBookregist').change(function(e) {
        e.preventDefault();
        var id = $('#selectBookregist').val();
        var parent = $('#selectBookregist_parent').val();
        $.ajax({
            type: "post",
            url: "/bookSender/bookType",
            data: {
                id: id,
                parent: parent
            },
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#inputBookregistLabel').text(response);
                $('#inputBookregistNumber').val(response);
            }
        });
    });
    $('#selectBookregist_parent').change(function(e) {
        e.preventDefault();
        var id = $('#selectBookregist').val();
        var parent = $('#selectBookregist_parent').val();
        $.ajax({
            type: "post",
            url: "/bookSender/bookType",
            data: {
                id: id,
                parent: parent
            },
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#inputBookregistLabel').text(response);
                $('#inputBookregistNumber').val(response);
            }
        });
    });
    $('#selectPositionParent').change(function(e) {
        e.preventDefault();
        var id = $(this).val();
        $.ajax({
            type: "post",
            url: "/bookSender/getPosition",
            data: {
                id: id
            },
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#emailText').text(response.email);
                $('#faxText').text(response.fax);
                $('#telText').text(response.tel);
            }
        });
    });
</script>
@if(session('success'))
<script>
    Swal.fire({
        title: "บันทึกข้อมูลเรียบร้อย",
        icon: "success",
    });
</script>
@endif
@if(session('error'))
<script>
    Swal.fire({
        title: "ท่านไม่ได้เลือกไฟล์ที่ต้องการนำเข้าระบบ",
        icon: "error",
    });
</script>
@endif
@endsection