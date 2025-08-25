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

    function pdf(url) {
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCanvasInsert = document.getElementById('pdf-render-insert'),
            pdfCtx = pdfCanvas.getContext('2d'),
            pdfCtxInsert = pdfCanvasInsert.getContext('2d'),
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


        // let markEventListener = null;
        function countLineBreaks(text) {
            var lines = text.split('\n');
            return lines.length - 1;
        }

        function drawMarkSignature(startX, startY, endX, endY, checkedValues) {
            var markCanvas = document.getElementById('mark-layer-insert');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

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

        function drawMarkSignatureInsert(startX, startY, endX, endY, checkedValues) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            var markCanvas = document.getElementById('mark-layer-insert');
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

        function drawTextHeaderSignature(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            var lines = text.split('\n');
            var lineHeight = 20;
            for (var i = 0; i < lines.length; i++) {
                var textWidth = markCtx.measureText(lines[i]).width;
                var centeredX = startX - (textWidth / 2);
                markCtx.fillText(lines[i], centeredX, startY + (i * lineHeight));
            }
        }

        function drawTextHeaderSignatureInsert(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer-insert');
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            var lines = text.split('\n');
            var lineHeight = 20;
            for (var i = 0; i < lines.length; i++) {
                var textWidth = markCtx.measureText(lines[i]).width;
                var centeredX = startX - (textWidth / 2);
                markCtx.fillText(lines[i], centeredX, startY + (i * lineHeight));
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

                        
// === Drag & Resize selection (main canvas) ===
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

var isDragging = false;
var isResizing = false;
var dragOffsetX = 0;
var dragOffsetY = 0;
var resizeHandleSize = 10;

function isOnResizeHandle(mouseX, mouseY) {
    return (
        mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
        mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
    );
}

function isInsideBox(mouseX, mouseY) {
    return (
        mouseX >= markCoordinates.startX && mouseX <= markCoordinates.endX &&
        mouseY >= markCoordinates.startY && mouseY <= markCoordinates.endY
    );
}

// draw rectangle with small cross like old drawMark
function drawMark(sx, sy, ex, ey) {
    var ctx = markCtx;
    ctx.clearRect(0, 0, markCanvas.width, markCanvas.height);
    ctx.beginPath();
    ctx.rect(sx, sy, ex - sx, ey - sy);
    ctx.lineWidth = 1;
    ctx.strokeStyle = 'blue';
    ctx.stroke();
    var crossSize = 10;
    ctx.beginPath();
    ctx.moveTo(ex - crossSize, sy + crossSize);
    ctx.lineTo(ex, sy);
    ctx.moveTo(ex, sy + crossSize);
    ctx.lineTo(ex - crossSize, sy);
    ctx.lineWidth = 2;
    ctx.strokeStyle = 'red';
    ctx.stroke();
}

markCanvas.addEventListener('mousemove', function (e) {
    var rect = markCanvas.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var y = e.clientY - rect.top;
    if (isOnResizeHandle(x, y)) {
        markCanvas.style.cursor = 'se-resize';
    } else if (isInsideBox(x, y)) {
        markCanvas.style.cursor = 'move';
    } else {
        markCanvas.style.cursor = 'default';
    }
});

markCanvas.onmousedown = function (e) {
    var rect = markCanvas.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var y = e.clientY - rect.top;
    if (isOnResizeHandle(x, y)) {
        isResizing = true;
    } else if (isInsideBox(x, y)) {
        isDragging = true;
        dragOffsetX = x - markCoordinates.startX;
        dragOffsetY = y - markCoordinates.startY;
    }
};

markCanvas.onmousemove = function (e) {
    if (!isDragging && !isResizing) return;
    var rect = markCanvas.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var y = e.clientY - rect.top;
    if (isDragging) {
        var w = markCoordinates.endX - markCoordinates.startX;
        var h = markCoordinates.endY - markCoordinates.startY;
        var nsx = x - dragOffsetX;
        var nsy = y - dragOffsetY;
        markCoordinates.startX = Math.max(0, Math.min(nsx, markCanvas.width - w));
        markCoordinates.startY = Math.max(0, Math.min(nsy, markCanvas.height - h));
        markCoordinates.endX = markCoordinates.startX + w;
        markCoordinates.endY = markCoordinates.startY + h;
    } else if (isResizing) {
        markCoordinates.endX = Math.max(markCoordinates.startX + 40, Math.min(x, markCanvas.width));
        markCoordinates.endY = Math.max(markCoordinates.startY + 40, Math.min(y, markCanvas.height));
    }

    drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);

    var text = $('#modal-text').val();
    var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();
    var lineBreakCount = (text.match(/\n/g) || []).length;
    drawMarkSignature(markCoordinates.startX - 40, markCoordinates.startY + (20 * lineBreakCount), markCoordinates.endX, markCoordinates.endY, checkedValues);
    drawTextHeaderSignature('15px Sarabun', markCoordinates.startX, markCoordinates.startY, text);

    $('#positionX').val(Math.round(markCoordinates.startX));
    $('#positionY').val(Math.round(markCoordinates.startY));
    $('#positionPages').val(1);
};

markCanvas.onmouseup = function () { isDragging = false; isResizing = false; };
markCanvas.onmouseleave = function () { isDragging = false; isResizing = false; };

drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);
$('#positionX').val(Math.round(markCoordinates.startX));
$('#positionY').val(Math.round(markCoordinates.startY));
$('#positionPages').val(1);


                        
// === Drag & Resize selection (insert canvas) ===
document.getElementById('manager-sinature').disabled = true;
document.getElementById('manager-save').disabled = false;

var insertCanvas = document.getElementById('mark-layer-insert');
if (insertCanvas) {
    var insertCtx = insertCanvas.getContext('2d');
    var defaultWidthI = 220;
    var defaultHeightI = 115;
    var insertCoordinates = {
        startX: (insertCanvas.width - defaultWidthI) / 2,
        startY: (insertCanvas.height - defaultHeightI) / 2,
        endX: (insertCanvas.width - defaultWidthI) / 2 + defaultWidthI,
        endY: (insertCanvas.height - defaultHeightI) / 2 + defaultHeightI
    };
    var isDraggingI = false;
    var isResizingI = false;
    var dragOffsetXI = 0;
    var dragOffsetYI = 0;
    var handleI = 10;

    function isOnResizeHandleI(x, y) {
        return x >= insertCoordinates.endX - handleI && x <= insertCoordinates.endX &&
               y >= insertCoordinates.endY - handleI && y <= insertCoordinates.endY;
    }
    function isInsideBoxI(x, y) {
        return x >= insertCoordinates.startX && x <= insertCoordinates.endX &&
               y >= insertCoordinates.startY && y <= insertCoordinates.endY;
    }
    function drawMarkI(sx, sy, ex, ey) {
        insertCtx.clearRect(0, 0, insertCanvas.width, insertCanvas.height);
        insertCtx.beginPath(); insertCtx.rect(sx, sy, ex - sx, ey - sy);
        insertCtx.lineWidth = 1; insertCtx.strokeStyle = 'blue'; insertCtx.stroke();
        var cross = 10; insertCtx.beginPath();
        insertCtx.moveTo(ex - cross, sy + cross); insertCtx.lineTo(ex, sy);
        insertCtx.moveTo(ex, sy + cross); insertCtx.lineTo(ex - cross, sy);
        insertCtx.lineWidth = 2; insertCtx.strokeStyle = 'red'; insertCtx.stroke();
    }

    insertCanvas.addEventListener('mousemove', function (e) {
        var r = insertCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
        if (isOnResizeHandleI(x, y)) insertCanvas.style.cursor = 'se-resize';
        else if (isInsideBoxI(x, y)) insertCanvas.style.cursor = 'move';
        else insertCanvas.style.cursor = 'default';
    });
    insertCanvas.onmousedown = function (e) {
        var r = insertCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
        if (isOnResizeHandleI(x, y)) isResizingI = true;
        else if (isInsideBoxI(x, y)) { isDraggingI = true; dragOffsetXI = x - insertCoordinates.startX; dragOffsetYI = y - insertCoordinates.startY; }
    };
    insertCanvas.onmousemove = function (e) {
        if (!isDraggingI && !isResizingI) return;
        var r = insertCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
        if (isDraggingI) {
            var w = insertCoordinates.endX - insertCoordinates.startX;
            var h = insertCoordinates.endY - insertCoordinates.startY;
            var nsx = x - dragOffsetXI, nsy = y - dragOffsetYI;
            insertCoordinates.startX = Math.max(0, Math.min(nsx, insertCanvas.width - w));
            insertCoordinates.startY = Math.max(0, Math.min(nsy, insertCanvas.height - h));
            insertCoordinates.endX = insertCoordinates.startX + w;
            insertCoordinates.endY = insertCoordinates.startY + h;
        } else if (isResizingI) {
            insertCoordinates.endX = Math.max(insertCoordinates.startX + 40, Math.min(x, insertCanvas.width));
            insertCoordinates.endY = Math.max(insertCoordinates.startY + 40, Math.min(y, insertCanvas.height));
        }

        drawMarkI(insertCoordinates.startX, insertCoordinates.startY, insertCoordinates.endX, insertCoordinates.endY);

        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();
        var lineBreakCount = (text.match(/\n/g) || []).length;
        drawMarkSignatureInsert(insertCoordinates.startX - 40, insertCoordinates.startY + (20 * lineBreakCount), insertCoordinates.endX, insertCoordinates.endY, checkedValues);
        drawTextHeaderSignatureInsert('15px Sarabun', insertCoordinates.startX, insertCoordinates.startY, text);

        $('#positionX').val(Math.round(insertCoordinates.startX));
        $('#positionY').val(Math.round(insertCoordinates.startY));
        $('#positionPages').val(2);
    };
    insertCanvas.onmouseup = function () { isDraggingI = false; isResizingI = false; };
    insertCanvas.onmouseleave = function () { isDraggingI = false; isResizingI = false; };

    drawMarkI(insertCoordinates.startX, insertCoordinates.startY, insertCoordinates.endX, insertCoordinates.endY);
}

                    } else {
                        $('#exampleModal').modal('hide');
                        Swal.fire("", response.message, "error");
                    }
                }
            });
        });
    }

    let markEventListener = null;
    let markEventListenerInsert = null;

    function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
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
        if (status == STATUS.MANAGER_SIGNATURE) {
            $('#manager-sinature').show();
            $('#manager-save').show();
            $('#insert-pages').show();
        }
        if (status == STATUS.MANAGER_SENT) {
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

    function removeMarkListener() {
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        if (markEventListener) {
            markCanvas.removeEventListener('click', markEventListener);
            markEventListener = null;
        }
        if (markEventListenerInsert) {
            markCanvasInsert.removeEventListener('click', markEventListenerInsert);
            markEventListenerInsert = null;
        }
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        var markCtx = markCanvas.getContext('2d');
        var markCtxInsert = markCanvasInsert.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
        markCtxInsert.clearRect(0, 0, markCanvasInsert.width, markCanvasInsert.height);
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
                        var color = 'info';
                        if (element.type != 1) {
                            var color = 'warning';
                        }
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ',' + "'" + element.type + "'" + ',' + "'" + element.is_number_stamp + "'" + ',' + "'" + element.inputBookregistNumber + "'" + ',' + "'" + element.position_id + "'" + ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
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
                        var color = 'info';
                        if (element.type != 1) {
                            var color = 'warning';
                        }
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ',' + "'" + element.type + "'" + ',' + "'" + element.is_number_stamp + "'" + ',' + "'" + element.inputBookregistNumber + "'" + ',' + "'" + element.position_id + "'" + ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
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
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var positionPages = $('#positionPages').val();
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
                            positionPages: positionPages,
                            status: 7,
                            text: text,
                            checkedValues: checkedValues
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
                        status: 8
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
    $(document).ready(function() {
        $('#manager-sinature').click(function(e) {
            e.preventDefault();
        });
        $('#insert-pages').click(function(e) {
            e.preventDefault();
            $('#insert_tab').show();
        });
        $('#reject-book').click(function (e) {
            e.preventDefault();
            Swal.fire({
                title: "",
                text: "ยืนยันการปฏิเสธหนังสือหรือไม่",
                icon: "warning",
                input: 'textarea',
                inputPlaceholder: 'กรอกเหตุผลการปฏิเสธ33',
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

        async function createAndRenderPDF() {
            const pdfDoc = await PDFLib.PDFDocument.create();
            pdfDoc.addPage([600, 800]);
            const pdfBytes = await pdfDoc.save();

            const loadingTask = pdfjsLib.getDocument({
                data: pdfBytes
            });
            loadingTask.promise.then(pdf => pdf.getPage(1))
                .then(page => {
                    const scale = 1.5;
                    const viewport = page.getViewport({
                        scale
                    });

                    const canvas = document.getElementById("pdf-render-insert");
                    const context = canvas.getContext("2d");
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    return page.render(renderContext).promise;
                }).catch(error => console.error("Error rendering PDF:", error));
        }

        createAndRenderPDF();
    });
</script>
@endsection