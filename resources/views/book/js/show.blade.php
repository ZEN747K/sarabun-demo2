@section('script')
<?php $position = $item; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    @include('book.js.constants')

    var permission_id = '{{$permission_id}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;
    var fileInput = document.getElementById('file-input');
    var uploadArea = document.getElementById('upload-area');
    var pdfContainer = document.getElementById('pdf-container');
    var browseBtn = document.getElementById('browse-btn');
    var editMode = false;

    // กันโหลด PDF ซ้อนกันโดยไม่ตั้งใจ
    var pdfLoadSeq = 0;

    // ==============================
    // โหลดและแสดง PDF
    // ==============================
    function pdf(url) {
        const thisLoad = ++pdfLoadSeq;

        var pdfDoc = null,
            pageNum = 1,
            scale = 1.5,

            pdfCanvas = document.getElementById('pdf-render'),
            pdfCanvasInsert = document.getElementById('pdf-render-insert'),
            markCanvas = document.getElementById('mark-layer'),
            additionalContainer = document.getElementById('pdf-additional');

        if (!pdfCanvas || !markCanvas) {
            // ยังไม่มี canvas → ไม่ทำต่อ
            return;
        }

        var pdfCtx = pdfCanvas.getContext('2d');
        var markCtx = markCanvas.getContext('2d');
        var pdfCtxInsert = pdfCanvasInsert ? pdfCanvasInsert.getContext('2d') : null;

        additionalContainer && (additionalContainer.innerHTML = '');

        // state สำหรับกรอบตราประทับในหน้าหลัก (set เมื่อกดปุ่ม add-stamp)
        var markCoordinates = null;

        document.getElementById('add-stamp').disabled = true;

        function renderPage(num) {
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
                page.render(renderContext);
            });
        }

        pdfjsLib.getDocument({
            url: url,
            withCredentials: true,
            disableRange: true
        }).promise.then(function(pdfDoc_) {
            if (thisLoad !== pdfLoadSeq) return; // ผลลัพธ์เก่า

            pdfDoc = pdfDoc_;
            renderPage(pageNum);

            // แสดงหน้าถัด ๆ ไปเป็นภาพ
            if (additionalContainer) {
                for (let i = 2; i <= pdfDoc.numPages; i++) {
                    pdfDoc.getPage(i).then(function(page) {
                        const viewport = page.getViewport({
                            scale: scale
                        });
                        const wrapper = document.createElement('div');
                        wrapper.style.position = 'relative';
                        wrapper.style.margin = '20px auto 0';

                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        wrapper.appendChild(canvas);

                        const ctx = canvas.getContext('2d');
                        page.render({
                            canvasContext: ctx,
                            viewport: viewport
                        });

                        additionalContainer.appendChild(wrapper);
                    });
                }
            }

            document.getElementById('add-stamp').disabled = false;
        });

        // ==============================
        // ปุ่ม “ตราประทับ”
        // ==============================
        $('#add-stamp').off('click').on('click', function(e) {
            e.preventDefault();
            removeMarkListener();

            document.getElementById('add-stamp').disabled = true;
            document.getElementById('save-stamp').disabled = false;

            var markCanvas = document.getElementById('mark-layer');
            if (!markCanvas) return;
            var markCtx = markCanvas.getContext('2d');

            // จุดตั้งต้น
            var defaultWidth = 230;
            var defaultHeight = 115;
            var startX, startY;

            if (editMode && $('#oldPositionX').val()) {
                startX = parseFloat($('#oldPositionX').val());
                startY = parseFloat($('#oldPositionY').val());
                defaultWidth = parseFloat($('#oldPositionWidth').val() || defaultWidth);
                defaultHeight = parseFloat($('#oldPositionHeight').val() || defaultHeight);
                $('#positionPages').val($('#oldPositionPages').val() || 1);
            } else {
                startX = (markCanvas.width - defaultWidth) / 2;
                startY = (markCanvas.height - defaultHeight) / 2;
                $('#positionPages').val(1);
            }

            var endX = startX + defaultWidth;
            var endY = startY + defaultHeight;

            markCoordinates = {
                startX,
                startY,
                endX,
                endY
            };
            drawMark(startX, startY, endX, endY);

            $('#positionX').val(startX);
            $('#positionY').val(startY);
            $('#positionWidth').val(defaultWidth);
            $('#positionHeight').val(defaultHeight);

            drawTextHeader('15px Sarabun', startX + 3, startY + 25, 'องค์การบริหารส่วนตำบลพระอาจารย์');
            drawTextHeader('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
            drawTextHeader('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
            drawTextHeader('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');

            // ลาก/ย่อ-ขยายกรอบ
            var isDragging = false,
                isResizing = false;
            var dragOffsetX = 0,
                dragOffsetY = 0;
            var resizeHandleSize = 16;

            function redrawStampBox() {
                markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                var boxW = markCoordinates.endX - markCoordinates.startX;
                var boxH = markCoordinates.endY - markCoordinates.startY;

                $('#positionWidth').val(boxW);
                $('#positionHeight').val(boxH);

                var baseW = 230,
                    baseH = 115;
                var scale = Math.min(boxW / baseW, boxH / baseH);
                scale = Math.max(0.5, Math.min(2.5, scale));

                drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);
                drawTextHeader((15 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 3 * scale, markCoordinates.startY + 25 * scale, 'องค์การบริหารส่วนตำบลพระอาจารย์');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 55 * scale, 'รับที่..........................................................');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 80 * scale, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 100 * scale, 'เวลา......................................................น.');
            }

            function isOnResizeHandle(mouseX, mouseY) {
                return (
                    mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
                    mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
                );
            }

            markCanvas.addEventListener('mousemove', function(e) {
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;

                if (isOnResizeHandle(mouseX, mouseY)) {
                    markCanvas.style.cursor = 'se-resize';
                } else if (
                    mouseX >= markCoordinates.startX && mouseX <= markCoordinates.endX &&
                    mouseY >= markCoordinates.startY && mouseY <= markCoordinates.endY
                ) {
                    markCanvas.style.cursor = 'move';
                } else {
                    markCanvas.style.cursor = 'default';
                }
            });

            markCanvas.onmousedown = function(e) {
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;

                if (isOnResizeHandle(mouseX, mouseY)) {
                    isResizing = true;
                    e.preventDefault();
                    window.addEventListener('mousemove', onResizeMove);
                    window.addEventListener('mouseup', onResizeEnd);
                } else if (
                    mouseX >= markCoordinates.startX && mouseX <= markCoordinates.endX &&
                    mouseY >= markCoordinates.startY && mouseY <= markCoordinates.endY
                ) {
                    isDragging = true;
                    dragOffsetX = mouseX - markCoordinates.startX;
                    dragOffsetY = mouseY - markCoordinates.startY;
                    e.preventDefault();
                    window.addEventListener('mousemove', onDragMove);
                    window.addEventListener('mouseup', onDragEnd);
                }
            };

            // กันคลิกนอกกรอบแล้วล้างโดยไม่ตั้งใจ
            markCanvas.addEventListener('click', function(e) {
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;
                if (!isDragging && !isResizing &&
                    (mouseX < markCoordinates.startX || mouseX > markCoordinates.endX ||
                        mouseY < markCoordinates.startY || mouseY > markCoordinates.endY)) {
                    e.stopPropagation();
                    e.preventDefault();
                }
            }, true);

            function onDragMove(e) {
                if (!isDragging) return;
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;

                var boxW = markCoordinates.endX - markCoordinates.startX;
                var boxH = markCoordinates.endY - markCoordinates.startY;

                var newStartX = mouseX - dragOffsetX;
                var newStartY = mouseY - dragOffsetY;

                newStartX = Math.max(0, Math.min(markCanvas.width - boxW, newStartX));
                newStartY = Math.max(0, Math.min(markCanvas.height - boxH, newStartY));

                var newEndX = newStartX + boxW;
                var newEndY = newStartY + boxH;

                if (newEndX > markCanvas.width) {
                    newEndX = markCanvas.width;
                    newStartX = newEndX - boxW;
                }
                if (newEndY > markCanvas.height) {
                    newEndY = markCanvas.height;
                    newStartY = newEndY - boxH;
                }

                markCoordinates.startX = newStartX;
                markCoordinates.startY = newStartY;
                markCoordinates.endX = newEndX;
                markCoordinates.endY = newEndY;

                $('#positionX').val(newStartX);
                $('#positionY').val(newStartY);

                redrawStampBox();
                showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
            }

            function onResizeMove(e) {
                if (!isResizing) return;
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;

                var minW = 40,
                    minH = 30;
                var newEndX = Math.max(markCoordinates.startX + minW, mouseX);
                var newEndY = Math.max(markCoordinates.startY + minH, mouseY);

                newEndX = Math.min(markCanvas.width, newEndX);
                newEndY = Math.min(markCanvas.height, newEndY);

                markCoordinates.endX = newEndX;
                markCoordinates.endY = newEndY;

                redrawStampBox();
                showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
            }

            function onResizeEnd() {
                isResizing = false;
                window.removeEventListener('mousemove', onResizeMove);
                window.removeEventListener('mouseup', onResizeEnd);
            }

            function onDragEnd() {
                isDragging = false;
                window.removeEventListener('mousemove', onDragMove);
                window.removeEventListener('mouseup', onDragEnd);
            }

            // ----- ใส่บนแท็บ insert (พับครึ่ง) -----
            markEventListenerInsert = function(e) {
                var markCanvasIns = document.getElementById('mark-layer-insert');
                if (!markCanvasIns) return;
                var markCtxIns = markCanvasIns.getContext('2d');
                var rect = markCanvasIns.getBoundingClientRect();
                var startX = (e.clientX - rect.left);
                var startY = (e.clientY - rect.top);

                var endX = startX + 230;
                var endY = startY + 115;

                markCoordinates = {
                    startX,
                    startY,
                    endX,
                    endY
                };
                drawMarkInsert(startX, startY, endX, endY);

                $('#positionX').val(startX);
                $('#positionY').val(startY);
                $('#positionPages').val(2);
                $('#positionWidth').val(213);
                $('#positionHeight').val(115);

                drawTextHeaderInsert('15px Sarabun', startX + 3, startY + 25, 'องค์การบริหารส่วนตำบลพระอาจารย์');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');
            };

            var markCanvasInsert = document.getElementById('mark-layer-insert');
            if (markCanvasInsert) {
                markCanvasInsert.addEventListener('click', markEventListenerInsert);
            }
        });

        // ==============================
        // ปุ่ม “ประทับเลขที่รับ”
        // ==============================
        $('#number-stamp').off('click').on('click', function(e) {
            e.preventDefault();
            removeMarkListener();
            document.getElementById('number-save').disabled = false;

            markEventListener = function(e) {
                var markCanvas = document.getElementById('mark-layer');
                if (!markCanvas) return;

                var rect = markCanvas.getBoundingClientRect();
                var startX = (e.clientX - rect.left);
                var startY = (e.clientY - rect.top);
                var endX = startX + 30;
                var endY = startY;

                markCoordinates = {
                    startX,
                    startY,
                    endX,
                    endY
                };
                drawMarkHidden(startX, startY, endX, endY);

                $('#positionX').val(startX);
                $('#positionY').val(startY);

                drawTextHeader('20px Sarabun', startX, startY, $('#number_id').val());
            };

            var markCanvas = document.getElementById('mark-layer');
            if (markCanvas) markCanvas.addEventListener('click', markEventListener);
        });

        // ==============================
        // util ใน pdf()
        // ==============================
        function removeMarkListener() {
            var markCanvas = document.getElementById('mark-layer');
            var markCanvasInsert = document.getElementById('mark-layer-insert');

            if (markEventListener && markCanvas) {
                markCanvas.removeEventListener('click', markEventListener);
                markEventListener = null;
            }
            if (markEventListenerInsert && markCanvasInsert) {
                markCanvasInsert.removeEventListener('click', markEventListenerInsert);
                markEventListenerInsert = null;
            }
            $('#positionX').val('');
            $('#positionY').val('');
            $('#positionPages').val('');
            $('#positionWidth').val('');
            $('#positionHeight').val('');
        }

        function drawMark(startX, startY, endX, endY) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
            markCtx.beginPath();
            markCtx.rect(startX, startY, endX - startX, endY - startY);
            markCtx.lineWidth = 0.5;
            markCtx.strokeStyle = 'blue';
            markCtx.stroke();

            var resizeHandleSize = 16;
            markCtx.save();
            markCtx.beginPath();
            markCtx.rect(endX - resizeHandleSize, endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
            markCtx.fillStyle = '#fff';
            markCtx.strokeStyle = '#007bff';
            markCtx.lineWidth = 2;
            markCtx.fill();
            markCtx.stroke();
            markCtx.restore();
        }

        function drawMarkInsert(startX, startY, endX, endY) {
            var markCanvasMain = document.getElementById('mark-layer');
            var markCtxMain = markCanvasMain.getContext('2d');
            markCtxMain.clearRect(0, 0, markCanvasMain.width, markCanvasMain.height);

            var markCanvas = document.getElementById('mark-layer-insert');
            if (!markCanvas) return;

            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            markCtx.beginPath();
            markCtx.rect(startX, startY, endX - startX, endY - startY);
            markCtx.lineWidth = 0.5;
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

                if (clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize) {
                    removeMarkListener();
                    var ctx = markCanvas.getContext('2d');
                    ctx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                }
            });
        }

        function drawMarkHidden(startX, startY, endX, endY) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            var crossSize = 7;
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

                if (clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize) {
                    removeMarkListener();
                    var ctx = markCanvas.getContext('2d');
                    ctx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                }
            });
        }

        function drawTextHeader(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            markCtx.fillText(text, startX, startY);
        }

        function drawTextHeaderInsert(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer-insert');
            if (!markCanvas) return;
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            markCtx.fillText(text, startX, startY);
        }
    }

    // ไว้ให้ remove/add click listener (ใช้ใน pdf())
    let markEventListener = null;
    let markEventListenerInsert = null;

    // ==============================
    // เปิดเอกสาร
    // ==============================
    function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
        $('.btn-default').hide();
        document.getElementById('reject-book').disabled = true;
        $('#div-showPdf').show();
        $('#div-uploadPdf').hide();

        editMode = false;

        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('number-save').disabled = true;

        // สร้าง canvas ใหม่ทุกครั้ง ก่อนเรียก pdf()
        $('#div-canvas').html(
            '<div style="position: relative;">' +
            '<canvas id="pdf-render"></canvas>' +
            '<canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas>' +
            '</div>' +
            '<div id="pdf-additional"></div>'
        );

        pdf(url);

        $('#id').val(id);
        $('#position_id').val(position_id);
        $('#number_id').val(number_id);

        // reset values
        $('#positionX, #positionY, #oldPositionX, #oldPositionY, #oldPositionPages, #oldPositionWidth, #oldPositionHeight').val('');

        if (status == STATUS.STAMPED) {
            $.get('/book/stamp_position/' + id, function(res) {
                if (res.status) {
                    $('#oldPositionX').val(res.x);
                    $('#oldPositionY').val(res.y);
                    $('#oldPositionPages').val(res.pages);
                    $('#oldPositionWidth').val(res.width);
                    $('#oldPositionHeight').val(res.height);
                }
            });
        }

        if (type == 1) {
            if (status == STATUS.PENDING_STAMP) {
                $('#add-stamp, #save-stamp, #insert-pages').show();
            }
            if (status == STATUS.STAMPED) {
                $('#send-to, #edit-stamp, #save-stamp').show();
            } else {
                $('#edit-stamp').hide();
                $('#save-stamp').show();
            }
        }

        if (type == 2) {
            if (is_check == '' || is_check == 'null') {
                $('#number-stamp, #number-save').show();
                $('#add-stamp, #save-stamp').hide();
            } else {
                if (status == STATUS.STAMPED) {
                    $('#send-to').show();
                }
            }
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

    // ==============================
    // อัปโหลด PDF แทน
    // ==============================
    function uploadPdf(id) {
        uploadArea.style.opacity = '';
        uploadArea.style.position = '';
        document.getElementById('save-pdf').disabled = true;

        $('#pdf-container').html('');
        $('#div-canvas').html('');

        $('#pdf-container').hide('');
        $('.btn-default').hide();
        $('#div-showPdf').hide();

        $('#div-uploadPdf').show();
        $('#save-pdf').show();
        $('#upload-area').show();
        $('#id').val(id);
    }

    // ==============================
    // utils นอก pdf()
    // ==============================
    function removeMarkListener() {
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        if (markEventListener && markCanvas) {
            markCanvas.removeEventListener('click', markEventListener);
            markEventListener = null;
        }
        if (markEventListenerInsert && markCanvasInsert) {
            markCanvasInsert.removeEventListener('click', markEventListenerInsert);
            markEventListenerInsert = null;
        }
        $('#positionX, #positionY, #positionPages, #positionWidth, #positionHeight, #edit-date-hidden, #edit-time-hidden').val('');
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        if (markCanvas) {
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
        }
        if (markCanvasInsert) {
            var markCtxInsert = markCanvasInsert.getContext('2d');
            markCtxInsert.clearRect(0, 0, markCanvasInsert.width, markCanvasInsert.height);
        }
    }

    // ==============================
    // เปลี่ยนหน้า list เอกสาร (ซ้าย)
    // ==============================
    if (selectPageTable) {
        selectPageTable.addEventListener('change', function() {
            let selectedPage = parseInt(this.value, 10);
            ajaxTable(selectedPage);
        });
    }

    function onNextPageTable() {
        if (pageNumTalbe >= pageTotal) return;
        pageNumTalbe++;
        selectPageTable && (selectPageTable.value = pageNumTalbe);
        ajaxTable(pageNumTalbe);
    }

    function onPrevPageTable() {
        if (pageNumTalbe <= 1) return;
        pageNumTalbe--;
        selectPageTable && (selectPageTable.value = pageNumTalbe);
        ajaxTable(pageNumTalbe);
    }
    document.getElementById('nextPage')?.addEventListener('click', onNextPageTable);
    document.getElementById('prevPage')?.addEventListener('click', onPrevPageTable);

    function ajaxTable(pages) {
        $('#id, #positionX, #positionY').val('');
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;

        $.ajax({
            type: "post",
            url: "/book/dataList",
            data: {
                pages: pages
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
                        var color = (element.type != 1) ? 'warning' : 'info';
                        var html = '<a href="javascript:void(0)" onclick="openPdf(' +
                            "'" + element.url + "'" + ',' +
                            "'" + element.id + "'" + ',' +
                            "'" + element.status + "'" + ',' +
                            "'" + element.type + "'" + ',' +
                            "'" + element.is_number_stamp + "'" + ',' +
                            "'" + element.inputBookregistNumber + "'" + ',' +
                            "'" + element.position_id + "'" +
                            ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' +
                            element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' +
                            element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append(html);
                    });
                }
            }
        });
    }

    // ==============================
    // ค้นหาเอกสาร
    // ==============================
    $('#search_btn').click(function(e) {
        e.preventDefault();
        $('#id, #positionX, #positionY').val('');
        $('.btn-default').hide();
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;

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
                        var color = (element.type != 1) ? 'warning' : 'info';
                        var html = '<a href="javascript:void(0)" onclick="openPdf(' +
                            "'" + element.url + "'" + ',' +
                            "'" + element.id + "'" + ',' +
                            "'" + element.status + "'" + ',' +
                            "'" + element.type + "'" + ',' +
                            "'" + element.is_number_stamp + "'" + ',' +
                            "'" + element.inputBookregistNumber + "'" + ',' +
                            "'" + element.position_id + "'" +
                            ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' +
                            element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' +
                            element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append(html);
                    });

                    $("#page-select-card").empty();
                    for (let i = 1; i <= pageTotal; i++) {
                        $('#page-select-card').append('<option value="' + i + '">' + i + '</option>');
                    }
                }
            }
        });
    });

    // Enter = ค้นหา
    $('#inputSearch').on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            $('#search_btn').click();
        }
    });

    // ==============================
    // บันทึกตราประทับ
    // ==============================
    $('#save-stamp').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var positionPages = $('#positionPages').val();
        var positionWidth = $('#positionWidth').val();
        var positionHeight = $('#positionHeight').val();
        var pages = 1;

        if (id && positionX !== '' && positionY !== '') {
            Swal.fire({
                title: "ยืนยันการลงบันทึกเวลา",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    var urlAjax = editMode ? "/book/edit_stamp" : "/book/save_stamp";
                    var postData = {
                        id: id,
                        positionX: positionX,
                        positionY: positionY,
                        positionPages: positionPages,
                        width: positionWidth,
                        height: positionHeight,
                        pages: pages
                    };
                    if (editMode) {
                        postData.date = $('#edit-date-hidden').val();
                        postData.time = $('#edit-time-hidden').val();
                    }

                    $.ajax({
                        type: "post",
                        url: urlAjax,
                        data: postData,
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "บันทึกเรียบร้อย", "success");
                                setTimeout(() => location.reload(), 1500);
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

    // ==============================
    // แก้ไขตราประทับ (ผู้ดูแล)
    // ==============================
    $('#edit-stamp').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันตัวตนผู้ดูแล',
            html: '<input id="admin-user" class="swal2-input" placeholder="Username">' +
                '<input id="admin-pass" type="password" class="swal2-input" placeholder="Password">',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const username = $('#admin-user').val();
                const password = $('#admin-pass').val();
                return $.ajax({
                    type: 'post',
                    url: '/book/check_admin',
                    data: {
                        username: username,
                        password: password
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value.status) {
                Swal.fire({
                    title: 'แก้ไขวันและเวลา',
                    html: '<input type="date" id="edit-date" class="swal2-input">' +
                        '<input type="time" id="edit-time" class="swal2-input">',
                    showCancelButton: true,
                    confirmButtonText: 'ตกลง',
                    cancelButtonText: 'ยกเลิก'
                }).then((res) => {
                    if (res.isConfirmed) {
                        editMode = true;
                        $('#edit-date-hidden').val($('#edit-date').val());
                        $('#edit-time-hidden').val($('#edit-time').val());
                        $('#add-stamp').show();
                        document.getElementById('add-stamp').disabled = false;
                        $('#add-stamp').trigger('click');
                        if ($('#oldPositionX').val()) {
                            Swal.fire('', 'ยืนยันตำแหน่งตราประทับแล้วกดบันทึก', 'info');
                        } else {
                            Swal.fire('', 'เลือกตำแหน่งตราประทับแล้วกดบันทึก', 'info');
                        }
                    }
                });
            } else if (result.isConfirmed) {
                Swal.fire('', 'ไม่พบข้อมูลผู้ดูแล', 'error');
            }
        });
    });

    // ==============================
    // บันทึกเลขที่รับ
    // ==============================
    $('#number-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = 1;
        var number_id = $('#number_id').val();

        if (id && positionX !== '' && positionY !== '') {
            Swal.fire({
                title: "ยืนยันการลงบันทึกเวลา",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: "/book/number_save",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            pages: pages,
                            number_id: number_id
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "บันทึกเรียบร้อย", "success");
                                setTimeout(() => location.reload(), 1500);
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

    // ==============================
    // ส่งเรื่อง
    // ==============================
    $('#send-to').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'เลือกหน่วยงานที่ต้องการแทงเรื่อง',
            html: `
                <select id="select_position_id" name="states[]" multiple="multiple" class="swal2-input" style="width: 80%;">
                    @foreach($position as $key => $rec)
                        <option value="{{$key}}">{{$rec}}</option>
                    @endforeach
                </select>
            `,
            didOpen: () => {
                $('#select_position_id').select2({
                    dropdownParent: $('.swal2-container')
                });
            },
            allowOutsideClick: false,
            focusConfirm: true,
            confirmButtonText: 'ตกลง',
            showCancelButton: true,
            cancelButtonText: `ยกเลิก`,
            preConfirm: () => {
                const selectedValue = $('#select_position_id').val();
                if (!selectedValue) {
                    Swal.showValidationMessage('ท่านยังไม่ได้เลือกหน่วยงาน');
                }
                return selectedValue;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var id = $('#id').val();
                $.ajax({
                    type: "post",
                    url: "/book/send_to_admin",
                    data: {
                        id: id,
                        position_id: result.value
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error");
                        }
                    }
                });
            }
        });
    });

    // ปุ่มยกเลิกกล่องตรา
    function showCancelStampBtn(x, y) {
        let cancelBtn = document.getElementById('cancel-stamp-btn');
        var markCanvas = document.getElementById('mark-layer');
        if (!cancelBtn) {
            cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancel-stamp-btn';
            cancelBtn.className = 'btn btn-danger btn-sm';
            cancelBtn.innerText = 'x';
            cancelBtn.style.position = 'fixed';
            cancelBtn.style.zIndex = 1000;
            cancelBtn.onclick = function() {
                var markCtx = markCanvas.getContext('2d');
                markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                removeMarkListener();
                document.getElementById('add-stamp').disabled = false;
                document.getElementById('save-stamp').disabled = true;
                cancelBtn.remove();
            };
            document.body.appendChild(cancelBtn);
        }
        const rect = markCanvas.getBoundingClientRect();
        cancelBtn.style.left = (rect.left + x) + 'px';
        cancelBtn.style.top = (rect.top + y - 40) + 'px';
        cancelBtn.style.display = 'block';
    }

    function hideCancelStampBtn() {
        let cancelBtn = document.getElementById('cancel-stamp-btn');
        if (cancelBtn) cancelBtn.remove();
    }
    document.addEventListener('DOMContentLoaded', function() {
        hideCancelStampBtn();
    });
    const _oldRemoveMarkListener = removeMarkListener;
    removeMarkListener = function() {
        hideCancelStampBtn();
        _oldRemoveMarkListener.apply(this, arguments);
    };
</script>

<script>
    // ==============================
    // ฝั่งอัปโหลด PDF
    // ==============================
    var input_hiddenFiles = '';

    // ปุ่มเลือกไฟล์
    (function() {
        const browseBtn = document.getElementById('browse-btn');
        const fileInput = document.getElementById('file-input');
        if (browseBtn && fileInput) {
            browseBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function(event) {
                var file = event.target.files[0];
                if (file && file.type === 'application/pdf') {
                    handlePDF(file);
                } else {
                    Swal.fire({
                        title: "เฉพาะไฟล์นามสกุลที่เป็น .pdf",
                        icon: "info",
                        confirmButtonText: "ตกลง"
                    });
                }
            });
        }
    })();

    function handlePDF(file) {
        $('#upload-area').hide();
        $('#pdf-container').show();
        document.getElementById('save-pdf').disabled = false;

        if (uploadArea) {
            uploadArea.style.opacity = '0';
            uploadArea.style.position = 'absolute';
        }

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

                    page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    });
                    pdfContainer.appendChild(canvas);
                });
            }
            pdfContainer.classList.remove('hidden');
        });
    }

    uploadArea?.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea?.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    uploadArea?.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadArea.classList.remove('dragover');

        const file = event.dataTransfer.files[0];
        if (file && file.type === 'application/pdf') {
            handlePDF(file);
        } else {
            alert('Please upload a PDF file.');
        }
    });

    $('#save-pdf').click(function(e) {
        e.preventDefault();
        var fileInput = document.getElementById('file-input');
        var file = fileInput ? fileInput.files[0] : null;
        var id = $('#id').val();

        if (file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('id', id);

            $.ajax({
                type: "post",
                url: "/book/uploadPdf",
                data: formData,
                dataType: "json",
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {
                        Swal.fire("", "บันทึกเรียบร้อย", "success");
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        Swal.fire("", "บันทึกไม่สำเร็จ", "error");
                    }
                }
            });
        } else {
            alert('Please select a file!');
        }
    });

    $('#insert-pages').click(function(e) {
        e.preventDefault();
        $('#insert_tab').show();
    });

    $('#reject-book').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: "",
            text: "ยืนยันการปฏิเสธหนังสือหรือไม่",
            icon: "warning",
            input: 'textarea',
            inputPlaceholder: 'กรอกเหตุผลการปฏิเสธ',
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
                        note: note
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
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
                    if (!canvas) return;
                    const context = canvas.getContext("2d");
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    return page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
                }).catch(error => console.error("Error rendering PDF:", error));
        }
        createAndRenderPDF();
    });
</script>
@endsection