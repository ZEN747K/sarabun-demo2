@section('script')
<?php $position = $item; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var permission_id = '{{$permission_id}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;
    var fileInput = document.getElementById('file-input');
    var uploadArea = document.getElementById('upload-area');
    var pdfContainer = document.getElementById('pdf-container');
    var browseBtn = document.getElementById('browse-btn');

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

        document.getElementById('add-stamp').disabled = true;

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
            document.getElementById('add-stamp').disabled = false;
        });


        document.getElementById('next').addEventListener('click', onNextPage);
        document.getElementById('prev').addEventListener('click', onPrevPage);


        // let markEventListener = null;
        // let markEventListenerInsert = null;
        $('#add-stamp').click(function(e) {
            e.preventDefault();
            removeMarkListener();
            document.getElementById('add-stamp').disabled = true;
            document.getElementById('save-stamp').disabled = false;

            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            var rect = markCanvas.getBoundingClientRect();
            // Default position: center of canvas
            var defaultWidth = 213;
            var defaultHeight = 115;
            var startX = (markCanvas.width - defaultWidth) / 2;
            var startY = (markCanvas.height - defaultHeight) / 2;
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
            $('#positionPages').val(1);
            $('#positionWidth').val(defaultWidth);
            $('#positionHeight').val(defaultHeight);

            drawTextHeader('15px Sarabun', startX + 3, startY + 25, 'องค์การบริหารส่วนตำบลแปลงยาว');
            drawTextHeader('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
            drawTextHeader('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
            drawTextHeader('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');

            // Drag logic
            var isDragging = false;
            var dragOffsetX = 0;
            var dragOffsetY = 0;
            var isResizing = false;
            var resizeHandleSize = 16;

            function redrawStampBox() {
                markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                // Calculate scale for both box and text based on current box size
                var boxW = markCoordinates.endX - markCoordinates.startX;
                var boxH = markCoordinates.endY - markCoordinates.startY;
                $('#positionWidth').val(boxW);
                $('#positionHeight').val(boxH);
                var defaultWidth = 213;
                var defaultHeight = 115;
                // Use the smaller scale of width/height to keep aspect ratio
                var scaleW = boxW / defaultWidth;
                var scaleH = boxH / defaultHeight;
                var scale = Math.min(scaleW, scaleH);
                // Minimum scale = 0.5, Maximum scale = 2.5
                scale = Math.max(0.5, Math.min(2.5, scale));
                // Draw the box using current coordinates (do not overwrite endX/endY)
                drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);
                // Draw text with scaled font and position
                drawTextHeader((15 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 3 * scale, markCoordinates.startY + 25 * scale, 'องค์การบริหารส่วนตำบลแปลงยาว');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 55 * scale, 'รับที่..........................................................');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 80 * scale, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeader((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 100 * scale, 'เวลา......................................................น.');
            }

            // Helper: check if mouse is on resize handle (bottom-right corner)
            function isOnResizeHandle(mouseX, mouseY) {
                return (
                    mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
                    mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
                );
            }

            // Change cursor when hovering resize handle
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

            // Prevent accidental reset of box when clicking outside
            markCanvas.addEventListener('click', function(e) {
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;
                // Only allow click to reset if click is outside the box and not resizing/dragging
                if (
                    !isDragging && !isResizing &&
                    (mouseX < markCoordinates.startX || mouseX > markCoordinates.endX ||
                    mouseY < markCoordinates.startY || mouseY > markCoordinates.endY)
                ) {
                    // Prevent reset: do nothing
                    e.stopPropagation();
                    e.preventDefault();
                }
            }, true);

            function onDragMove(e) {
                if (!isDragging) return;
                // Calculate mouse position relative to canvas
                var rect = markCanvas.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;
                var mouseY = e.clientY - rect.top;
                // Keep current box size
                var boxW = markCoordinates.endX - markCoordinates.startX;
                var boxH = markCoordinates.endY - markCoordinates.startY;
                var newStartX = mouseX - dragOffsetX;
                var newStartY = mouseY - dragOffsetY;
                // Clamp to canvas bounds
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
                // Clamp min size
                var minW = 40, minH = 30;
                var newEndX = Math.max(markCoordinates.startX + minW, mouseX);
                var newEndY = Math.max(markCoordinates.startY + minH, mouseY);
                // Clamp to canvas
                newEndX = Math.min(markCanvas.width, newEndX);
                newEndY = Math.min(markCanvas.height, newEndY);
                // Set only the width/height, keep startX/startY fixed
                markCoordinates.endX = newEndX;
                markCoordinates.endY = newEndY;
                redrawStampBox();
                showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
            }

            function onResizeEnd(e) {
                isResizing = false;
                window.removeEventListener('mousemove', onResizeMove);
                window.removeEventListener('mouseup', onResizeEnd);
            }

            function onDragEnd(e) {
                isDragging = false;
                window.removeEventListener('mousemove', onDragMove);
                window.removeEventListener('mouseup', onDragEnd);
            }
            //เกษียณพับครึ่ง
            markEventListenerInsert = function(e) {
                var markCanvas = document.getElementById('mark-layer-insert');
                var markCtx = markCanvas.getContext('2d');
                var rect = markCanvas.getBoundingClientRect();
                var startX = (e.clientX - rect.left);
                var startY = (e.clientY - rect.top);

                var endX = startX + 213;
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

                drawTextHeaderInsert('15px Sarabun', startX + 3, startY + 25, 'องค์การบริหารส่วนตำบลแปลงยาว');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeaderInsert('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');
            };

            var markCanvasInsert = document.getElementById('mark-layer-insert');
            markCanvasInsert.addEventListener('click', markEventListenerInsert);
        });

        $('#number-stamp').click(function(e) {
            e.preventDefault();
            removeMarkListener();
            document.getElementById('number-save').disabled = false;

            markEventListener = function(e) {
                var markCanvas = document.getElementById('mark-layer');
                var markCtx = markCanvas.getContext('2d');
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
            markCanvas.addEventListener('click', markEventListener);
        });

        function removeMarkListener() {
            var markCanvas = document.getElementById('mark-layer');
            var markCanvasInsert = document.getElementById('mark-layer-insert');
            if (markEventListener) {
                markEventListener = null;
            }
            if (markEventListenerInsert) {
                markEventListenerInsert = null;
            }
            $('#positionX').val('');
            $('#positionY').val('');
            $('#positionPages').val('');
            $('#positionWidth').val('');
            $('#positionHeight').val('');
        }

        // ฟังก์ชันในการวาดกากบาทเล็กๆ ที่มุมขวาบน
        function drawMark(startX, startY, endX, endY) {
            // วาดกรอบตราประทับ (ไม่มีเครื่องหมายกากบาท)
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
            markCtx.beginPath();
            markCtx.rect(startX, startY, endX - startX, endY - startY);
            markCtx.lineWidth = 0.5;
            markCtx.strokeStyle = 'blue';
            markCtx.stroke();
            // Draw resize handle (bottom-right)
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
            //เคลียร์กรอบเดิมของหน้าหนังสือ
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            var markCanvas = document.getElementById('mark-layer-insert');
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

                if (
                    clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize
                ) {
                    removeMarkListener();
                    var markCtx = markCanvas.getContext('2d');
                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height); // เคลียร์แคนวาส
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

                if (
                    clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize
                ) {
                    removeMarkListener();
                    var markCtx = markCanvas.getContext('2d');
                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height); // เคลียร์แคนวาส
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
            var markCtx = markCanvas.getContext('2d');

            markCtx.font = type;
            markCtx.fillStyle = "blue";
            markCtx.fillText(text, startX, startY);
        }
    }

    let markEventListener = null;
    let markEventListenerInsert = null;

    function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
        $('.btn-default').hide();
        $('#div-showPdf').show();
        $('#div-uploadPdf').hide();
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('number-save').disabled = true;
        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
        pdf(url);
        $('#id').val(id);
        $('#position_id').val(position_id);
        $('#number_id').val(number_id);
        $('#positionX').val('');
        $('#positionY').val('');
        document.getElementById('add-stamp').disabled = true;
        // if (permission_id != '1') {
        if (type == 1) {
            if (status == 1) {
                $('#add-stamp').show();
                $('#save-stamp').show();
                $('#insert-pages').show();
            }
            if (status == 2) {
                $('#send-to').show();
            }
        }
        if (type == 2) {
            if (is_check == '' || is_check == 'null') {
                $('#number-stamp').show();
                $('#number-save').show();
                $('#add-stamp').hide();
                $('#save-stamp').hide();

            } else {
                if (status == 2) {
                    $('#send-to').show();
                }
            }
        }
        // }
        resetMarking();
        removeMarkListener();
    }

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
        $('#positionX').val('');
        $('#positionY').val('');
        $('#positionPages').val('');
        $('#positionWidth').val('');
        $('#positionHeight').val('');
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
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
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

    $('#save-stamp').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var positionPages = $('#positionPages').val();
        var positionWidth = $('#positionWidth').val();
        var positionHeight = $('#positionHeight').val();
        var pages = $('#page-select').find(":selected").val();
        if (id != '' && positionX != '' && positionY != '') {
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
                        url: "/book/save_stamp",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            positionPages: positionPages,
                            width: positionWidth,
                            height: positionHeight,
                            pages: pages
                        },
                        dataType: "json",
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
                }
            });
        } else {
            Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info");
        }
    });
    $('#number-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = $('#page-select').find(":selected").val();
        var number_id = $('#number_id').val();
        if (id != '' && positionX != '' && positionY != '') {
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
                // ดึงค่าที่เลือกจาก Select2
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

    function showCancelStampBtn(x, y) {
        let cancelBtn = document.getElementById('cancel-stamp-btn');
        var markCanvas = document.getElementById('mark-layer');
        if (!cancelBtn) {
            cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancel-stamp-btn';
            cancelBtn.className = 'btn btn-danger btn-sm';
            cancelBtn.innerText = 'x';
            cancelBtn.style.position = 'fixed'; // เปลี่ยนเป็น fixed
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
        // คำนวณตำแหน่งปุ่มจากตำแหน่งกล่องตราประทับบนจอจริง
        const rect = markCanvas.getBoundingClientRect();
        const btnLeft = rect.left + x;
        const btnTop = rect.top + y - 40; // 40px เหนือกล่อง
        cancelBtn.style.left = btnLeft + 'px';
        cancelBtn.style.top = btnTop + 'px';
        cancelBtn.style.display = 'block';
    }
    function hideCancelStampBtn() {
        let cancelBtn = document.getElementById('cancel-stamp-btn');
        if (cancelBtn) cancelBtn.remove();
    }
    // ====== Hide cancel button on page load and when no stamp box ======
    document.addEventListener('DOMContentLoaded', function() {
        hideCancelStampBtn();
    });
    // ====== Hide cancel button when removeMarkListener is called ======
    const _oldRemoveMarkListener = removeMarkListener;
    removeMarkListener = function() {
        hideCancelStampBtn();
        _oldRemoveMarkListener.apply(this, arguments);
    };

    // --- Resize logic for stamp box ---
    var isResizing = false;
    var resizeHandleSize = 16;

    // Helper: check if mouse is on resize handle (bottom-right corner)
    function isOnResizeHandle(mouseX, mouseY) {
        return (
            mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
            mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
        );
    }

    // Patch drawMark to draw resize handle
    function drawMark(startX, startY, endX, endY) {
        var markCanvas = document.getElementById('mark-layer');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
        markCtx.beginPath();
        markCtx.rect(startX, startY, endX - startX, endY - startY);
        markCtx.lineWidth = 0.5;
        markCtx.strokeStyle = 'blue';
        markCtx.stroke();
        // Draw resize handle (bottom-right)
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

    // Change cursor when hovering resize handle
    markCanvas.addEventListener('mousemove', function(e) {
        var rect = markCanvas.getBoundingClientRect();
        var mouseX = e.clientX - rect.left;
        var mouseY = e.clientY - rect.top;
        if (isResizing) {
            markCanvas.style.cursor = 'se-resize';
        } else if (isOnResizeHandle(mouseX, mouseY)) {
            markCanvas.style.cursor = 'se-resize';
        } else if (
            markCoordinates &&
            mouseX >= markCoordinates.startX && mouseX <= markCoordinates.endX &&
            mouseY >= markCoordinates.startY && mouseY <= markCoordinates.endY
        ) {
            markCanvas.style.cursor = 'move';
        } else {
            markCanvas.style.cursor = 'default';
        }
    });

    // Merge drag/resize logic
    markCanvas.onmousedown = function(e) {
        var rect = markCanvas.getBoundingClientRect();
        var mouseX = e.clientX - rect.left;
        var mouseY = e.clientY - rect.top;
        if (isOnResizeHandle(mouseX, mouseY)) {
            isResizing = true;
            // Prevent text selection and other default actions
            e.preventDefault();
            window.addEventListener('mousemove', onResizeMove);
            window.addEventListener('mouseup', onResizeEnd);
        } else if (
            markCoordinates &&
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

    function onResizeMove(e) {
        if (!isResizing) return;
        // Prevent text selection while resizing
        e.preventDefault();
        var rect = markCanvas.getBoundingClientRect();
        var mouseX = e.clientX - rect.left;
        var mouseY = e.clientY - rect.top;
        // Clamp min size
        var minW = 40,
            minH = 30;
        var newEndX = Math.max(markCoordinates.startX + minW, mouseX);
        var newEndY = Math.max(markCoordinates.startY + minH, mouseY);
        // Clamp to canvas
        newEndX = Math.min(markCanvas.width, newEndX);
        newEndY = Math.min(markCanvas.height, newEndY);
        markCoordinates.endX = newEndX;
        markCoordinates.endY = newEndY;
        $('#positionX').val(markCoordinates.startX);
        $('#positionY').val(markCoordinates.startY);
        redrawStampBox();
        showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
    }

    function onResizeEnd(e) {
        isResizing = false;
        window.removeEventListener('mousemove', onResizeMove);
        window.removeEventListener('mouseup', onResizeEnd);
    }
</script>
<script>
    var input_hiddenFiles = '';
    browseBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function(event) {
        var file = event.target.files[0];
        if (file && file.type === 'application/pdf') {
            handlePDF(file);
            // fileInput.value = '';
        } else {
            Swal.fire({
                title: "เฉพาะไฟล์นามสกุลที่เป็น .pdf",
                icon: "info",
                confirmButtonText: "ตกลง",
            });
        }
    });

    function handlePDF(file) {
        $('#upload-area').hide();
        $('#pdf-container').show();
        document.getElementById('save-pdf').disabled = false;
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

    $('#save-pdf').click(function(e) {
        e.preventDefault();
        var fileInput = document.getElementById('file-input');
        var file = fileInput.files[0];
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