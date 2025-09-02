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
    var pdfLoadSeq = 0;

    var imgData = null;
    // Preload signature image and track load state
    var signatureImg = new Image();
    var signatureImgLoaded = false;
    signatureImg.onload = function() { signatureImgLoaded = true; };
    signatureImg.src = signature;
    // Store coordinates for draggable boxes
    var signatureCoordinates = null;

    function pdf(url) {
        const thisLoad = ++pdfLoadSeq;
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCanvasInsert = document.getElementById('pdf-render-insert'),
            pdfCtx = pdfCanvas.getContext('2d'),
            pdfCtxInsert = pdfCanvasInsert ? pdfCanvasInsert.getContext('2d') : null,
            markCanvas = document.getElementById('mark-layer'),
            markCtx = markCanvas.getContext('2d'),
            selectPage = document.getElementById('page-select');

        // Reset page selector to avoid duplicated options/listeners
        if (selectPage) {
            const cleanSelect = selectPage.cloneNode(false);
            selectPage.parentNode.replaceChild(cleanSelect, selectPage);
            selectPage = cleanSelect;
        }

        document.getElementById('manager-save').disabled = true;

        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({ scale: scale });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;
                markCanvas.height = viewport.height;
                markCanvas.width = viewport.width;

                let renderContext = { canvasContext: pdfCtx, viewport: viewport };
                let renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            if (selectPage) selectPage.value = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) { pageNumPending = num; }
            else { renderPage(num); }
        }

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        }

        function onPrevPage() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        }

        if (selectPage) {
            selectPage.addEventListener('change', function() {
                let selectedPage = parseInt(this.value);
                if (selectedPage && selectedPage >= 1 && selectedPage <= pdfDoc.numPages) {
                    pageNum = selectedPage;
                    queueRenderPage(selectedPage);
                }
            });
        }

        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            if (thisLoad !== pdfLoadSeq) return; // stale load, ignore
            pdfDoc = pdfDoc_;
            if (selectPage) {
                for (let i = 1; i <= pdfDoc.numPages; i++) {
                    let option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    selectPage.appendChild(option);
                }
            }
            renderPage(pageNum);
            document.getElementById('manager-sinature').disabled = false;
        });

        document.getElementById('next').onclick = onNextPage;
        document.getElementById('prev').onclick = onPrevPage;

        function drawMarkSignature(startX, startY, endX, endY, checkedValues) {
            var markCanvasIns = document.getElementById('mark-layer-insert');
            if (markCanvasIns) {
                var insCtx = markCanvasIns.getContext('2d');
                insCtx.clearRect(0, 0, markCanvasIns.width, markCanvasIns.height);
            }
            var markCanvasMain = document.getElementById('mark-layer');
            var markCtxMain = markCanvasMain.getContext('2d');
            markCtxMain.clearRect(0, 0, markCanvasMain.width, markCanvasMain.height);

            checkedValues.forEach(element => {
                if (element == 4) {
                    var img = new Image();
                    img.src = signature;
                    img.onload = function() {
                        var imgWidth = 240, imgHeight = 130;
                        var centeredX = (startX + 50) - (imgWidth / 2);
                        var centeredY = (startY + 60) - (imgHeight / 2);
                        markCtxMain.drawImage(img, centeredX, centeredY, imgWidth, imgHeight);
                        imgData = { x: centeredX, y: centeredY, width: imgWidth, height: imgHeight };
                    }
                }
            });
        }

        function drawMarkSignatureInsert(startX, startY, endX, endY, checkedValues) {
            var markCanvasMain = document.getElementById('mark-layer');
            var markCtxMain = markCanvasMain.getContext('2d');
            markCtxMain.clearRect(0, 0, markCanvasMain.width, markCanvasMain.height);

            var markCanvasIns = document.getElementById('mark-layer-insert');
            if (!markCanvasIns) return;
            var insCtx = markCanvasIns.getContext('2d');
            insCtx.clearRect(0, 0, markCanvasIns.width, markCanvasIns.height);

            checkedValues.forEach(element => {
                if (element == 4) {
                    var img = new Image();
                    img.src = signature;
                    img.onload = function() {
                        var imgWidth = 240, imgHeight = 130;
                        var centeredX = (startX + 50) - (imgWidth / 2);
                        var centeredY = (startY + 60) - (imgHeight / 2);
                        insCtx.drawImage(img, centeredX, centeredY, imgWidth, imgHeight);
                        imgData = { x: centeredX, y: centeredY, width: imgWidth, height: imgHeight };
                    }
                }
            });
        }

        function drawTextHeaderSignature(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            var lines = String(text || '').split('\n');
            var lineHeight = 20;
            for (var i = 0; i < lines.length; i++) {
                var textWidth = markCtx.measureText(lines[i]).width;
                var centeredX = startX - (textWidth / 2);
                markCtx.fillText(lines[i], centeredX, startY + (i * lineHeight));
            }
        }

        function drawTextHeaderSignatureInsert(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer-insert');
            if (!markCanvas) return;
            var markCtx = markCanvas.getContext('2d');
            markCtx.font = type;
            markCtx.fillStyle = "blue";
            var lines = String(text || '').split('\n');
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
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.status) {
                        $('#exampleModal').modal('hide');
                        setTimeout(() => { swal.close(); }, 1500);
                        resetMarking();
                        removeMarkListener();
                        document.getElementById('manager-save').disabled = false;

                        // === Drag & Resize selection (main canvas) ===
                        document.getElementById('manager-sinature').disabled = true;
                        document.getElementById('manager-save').disabled = false;

                        var markCanvas = document.getElementById('mark-layer');
                        var markCtx = markCanvas.getContext('2d');

                        // Default boxes (ขนาด)
                        var defaultTextWidth = 220;
                        var defaultTextHeight = 40;
                        var defaultBottomBoxHeight = 80;
                        var defaultImageWidth = 240;
                        var defaultImageHeight = 130;
                        var gap = 10;

                        // จัดเรียง: text (บน) → image (กลาง) → bottom (ล่าง)
                        var startX = (markCanvas.width - defaultTextWidth) / 2;
                        var totalH = defaultTextHeight + gap + defaultImageHeight + gap + defaultBottomBoxHeight + 20;
                        var startY = (markCanvas.height - totalH) / 2;

                        signatureCoordinates = {
                            textBox: {
                                startX: startX,
                                startY: startY,
                                endX: startX + defaultTextWidth,
                                endY: startY + defaultTextHeight,
                                type: 'text'
                            },
                            imageBox: {
                                startX: startX - 13,
                                startY: startY + defaultTextHeight + gap, // ถัดจากกล่องบน
                                endX: (startX - 13) + defaultImageWidth,
                                endY: startY + defaultTextHeight + gap + defaultImageHeight,
                                type: 'image'
                            },
                            bottomBox: {
                                startX: startX,
                                startY: startY + defaultTextHeight + gap + defaultImageHeight + gap, // อยู่ล่างสุด
                                endX: startX + defaultTextWidth,
                                endY: startY + defaultTextHeight + gap + defaultImageHeight + gap + defaultBottomBoxHeight,
                                type: 'bottom'
                            }
                        };

                        $('#positionX').val(startX);
                        $('#positionY').val(startY);
                        $('#positionPages').val(1);

                        redrawSignatureBoxes();

                        var isDragging = false;
                        var isResizing = false;
                        var activeBox = null;
                        var dragOffsetX = 0;
                        var dragOffsetY = 0;
                        var resizeHandleSize = 16;

                        function redrawSignatureBoxes() {
                            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

                            var text = $('#modal-text').val();
                            var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();

                            // --- วาด 1) กล่องข้อความบน ---
                            var textBox = signatureCoordinates.textBox;
                            markCtx.save();
                            markCtx.strokeStyle = 'blue';
                            markCtx.lineWidth = 0.5;
                            markCtx.strokeRect(textBox.startX, textBox.startY, textBox.endX - textBox.startX, textBox.endY - textBox.startY);
                            markCtx.fillStyle = '#fff';
                            markCtx.strokeStyle = '#007bff';
                            markCtx.lineWidth = 2;
                            markCtx.fillRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                            markCtx.strokeRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                            markCtx.restore();

                            var textScale = Math.min((textBox.endX - textBox.startX) / 220, (textBox.endY - textBox.startY) / 40);
                            textScale = Math.max(0.5, Math.min(2.5, textScale));
                            drawTextHeaderSignature((15 * textScale).toFixed(1) + 'px Sarabun',
                                (textBox.startX + textBox.endX) / 2,
                                textBox.startY + 25 * textScale,
                                text);

                            // --- วาด 2) ลายเซ็น (กรอบเขียว) ---
                            var hasImage = checkedValues.includes('4');
                            if (false && hasImage) {
                                var imageBox = signatureCoordinates.imageBox;
                                markCtx.save();
                                markCtx.strokeStyle = 'green';
                                markCtx.lineWidth = 0.5;
                                markCtx.strokeRect(imageBox.startX, imageBox.startY, imageBox.endX - imageBox.startX, imageBox.endY - imageBox.startY);
                                markCtx.fillStyle = '#fff';
                                markCtx.strokeStyle = '#28a745';
                                markCtx.lineWidth = 2;
                                markCtx.fillRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                                markCtx.strokeRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                                markCtx.restore();

                                var imgWidth = imageBox.endX - imageBox.startX;
                                var imgHeight = imageBox.endY - imageBox.startY;
                                if (signatureImgLoaded) {
                                    markCtx.drawImage(signatureImg, imageBox.startX, imageBox.startY, imgWidth, imgHeight);
                                    imgData = { x: imageBox.startX, y: imageBox.startY, width: imgWidth, height: imgHeight };
                                }
                            }

                            // --- วาด 3) กล่องข้อความล่าง ---
                            var bottomBox = signatureCoordinates.bottomBox;
                            markCtx.save();
                            markCtx.strokeStyle = 'purple';
                            markCtx.lineWidth = 0.5;
                            markCtx.strokeRect(bottomBox.startX, bottomBox.startY, bottomBox.endX - bottomBox.startX, bottomBox.endY - bottomBox.startY);
                            markCtx.fillStyle = '#fff';
                            markCtx.strokeStyle = '#6f42c1';
                            markCtx.lineWidth = 2;
                            markCtx.fillRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                            markCtx.strokeRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
                            markCtx.restore();

                            var bottomScale = Math.min((bottomBox.endX - bottomBox.startX) / 220, (bottomBox.endY - bottomBox.startY) / 80);
                            bottomScale = Math.max(0.5, Math.min(2.5, bottomScale));
                            // Draw signature image inside bottomBox (top) then text below
                            var baseY = bottomBox.startY + 25 * bottomScale;
                            if (hasImage && signatureImgLoaded) {
                                var innerPad = 8;
                                var contentW = (bottomBox.endX - bottomBox.startX) - innerPad*2;
                                var contentH = (bottomBox.endY - bottomBox.startY) - innerPad*2;
                                var ar = 240/130; // signature aspect ratio approx
                                var maxImgW = contentW;
                                var maxImgH = Math.max(30, contentH * 0.55);
                                var imgW = maxImgW;
                                var imgH = imgW / ar;
                                if (imgH > maxImgH) { imgH = maxImgH; imgW = imgH * ar; }
                                var imgX = bottomBox.startX + ((bottomBox.endX - bottomBox.startX) - imgW) / 2;
                                var imgY = bottomBox.startY + innerPad;
                                markCtx.drawImage(signatureImg, imgX, imgY, imgW, imgH);
                                imgData = { x: imgX, y: imgY, width: imgW, height: imgH };
                                baseY = imgY + imgH + 10;
                            }
                            var i = 0;
                            checkedValues.forEach(function (element) {
                                if (element != 4) {
                                    var checkbox_text = '';
                                    switch (element) {
                                        case '1': checkbox_text = `({{$users->fullname}})`; break;
                                        case '2': checkbox_text = `{{$permission_data->permission_name}}`; break;
                                        case '3': checkbox_text = `{{convertDateToThai(date("Y-m-d"))}}`; break;
                                    }
                            drawTextHeaderSignature((15 * bottomScale).toFixed(1) + 'px Sarabun',
                                (bottomBox.startX + bottomBox.endX) / 2,
                                baseY + (20 * i * bottomScale),
                                checkbox_text);
                                    i++;
                                }
                            });
                        }

                        function isOnResizeHandle(mouseX, mouseY, box) {
                            return (
                                mouseX >= box.endX - resizeHandleSize && mouseX <= box.endX &&
                                mouseY >= box.endY - resizeHandleSize && mouseY <= box.endY
                            );
                        }

                        function isInBox(mouseX, mouseY, box) {
                            return (
                                mouseX >= box.startX && mouseX <= box.endX &&
                                mouseY >= box.startY && mouseY <= box.endY
                            );
                        }

                        function getActiveBox(mouseX, mouseY) {
                            var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();
                            var hasImage = checkedValues.includes('4');
                            // ลำดับตรวจจับ: bottom → image → text (แล้วแต่ถนัด ไม่กระทบเลย์เอาต์)
                            if (isInBox(mouseX, mouseY, signatureCoordinates.bottomBox)) {
                                return signatureCoordinates.bottomBox;
                            } else if (hasImage && isInBox(mouseX, mouseY, signatureCoordinates.imageBox)) {
                                return signatureCoordinates.imageBox;
                            } else if (isInBox(mouseX, mouseY, signatureCoordinates.textBox)) {
                                return signatureCoordinates.textBox;
                            }
                            return null;
                        }

                        markCanvas.addEventListener('mousemove', function (e) {
                            var rect = markCanvas.getBoundingClientRect();
                            var x = e.clientX - rect.left;
                            var y = e.clientY - rect.top;
                            var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();
                            var hasImage = checkedValues.includes('4');
                            if (isOnResizeHandle(x, y, signatureCoordinates.textBox) ||
                                isOnResizeHandle(x, y, signatureCoordinates.bottomBox) ||
                                (hasImage && isOnResizeHandle(x, y, signatureCoordinates.imageBox))) {
                                markCanvas.style.cursor = 'se-resize';
                            } else if (getActiveBox(x, y)) {
                                markCanvas.style.cursor = 'move';
                            } else {
                                markCanvas.style.cursor = 'default';
                            }
                        });

                        markCanvas.onmousedown = function (e) {
                            var rect = markCanvas.getBoundingClientRect();
                            var x = e.clientX - rect.left;
                            var y = e.clientY - rect.top;
                            var checkedValues = $('input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();
                            var hasImage = checkedValues.includes('4');
                            if (isOnResizeHandle(x, y, signatureCoordinates.textBox)) {
                                isResizing = true; activeBox = signatureCoordinates.textBox;
                                e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                            } else if (isOnResizeHandle(x, y, signatureCoordinates.bottomBox)) {
                                isResizing = true; activeBox = signatureCoordinates.bottomBox;
                                e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                            } else if (hasImage && isOnResizeHandle(x, y, signatureCoordinates.imageBox)) {
                                isResizing = true; activeBox = signatureCoordinates.imageBox;
                                e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                            } else {
                                activeBox = getActiveBox(x, y);
                                if (activeBox) {
                                    isDragging = true;
                                    dragOffsetX = x - activeBox.startX;
                                    dragOffsetY = y - activeBox.startY;
                                    e.preventDefault();
                                    window.addEventListener('mousemove', onDragMove);
                                    window.addEventListener('mouseup', onDragEnd);
                                }
                            }
                        };

                        function onDragMove(e) {
                            if (!isDragging || !activeBox) return;
                            var rect = markCanvas.getBoundingClientRect();
                            var x = e.clientX - rect.left;
                            var y = e.clientY - rect.top;
                            var w = activeBox.endX - activeBox.startX;
                            var h = activeBox.endY - activeBox.startY;
                            var nsx = x - dragOffsetX;
                            var nsy = y - dragOffsetY;
                            nsx = Math.max(0, Math.min(markCanvas.width - w, nsx));
                            nsy = Math.max(0, Math.min(markCanvas.height - h, nsy));
                            activeBox.startX = nsx;
                            activeBox.startY = nsy;
                            activeBox.endX = nsx + w;
                            activeBox.endY = nsy + h;
                            if (activeBox.type === 'text') {
                                $('#positionX').val(nsx);
                                $('#positionY').val(nsy);
                            }
                            redrawSignatureBoxes();
                        }

                        function onResizeMove(e) {
                            if (!isResizing || !activeBox) return;
                            var rect = markCanvas.getBoundingClientRect();
                            var x = e.clientX - rect.left;
                            var y = e.clientY - rect.top;
                            var minW = 40, minH = 30;
                            var newEndX = Math.max(activeBox.startX + minW, x);
                            var newEndY = Math.max(activeBox.startY + minH, y);
                            newEndX = Math.min(markCanvas.width, newEndX);
                            newEndY = Math.min(markCanvas.height, newEndY);
                            activeBox.endX = newEndX;
                            activeBox.endY = newEndY;
                            redrawSignatureBoxes();
                        }

                        function onDragEnd(e) {
                            isDragging = false;
                            activeBox = null;
                            window.removeEventListener('mousemove', onDragMove);
                            window.removeEventListener('mouseup', onDragEnd);
                        }

                        function onResizeEnd(e) {
                            isResizing = false;
                            activeBox = null;
                            window.removeEventListener('mousemove', onResizeMove);
                            window.removeEventListener('mouseup', onResizeEnd);
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
        if (markEventListenerInsert && markCanvasInsert) {
            markCanvasInsert.removeEventListener('click', markEventListenerInsert);
            markEventListenerInsert = null;
        }
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
        if (markCanvasInsert) {
            var markCtxInsert = markCanvasInsert.getContext('2d');
            markCtxInsert.clearRect(0, 0, markCanvasInsert.width, markCanvasInsert.height);
        }
    }

    selectPageTable.addEventListener('change', function() {
        let selectedPage = parseInt(this.value);
        ajaxTable(selectedPage);
    });

    function onNextPageTable() {
        if (pageNumTalbe >= pageTotal) return;
        pageNumTalbe++;
        selectPageTable.value = pageNumTalbe;
        ajaxTable(pageNumTalbe);
    }

    function onPrevPageTable() {
        if (pageNumTalbe <= 1) return;
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
            data: { pages: pages },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').empty();
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    response.book.forEach(element => {
                        var color = (element.type != 1) ? 'warning' : 'info';
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ',' + "'" + element.type + "'" + ',' + "'" + element.is_number_stamp + "'" + ',' + "'" + element.inputBookregistNumber + "'" + ',' + "'" + element.position_id + "'" + ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append($html);
                    });
                }
            }
        });
    }

    $('#search_btn').click(function(e) {
        e.preventDefault();
        $('#id').val(''); $('#positionX').val(''); $('#positionY').val('');
        $('.btn-default').hide(); $('#txt_label').text(''); $('#users_id').val('');
        document.getElementById('manager-sinature').disabled = false;
        document.getElementById('manager-save').disabled = true;
        $.ajax({
            type: "post",
            url: "/book/dataListSearch",
            data: { pages: 1, search: $('#inputSearch').val() },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').html('');
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    pageNumTalbe = 1;
                    pageTotal = response.totalPages;
                    response.book.forEach(element => {
                        var color = (element.type != 1) ? 'warning' : 'info';
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

    // Allow pressing Enter in the search box to trigger search
    $('#inputSearch').on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            $('#search_btn').click();
        }
    });

    $('#manager-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();

        var positionPages = $('#positionPages').val();
        var pages = $('#page-select').find(":selected").val();
        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function() { return $(this).val(); }).get();
        var textBox = signatureCoordinates ? signatureCoordinates.textBox : null;
        var imageBox = signatureCoordinates ? signatureCoordinates.imageBox : null;
        var bottomBox = signatureCoordinates ? signatureCoordinates.bottomBox : null;

        var positionX = null, positionY = null, width = null, height = null;
        if (textBox) {
            positionX = textBox.startX;
            positionY = textBox.startY;
            width = textBox.endX - textBox.startX;
            height = textBox.endY - textBox.startY;
        }

        if (id != '' && positionX !== null && positionY !== null) {
            Swal.fire({
                title: "ยืนยันการลงลายเซ็น",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    var data = {
                        id: id,
                        positionX: positionX,
                        positionY: positionY,
                        pages: pages,
                        positionPages: positionPages,
                        status: 7,
                        text: text,
                        checkedValues: checkedValues,
                        width: width,
                        height: height
                    };
                    if (bottomBox) {
                        var bbStartY = bottomBox.startY;
                        if (imgData && checkedValues.includes('4')){
                            bbStartY = imgData.y + imgData.height + 10;
                        }
                        data.bottomBox = {
                            startX: bottomBox.startX,
                            startY: bbStartY,
                            width: bottomBox.endX - bottomBox.startX,
                            height: Math.max(10, bottomBox.endY - bbStartY)
                        };
                    }
                    if (checkedValues.includes('4')) {
                        if (imgData) {
                            data.imageBox = { startX: imgData.x, startY: imgData.y, width: imgData.width, height: imgData.height };
                        } else if (imageBox) {
                            data.imageBox = {
                                startX: imageBox.startX,
                                startY: imageBox.startY,
                                width: imageBox.endX - imageBox.startX,
                                height: imageBox.endY - imageBox.startY
                            };
                        }
                    }
                    $.ajax({
                        type: "post",
                        url: "/book/manager_stamp",
                        data: data,
                        dataType: "json",
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "บันทึกลายเซ็นเรียบร้อยแล้ว", "success");
                                setTimeout(() => { location.reload(); }, 1500);
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
                Swal.fire({
                    title: 'แทงเรื่อง', html: response, allowOutsideClick: false, focusConfirm: true,
                    confirmButtonText: 'ตกลง', showCancelButton: true, cancelButtonText: `ยกเลิก`,
                    preConfirm: () => {
                        var selectedCheckboxes = [];
                        var textCheckboxes = [];
                        $('input[name="flexCheckChecked[]"]:checked').each(function() {
                            selectedCheckboxes.push($(this).val());
                            textCheckboxes.push($(this).next('label').text().trim());
                        });
                        if (selectedCheckboxes.length === 0) {
                            Swal.showValidationMessage('กรุณาเลือกตัวเลือก');
                        }
                        return { id: selectedCheckboxes, text: textCheckboxes };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var id = result.value.id.join(',');
                        var txt = '- แทงเรื่อง (' + result.value.text.join(',') + ') -';
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
                    data: { id: id, users_id: users_id, status: 8 },
                    dataType: "json",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success");
                            setTimeout(() => { location.reload(); }, 1500);
                        } else {
                            Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error");
                        }
                    }
                });
            }
        });
    });

    $(document).ready(function() {
        $('#manager-sinature').click(function(e) { e.preventDefault(); });
        $('#insert-pages').click(function(e) { e.preventDefault(); $('#insert_tab').show(); });
        $('#reject-book').click(function (e) {
            e.preventDefault();
            Swal.fire({
                title: "", text: "ยืนยันการปฏิเสธหนังสือหรือไม่", icon: "warning",
                input: 'textarea', inputPlaceholder: 'กรอกเหตุผลการปฏิเสธ33',
                showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33",
                cancelButtonText: "ยกเลิก", confirmButtonText: "ตกลง",
                preConfirm: (note) => { if (!note) { Swal.showValidationMessage('กรุณากรอกเหตุผล'); } return note; }
            }).then((result) => {
                if (result.isConfirmed) {
                    var id = $('#id').val();
                    var note = result.value;
                    $.ajax({
                        type: "post",
                        url: "/book/reject",
                        data: { id: id, note: note },
                        dataType: "json",
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function (response) {
                            if (response.status) {
                                Swal.fire("", "ปฏิเสธเรียบร้อย", "success");
                                setTimeout(() => { location.reload(); }, 1500);
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

            const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
            loadingTask.promise.then(pdf => pdf.getPage(1)).then(page => {
                const scale = 1.5;
                const viewport = page.getViewport({ scale });
                const canvas = document.getElementById("pdf-render-insert");
                if (!canvas) return;
                const context = canvas.getContext("2d");
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                const renderContext = { canvasContext: context, viewport: viewport };
                return page.render(renderContext).promise;
            }).catch(error => console.error("Error rendering PDF:", error));
        }

        createAndRenderPDF();
    });
</script>
@endsection
