@section('script')
<?php $position = [1 => 'สำนักงานปลัด', 2 => 'งานกิจการสภา', 3 => 'กองคลัง', 4 => 'กองช่าง', 5 => 'กองการศึกษา ศาสนาและวัฒนธรรม', 6 => 'ฝ่ายศูนย์รับเรื่องร้องเรียน-ร้องทุกข์', 7 => 'ฝ่ายเลือกตั้ง', 8 => 'ฝ่ายสปสช.', 9 => 'ศูนย์ข้อมูลข่าวสาร']; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    @include('book.js.constants')
    $('.btn-default').hide();
    var signature = '{{$signature}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;

    var imgData = null;
//
    function pdf(url) {
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCtx = pdfCanvas.getContext('2d'),
            markCanvas = document.getElementById('mark-layer'),
            markCtx = markCanvas.getContext('2d'),
            selectPage = document.getElementById('page-select');

        var markCoordinates = null;

        document.getElementById('manager-save').disabled = true;

        function renderPage(num) {
            pageRendering = true;

            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({
                    scale: scale
                });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;
                markCanvas.height = viewport.height;
                markCanvas.width = viewport.width;

                let renderContext = {
                    canvasContext: pdfCtx,
                    viewport: viewport
                };
                let renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            selectPage.value = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }

        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }

        selectPage.addEventListener('change', function() {
            let selectedPage = parseInt(this.value);
            if (selectedPage && selectedPage >= 1 && selectedPage <= pdfDoc.numPages) {
                pageNum = selectedPage;
                queueRenderPage(selectedPage);
            }
        });

        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                let option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                selectPage.appendChild(option);
            }

            renderPage(pageNum);
            document.getElementById('manager-sinature').disabled = false;
        });


        document.getElementById('next').addEventListener('click', onNextPage);
        document.getElementById('prev').addEventListener('click', onPrevPage);


        let markEventListener = null;

        function countLineBreaks(text) {
            var lines = text.split('\n');
            return lines.length - 1;
        }
        // ฟังก์ชันในการวาดกากบาทเล็กๆ ที่มุมขวาบน
        function drawMark(startX, startY, endX, endY) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            markCtx.beginPath();
            markCtx.rect(startX, startY, endX - startX, endY - startY);
            markCtx.lineWidth = 1;
            markCtx.strokeStyle = 'blue';
            markCtx.stroke();

            var crossSize = 10;
            markCtx.beginPath();
            markCtx.moveTo(endX - crossSize, startY + crossSize);
            markCtx.lineTo(endX, startY);
            markCtx.moveTo(endX, startY + crossSize);
            markCtx.lineTo(endX - crossSize, startY);
            markCtx.lineWidth = 2;
            markCtx.strokeStyle = 'red';
            markCtx.stroke();

            markCanvas.addEventListener('click', function(event) {
                var rect = markCanvas.getBoundingClientRect();
                var clickX = event.clientX - rect.left;
                var clickY = event.clientY - rect.top;

                if (
                    clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize
                ) {
                    removeMarkListener();
                    var markCtx = markCanvas.getContext('2d');
                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                }
            });
        }

        function drawMarkSignature(startX, startY, endX, endY, checkedValues) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            checkedValues.forEach(element => {
                if (element == 4) {
                    var img = new Image();
                    img.src = signature;
                    img.onload = function() {
                        var imgWidth = 240;
                        var imgHeight = 130;

                        var centeredX = (startX + 50) - (imgWidth / 2);
                        var centeredY = (startY + 60) - (imgHeight / 2);

                        markCtx.drawImage(img, centeredX, centeredY, imgWidth, imgHeight);

                        imgData = {
                            x: centeredX,
                            y: centeredY,
                            width: imgWidth,
                            height: imgHeight
                        };
                    }
                }
            });
        }

        function drawTextHeader(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');

            markCtx.font = type;
            markCtx.fillStyle = "blue";
            var textWidth = markCtx.measureText(text).width;

            var centeredX = startX - (textWidth / 2);

            markCtx.fillText(text, centeredX, startY);
        }

        function drawTextHeaderSignature(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');

            markCtx.font = type;
            markCtx.fillStyle = "blue";

            var lines = text.split('\n');
            var lineHeight = 20;

            for (var i = 0; i < lines.length; i++) {
                // 🔴 คำนวณความกว้างของแต่ละบรรทัด
                var textWidth = markCtx.measureText(lines[i]).width;
                var centeredX = startX - (textWidth / 2);

                markCtx.fillText(lines[i], centeredX, startY + (i * lineHeight)); // 🔴 เปลี่ยน startX → centeredX
            }
        }
        $('#modalForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $('#exampleModal').modal('hide');
            Swal.showLoading();
            $.ajax({
                type: "post",
                url: "/book/confirm_signature",
                data: formData,
                dataType: "json",
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {
                        $('#exampleModal').modal('hide');
                        setTimeout(() => {
                            swal.close();
                        }, 1500);
                        resetMarking();
                        removeMarkListener();
                        document.getElementById('manager-save').disabled = false;

                        
// === Drag & Resize selection (merged from admin) ===
document.getElementById('manager-sinature').disabled = true;
document.getElementById('manager-save').disabled = false;

var markCanvas = document.getElementById('mark-layer');
var markCtx = markCanvas.getContext('2d');

// Default box centered
var defaultWidth = 220;
var defaultHeight = 115;
markCoordinates = {
    startX: (markCanvas.width - defaultWidth) / 2,
    startY: (markCanvas.height - defaultHeight) / 2,
    endX: (markCanvas.width - defaultWidth) / 2 + defaultWidth,
    endY: (markCanvas.height - defaultHeight) / 2 + defaultHeight
};

// For UI feedback & interactions
var isDragging = false;
var isResizing = false;
var dragOffsetX = 0;
var dragOffsetY = 0;
var resizeHandleSize = 10;

// Helper to detect handle
function isOnResizeHandle(mouseX, mouseY) {
    return (
        mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
        mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
    );
}

// Helper to detect inside box
function isInsideBox(mouseX, mouseY) {
    return (
        mouseX >= markCoordinates.startX && mouseX <= markCoordinates.endX &&
        mouseY >= markCoordinates.startY && mouseY <= markCoordinates.endY
    );
}

// Hover cursor feedback
markCanvas.addEventListener('mousemove', function(e) {
    var rect = markCanvas.getBoundingClientRect();
    var mouseX = e.clientX - rect.left;
    var mouseY = e.clientY - rect.top;
    if (isOnResizeHandle(mouseX, mouseY)) {
        markCanvas.style.cursor = 'se-resize';
    } else if (isInsideBox(mouseX, mouseY)) {
        markCanvas.style.cursor = 'move';
    } else {
        markCanvas.style.cursor = 'default';
    }
});

// Mouse down: start drag or resize
markCanvas.onmousedown = function(e) {
    var rect = markCanvas.getBoundingClientRect();
    var mouseX = e.clientX - rect.left;
    var mouseY = e.clientY - rect.top;
    if (isOnResizeHandle(mouseX, mouseY)) {
        isResizing = true;
    } else if (isInsideBox(mouseX, mouseY)) {
        isDragging = true;
        dragOffsetX = mouseX - markCoordinates.startX;
        dragOffsetY = mouseY - markCoordinates.startY;
    }
};

// Mouse move: update box
markCanvas.onmousemove = function(e) {
    if (!isDragging && !isResizing) { return; }
    var rect = markCanvas.getBoundingClientRect();
    var mouseX = e.clientX - rect.left;
    var mouseY = e.clientY - rect.top;

    if (isDragging) {
        var newStartX = mouseX - dragOffsetX;
        var newStartY = mouseY - dragOffsetY;
        var width = markCoordinates.endX - markCoordinates.startX;
        var height = markCoordinates.endY - markCoordinates.startY;

        markCoordinates.startX = Math.max(0, Math.min(newStartX, markCanvas.width - width));
        markCoordinates.startY = Math.max(0, Math.min(newStartY, markCanvas.height - height));
        markCoordinates.endX = markCoordinates.startX + width;
        markCoordinates.endY = markCoordinates.startY + height;
    } else if (isResizing) {
        markCoordinates.endX = Math.max(markCoordinates.startX + 40, Math.min(mouseX, markCanvas.width));
        markCoordinates.endY = Math.max(markCoordinates.startY + 40, Math.min(mouseY, markCanvas.height));
    }

    // Preview box and content
    drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);

    // Pull current values from modal form
    var text = $('#modal-text').val();
    var checkedValues = $('input[type="checkbox"]:checked').map(function() {
        return $(this).val();
    }).get();

    // Draw preview content scaled to current box
    var boxW = markCoordinates.endX - markCoordinates.startX;
    var boxH = markCoordinates.endY - markCoordinates.startY;
    var scaleW = boxW / defaultWidth;
    var scaleH = boxH / defaultHeight;
    var scale = Math.max(0.5, Math.min(2.5, Math.min(scaleW, scaleH)));

    // Center text baseline similar to old logic
    var lineBreakCount = (text.match(/\n/g) || []).length;
    drawMarkSignature(markCoordinates.startX - 40, markCoordinates.startY + (20 * lineBreakCount), markCoordinates.endX, markCoordinates.endY, checkedValues);

    // Header text preview with scaled font
    drawTextHeaderSignature((15 * scale) + 'px Sarabun', markCoordinates.startX, markCoordinates.startY, text);

    // Keep hidden inputs updated
    $('#positionX').val(Math.round(markCoordinates.startX));
    $('#positionY').val(Math.round(markCoordinates.startY));
};

// Mouse up: stop drag/resize
markCanvas.onmouseup = function() {
    isDragging = false;
    isResizing = false;
};

markCanvas.onmouseleave = function() {
    isDragging = false;
    isResizing = false;
};

// Initial draw
drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);
// Prime hidden inputs
$('#positionX').val(Math.round(markCoordinates.startX));
$('#positionY').val(Math.round(markCoordinates.startY));

                    } else {
                        $('#exampleModal').modal('hide');
                        Swal.fire("", response.message, "error");
                    }
                }
            });
        });
    }

    let markEventListener = null;

    function openPdf(url, id, status, type, is_number, number, position_id) {
        $('.btn-default').hide();
        document.getElementById('reject-book').disabled = true;
        document.getElementById('manager-sinature').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('send-save').disabled = true;
        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
        pdf(url);
        $('#id').val(id);
        $('#position_id').val(position_id);
        $('#positionX').val('');
        $('#positionY').val('');
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('manager-save').disabled = true;
        if (status == STATUS.MAYOR1_SIGNATURE) {
            $('#manager-sinature').show();
            $('#manager-save').show();
        }
        if (status == STATUS.MAYOR1_SENT) {
            $('#manager-send').show();
            $('#send-save').show();
        }
        $.get('/book/created_position/' + id, function(res) {
            if (status >= STATUS.ADMIN_PROCESS && status < STATUS.ARCHIVED && position_id != res.position_id) {
                document.getElementById('reject-book').disabled = false;
                $('#reject-book').show();
            }
        });
        resetMarking();
        removeMarkListener();
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
    }

    function removeMarkListener() {
        var markCanvas = document.getElementById('mark-layer');
        if (markEventListener) {
            markCanvas.removeEventListener('click', markEventListener);
            markEventListener = null;
        }
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
    }

    selectPageTable.addEventListener('change', function() {
        let selectedPage = parseInt(this.value);
        ajaxTable(selectedPage);
    });

    function onNextPageTable() {
        if (pageNumTalbe >= pageTotal) {
            return;
        }
        pageNumTalbe++;
        selectPageTable.value = pageNumTalbe;
        ajaxTable(pageNumTalbe);
    }

    function onPrevPageTable() {
        if (pageNumTalbe <= 1) {
            return;
        }
        pageNumTalbe--;
        selectPageTable.value = pageNumTalbe;
        ajaxTable(pageNumTalbe);
    }
    document.getElementById('nextPage').addEventListener('click', onNextPageTable);
    document.getElementById('prevPage').addEventListener('click', onPrevPageTable);

    function ajaxTable(pages) {
        $('#id').val('');
        $('#positionX').val('');
        $('#positionY').val('');
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('manager-sinature').disabled = false;
        document.getElementById('manager-save').disabled = true;
        $.ajax({
            type: "post",
            url: "/book/dataList",
            data: {
                pages: pages,
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').empty();
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    response.book.forEach(element => {
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append($html);
                    });
                }
            }
        });
    }

    $('#search_btn').click(function(e) {
        e.preventDefault();
        $('#id').val('');
        $('#positionX').val('');
        $('#positionY').val('');
        $('.btn-default').hide();
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('manager-sinature').disabled = false;
        document.getElementById('manager-save').disabled = true;
        $.ajax({
            type: "post",
            url: "/book/dataListSearch",
            data: {
                pages: 1,
                search: $('#inputSearch').val()
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').html('');
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    pageNumTalbe = 1;
                    pageTotal = response.totalPages;
                    response.book.forEach(element => {
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append($html);
                    });
                    $("#page-select-card").empty();
                    for (let index = 1; index <= pageTotal; index++) {
                        $('#page-select-card').append('<option value="' + index + '">' + index + '</option>');
                    }
                }
            }
        });
    });

    $('#manager-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var position_id = $('#position_id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = $('#page-select').find(":selected").val();
        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function() {
            return $(this).val();
        }).get();
        if (id != '' && positionX != '' && positionY != '') {
            Swal.fire({
                title: "ยืนยันการลงลายเซ็น",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: "/book/manager_stamp",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            pages: pages,
                            status: 11,
                            text: text,
                            checkedValues: checkedValues,
                            position_id: position_id
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "บันทึกลายเซ็นเรียบร้อยแล้ว", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                Swal.fire("", "บันทึกไม่สำเร็จ", "error");
                            }
                        }
                    });
                }
            });
        } else {
            Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info");
        }
    });

    $('#manager-send').click(function(e) {
        e.preventDefault();
       $.ajax({
  type: "post",
  url: "{{ route('book.checkbox_send') }}",
  headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
  success: function(response) {
    Swal.fire({ title: 'แทงเรื่อง', html: response, allowOutsideClick: false, focusConfirm: true,
      confirmButtonText: 'ตกลง', showCancelButton: true, cancelButtonText: `ยกเลิก`,
      preConfirm: () => {
                        var selectedCheckboxes = [];
                        var textCheckboxes = [];
                        $('input[name="flexCheckChecked[]"]:checked').each(function() {
                            selectedCheckboxes.push($(this).val());
                            textCheckboxes.push($(this).next('label').text().trim());
                        });

                        console.log(selectedCheckboxes);
                        if (selectedCheckboxes.length === 0) {
                            Swal.showValidationMessage('กรุณาเลือกตัวเลือก');
                        }

                        return {
                            id: selectedCheckboxes,
                            text: textCheckboxes
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var id = '';
                        var txt = '- แทงเรื่อง ('
                        for (let index = 0; index < result.value.text.length; index++) {
                            if (index > 0 && index < result.value.text.length) {
                                txt += ',';
                            }
                            txt += result.value.text[index];
                        }
                        for (let index = 0; index < result.value.id.length; index++) {
                            if (index > 0 && index < result.value.id.length) {
                                id += ',';
                            }
                            id += result.value.id[index];
                        }
                        txt += ') -';
                        $('#txt_label').text(txt);
                        $('#users_id').val(id);
                        document.getElementById('send-save').disabled = false;
                    }
                });
            }
        });
    });

    $('#send-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var users_id = $('#users_id').val();
        var position_id = $('#position_id').val();
        Swal.fire({
            title: "ยืนยันการแทงเรื่อง",
            showCancelButton: true,
            confirmButtonText: "ตกลง",
            cancelButtonText: `ยกเลิก`,
            icon: 'question'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "post",
                    url: "/book/send_to_save",
                    data: {
                        id: id,
                        users_id: users_id,
                        status: 12,
                        position_id: position_id
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status) {
                            if (response.status) {
                                Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error");
                            }
                        }
                    }
                });
            }
        });
    });
    $('#signature-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = $('#page-select').find(":selected").val();
        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function() {
            return $(this).val();
        }).get();
        if (id != '' && positionX != '' && positionY != '') {
            Swal.fire({
                title: "ยืนยันการลงเกษียณหนังสือ",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: "/book/signature_stamp",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            pages: pages,
                            text: text,
                            checkedValues: checkedValues
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "ลงบันทึกเกษียณหนังสือเรียบร้อย", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                Swal.fire("", "บันทึกไม่สำเร็จ", "error");
                            }
                        }
                    });
                }
            });
        } else {
            Swal.fire("", "กรุณาเลือกตำแหน่งเกษียณหนังสือ", "info");
        }
    });
    $('#reject-book').click(function (e) {
        e.preventDefault();
        Swal.fire({
            title: "",
            text: "ยืนยันการปฏิเสธหนังสือหรือไม่",
            icon: "warning",
            input: 'textarea',
            inputPlaceholder: 'กรอกเหตุผลการปฏิเสธ44',
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "ยกเลิก",
            confirmButtonText: "ตกลง",
            preConfirm: (note) => {
                if (!note) {
                    Swal.showValidationMessage('กรุณากรอกเหตุผล');
                }
                return note;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var id = $('#id').val();
                var note = result.value;
                $.ajax({
                    type: "post",
                    url: "/book/reject",
                    data: {
                        id: id,
                        note: note,
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.status) {
                            Swal.fire("", "ปฏิเสธเรียบร้อย", "success");
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            Swal.fire("", "ปฏิเสธไม่สำเร็จ", "error");
                        }
                    }
                });
            }
        });
    });
    $(document).ready(function() {
        $('#manager-sinature').click(function(e) {
            e.preventDefault();
            $('#exampleModal').modal('show');
        });
        $('#exampleModal').on('show.bs.modal', function(event) {
            $('input[type="password"]').val('');
            $('textarea').val('');
        });
    });
</script>
<div class="modal modal-lg fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="modalForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">เซ็นหนังสือ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>ข้อความเซ็นหนังสือ :</label>
                        <div class="col-sm-10">
                            <textarea rows="4" class="form-control" name="modal-text" id="modal-text"></textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-2">
                        </div>
                        <div class="col-sm-10 d-flex justify-content-center text-center">
                            ({{$users->fullname}})<br>
                            {{$permission_data->permission_name}}<br>
                            {{convertDateToThai(date("Y-m-d"))}}
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>รหัสผ่านเกษียน :</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="modal-Password" name="modal-Password">
                        </div>
                    </div>
                    <div class="row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>แสดงผล :</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <ul class="list-group list-group-horizontal">
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="1" checked>ชื่อ-นามสกุล</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="2" checked>ตำแหน่ง</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="3" checked>วันที่</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="4" checked>ลายเซ็น</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit-modal" class="btn btn-primary">ตกลง</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection