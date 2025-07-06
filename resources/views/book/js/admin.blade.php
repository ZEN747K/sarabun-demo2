@section('script')
    <?php $position = $itemParent; ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $('.btn-default').hide();
        var signature = '{{$signature}}';
        var selectPageTable = document.getElementById('page-select-card');
        var pageTotal = '{{$totalPages}}';
        var pageNumTalbe = 1;
        var permission = '{{$permission}}';

        var imgData = null;
        // Make markCoordinates global so all handlers can access it
        var markCoordinates = null;
        // Add global variable for signature coordinates
        var signatureCoordinates = null;

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

            // markCoordinates is now global

            document.getElementById('add-stamp').disabled = true;

            function renderPage(num) {
                pageRendering = true;

                pdfDoc.getPage(num).then(function (page) {
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

                    renderTask.promise.then(function () {
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

            selectPage.addEventListener('change', function () {
                let selectedPage = parseInt(this.value);
                if (selectedPage && selectedPage >= 1 && selectedPage <= pdfDoc.numPages) {
                    pageNum = selectedPage;
                    queueRenderPage(selectedPage);
                }
            });

            pdfjsLib.getDocument(url).promise.then(function (pdfDoc_) {
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


            // Enhanced add-stamp with drag and resize functionality
            $('#add-stamp').click(function (e) {
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

                var text = '{{$position_name}}';
                var dynamicX;
                if (text.length >= 30) {
                    dynamicX = 5;
                } else if (text.length >= 20) {
                    dynamicX = 10;
                } else if (text.length >= 15) {
                    dynamicX = 60;
                } else if (text.length >= 13) {
                    dynamicX = 75;
                } else if (text.length >= 10) {
                    dynamicX = 70;
                } else {
                    dynamicX = 80;
                }
                drawTextHeaderClassic('15px Sarabun', startX + dynamicX, startY + 25, text);
                drawTextHeaderClassic('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                drawTextHeaderClassic('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeaderClassic('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');

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
                    var text = '{{$position_name}}';
                    var dynamicX;
                    if (text.length >= 30) {
                        dynamicX = 5 * scale;
                    } else if (text.length >= 20) {
                        dynamicX = 10 * scale;
                    } else if (text.length >= 15) {
                        dynamicX = 60 * scale;
                    } else if (text.length >= 13) {
                        dynamicX = 75 * scale;
                    } else if (text.length >= 10) {
                        dynamicX = 70 * scale;
                    } else {
                        dynamicX = 80 * scale;
                    }
                    drawTextHeaderClassic((15 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + dynamicX, markCoordinates.startY + 25 * scale, text);
                    drawTextHeaderClassic((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 55 * scale, 'รับที่..........................................................');
                    drawTextHeaderClassic((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 80 * scale, 'วันที่.........เดือน......................พ.ศ.........');
                    drawTextHeaderClassic((12 * scale).toFixed(1) + 'px Sarabun', markCoordinates.startX + 8 * scale, markCoordinates.startY + 100 * scale, 'เวลา......................................................น.');
                }

                // Helper: check if mouse is on resize handle (bottom-right corner)
                function isOnResizeHandle(mouseX, mouseY) {
                    return (
                        mouseX >= markCoordinates.endX - resizeHandleSize && mouseX <= markCoordinates.endX &&
                        mouseY >= markCoordinates.endY - resizeHandleSize && mouseY <= markCoordinates.endY
                    );
                }

                // Change cursor when hovering resize handle
                markCanvas.addEventListener('mousemove', function (e) {
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

                markCanvas.onmousedown = function (e) {
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
                markCanvas.addEventListener('click', function (e) {
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
                markEventListenerInsert = function (e) {
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

                    var text = '{{$position_name}}';
                    var dynamicX;
                    if (text.length >= 30) {
                        dynamicX = 5;
                    } else if (text.length >= 20) {
                        dynamicX = 10;
                    } else if (text.length >= 15) {
                        dynamicX = 60;
                    } else if (text.length >= 13) {
                        dynamicX = 75;
                    } else if (text.length >= 10) {
                        dynamicX = 70;
                    } else {
                        dynamicX = 80;
                    }
                    drawTextHeaderClassicInsert('15px Sarabun', startX + dynamicX, startY + 25, text);
                    drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                    drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                    drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');
                };

                var markCanvasInsert = document.getElementById('mark-layer-insert');
                markCanvasInsert.addEventListener('click', markEventListenerInsert);
            });

            $('#modalForm').on('submit', function (e) {
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
                    success: function (response) {
                        if (response.status) {
                            $('#exampleModal').modal('hide');
                            setTimeout(() => {
                                swal.close();
                            }, 1500);
                            resetMarking();
                            removeMarkListener();
                            document.getElementById('signature-save').disabled = false;

                            // Replace the markEventListener function starting from line 380
                            markEventListener = function (e) {
                                var markCanvas = document.getElementById('mark-layer');
                                var markCtx = markCanvas.getContext('2d');
                                var rect = markCanvas.getBoundingClientRect();

                                // Only create initial coordinates on first click
                                if (!signatureCoordinates) {
                                    // Default position and sizes
                                    var defaultTextWidth = 213;
                                    var defaultTextHeight = 40;
                                    var defaultBottomBoxHeight = 80;
                                    var defaultImageWidth = 240;
                                    var defaultImageHeight = 130;

                                    var startX = (markCanvas.width - defaultTextWidth) / 2;
                                    var startY = (markCanvas.height - (defaultTextHeight + defaultBottomBoxHeight + defaultImageHeight + 40)) / 2;

                                    // Create separate boxes
                                    var textBox = {
                                        startX: startX,
                                        startY: startY,
                                        endX: startX + defaultTextWidth,
                                        endY: startY + defaultTextHeight,
                                        type: 'text'
                                    };

                                    var bottomBox = {
                                        startX: startX,
                                        startY: startY + defaultTextHeight + 10,
                                        endX: startX + defaultTextWidth,
                                        endY: startY + defaultTextHeight + 10 + defaultBottomBoxHeight,
                                        type: 'bottom'
                                    };

                                    var imageBox = {
                                        startX: startX - 13,
                                        startY: startY + defaultTextHeight + defaultBottomBoxHeight + 20,
                                        endX: startX + defaultImageWidth - 13,
                                        endY: startY + defaultTextHeight + defaultBottomBoxHeight + 20 + defaultImageHeight,
                                        type: 'image'
                                    };

                                    signatureCoordinates = {
                                        textBox: textBox,
                                        bottomBox: bottomBox,
                                        imageBox: imageBox
                                    };

                                    $('#positionX').val(startX);
                                    $('#positionY').val(startY);
                                    $('#positionPages').val(1);
                                }

                                // Draw boxes
                                redrawSignatureBoxes();

                                // Variables for drag and resize
                                var isDragging = false;
                                var isResizing = false;
                                var activeBox = null;
                                var dragOffsetX = 0;
                                var dragOffsetY = 0;
                                var resizeHandleSize = 16;

                                function redrawSignatureBoxes() {
                                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

                                    var text = $('#modal-text').val();
                                    var lineBreakCount = countLineBreaks(text);
                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();

                                    // Draw top text box (for signature text only)
                                    var textBox = signatureCoordinates.textBox;
                                    markCtx.save();
                                    markCtx.strokeStyle = 'blue';
                                    markCtx.lineWidth = 1;
                                    markCtx.strokeRect(textBox.startX, textBox.startY,
                                        textBox.endX - textBox.startX, textBox.endY - textBox.startY);

                                    // Draw resize handle for text box
                                    markCtx.fillStyle = '#fff';
                                    markCtx.strokeStyle = '#007bff';
                                    markCtx.lineWidth = 2;
                                    markCtx.fillRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.strokeRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.restore();

                                    // Draw signature text in top box
                                    var textScale = Math.min(
                                        (textBox.endX - textBox.startX) / 213,
                                        (textBox.endY - textBox.startY) / 40
                                    );
                                    textScale = Math.max(0.5, Math.min(2.5, textScale));

                                    drawTextHeaderSignature((15 * textScale).toFixed(1) + 'px Sarabun',
                                        (textBox.startX + textBox.endX) / 2, textBox.startY + 25 * textScale, text);

                                    // Draw bottom box (for name, position, date)
                                    var bottomBox = signatureCoordinates.bottomBox;
                                    markCtx.save();
                                    markCtx.strokeStyle = 'purple';
                                    markCtx.lineWidth = 1;
                                    markCtx.strokeRect(bottomBox.startX, bottomBox.startY,
                                        bottomBox.endX - bottomBox.startX, bottomBox.endY - bottomBox.startY);

                                    // Draw resize handle for bottom box
                                    markCtx.fillStyle = '#fff';
                                    markCtx.strokeStyle = '#6f42c1';
                                    markCtx.lineWidth = 2;
                                    markCtx.fillRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.strokeRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.restore();

                                    // Draw checkbox content in bottom box
                                    var bottomScale = Math.min(
                                        (bottomBox.endX - bottomBox.startX) / 213,
                                        (bottomBox.endY - bottomBox.startY) / 80
                                    );
                                    bottomScale = Math.max(0.5, Math.min(2.5, bottomScale));

                                    var i = 0;
                                    var checkbox_text = '';

                                    checkedValues.forEach(element => {
                                        if (element != 4) {
                                            switch (element) {
                                                case '1':
                                                    checkbox_text = `({{$users->fullname}})`;
                                                    break;
                                                case '2':
                                                    checkbox_text = `{{$permission_data->permission_name}}`;
                                                    break;
                                                case '3':
                                                    checkbox_text = `{{convertDateToThai(date("Y-m-d"))}}`;
                                                    break;
                                            }
                                            drawTextHeaderSignature((15 * bottomScale).toFixed(1) + 'px Sarabun',
                                                (bottomBox.startX + bottomBox.endX) / 2,
                                                bottomBox.startY + 25 * bottomScale + (20 * i * bottomScale),
                                                checkbox_text);
                                            i++;
                                        }
                                    });

                                    // ONLY draw image box if checkbox 4 is selected - FIX FOR GREEN BOX
                                    var hasImage = checkedValues.includes('4');
                                    if (hasImage) {
                                        var imageBox = signatureCoordinates.imageBox;
                                        markCtx.save();
                                        markCtx.strokeStyle = 'green';
                                        markCtx.lineWidth = 1;
                                        markCtx.strokeRect(imageBox.startX, imageBox.startY,
                                            imageBox.endX - imageBox.startX, imageBox.endY - imageBox.startY);

                                        // Draw resize handle for image box
                                        markCtx.fillStyle = '#fff';
                                        markCtx.strokeStyle = '#28a745';
                                        markCtx.lineWidth = 2;
                                        markCtx.fillRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize,
                                            resizeHandleSize, resizeHandleSize);
                                        markCtx.strokeRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize,
                                            resizeHandleSize, resizeHandleSize);
                                        markCtx.restore();

                                        // Draw signature image
                                        var img = new Image();
                                        img.src = signature;
                                        img.onload = function () {
                                            var imgWidth = imageBox.endX - imageBox.startX;
                                            var imgHeight = imageBox.endY - imageBox.startY;
                                            markCtx.drawImage(img, imageBox.startX, imageBox.startY, imgWidth, imgHeight);

                                            imgData = {
                                                x: imageBox.startX,
                                                y: imageBox.startY,
                                                width: imgWidth,
                                                height: imgHeight
                                            };
                                        }
                                    }
                                }

                                // Helper functions
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
                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    // Check bottom box FIRST (so it can be dragged)
                                    if (isInBox(mouseX, mouseY, signatureCoordinates.bottomBox)) {
                                        return signatureCoordinates.bottomBox;
                                    } else if (hasImage && isInBox(mouseX, mouseY, signatureCoordinates.imageBox)) {
                                        return signatureCoordinates.imageBox;
                                    } else if (isInBox(mouseX, mouseY, signatureCoordinates.textBox)) {
                                        return signatureCoordinates.textBox;
                                    }
                                    return null;
                                }

                                // Mouse events
                                markCanvas.addEventListener('mousemove', function (e) {
                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    // Check resize handles for all boxes
                                    if (isOnResizeHandle(mouseX, mouseY, signatureCoordinates.textBox) ||
                                        isOnResizeHandle(mouseX, mouseY, signatureCoordinates.bottomBox) ||
                                        (hasImage && isOnResizeHandle(mouseX, mouseY, signatureCoordinates.imageBox))) {
                                        markCanvas.style.cursor = 'se-resize';
                                    } else if (getActiveBox(mouseX, mouseY)) {
                                        markCanvas.style.cursor = 'move';
                                    } else {
                                        markCanvas.style.cursor = 'default';
                                    }
                                });

                                markCanvas.onmousedown = function (e) {
                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    // Check resize handles first
                                    if (isOnResizeHandle(mouseX, mouseY, signatureCoordinates.textBox)) {
                                        isResizing = true;
                                        activeBox = signatureCoordinates.textBox;
                                        e.preventDefault();
                                        window.addEventListener('mousemove', onResizeMove);
                                        window.addEventListener('mouseup', onResizeEnd);
                                    } else if (isOnResizeHandle(mouseX, mouseY, signatureCoordinates.bottomBox)) {
                                        isResizing = true;
                                        activeBox = signatureCoordinates.bottomBox;
                                        e.preventDefault();
                                        window.addEventListener('mousemove', onResizeMove);
                                        window.addEventListener('mouseup', onResizeEnd);
                                    } else if (hasImage && isOnResizeHandle(mouseX, mouseY, signatureCoordinates.imageBox)) {
                                        isResizing = true;
                                        activeBox = signatureCoordinates.imageBox;
                                        e.preventDefault();
                                        window.addEventListener('mousemove', onResizeMove);
                                        window.addEventListener('mouseup', onResizeEnd);
                                    } else {
                                        // Check drag
                                        activeBox = getActiveBox(mouseX, mouseY);
                                        if (activeBox) {
                                            isDragging = true;
                                            dragOffsetX = mouseX - activeBox.startX;
                                            dragOffsetY = mouseY - activeBox.startY;
                                            e.preventDefault();
                                            window.addEventListener('mousemove', onDragMove);
                                            window.addEventListener('mouseup', onDragEnd);
                                        }
                                    }
                                };

                                function onDragMove(e) {
                                    if (!isDragging || !activeBox) return;

                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var boxW = activeBox.endX - activeBox.startX;
                                    var boxH = activeBox.endY - activeBox.startY;
                                    var newStartX = mouseX - dragOffsetX;
                                    var newStartY = mouseY - dragOffsetY;

                                    newStartX = Math.max(0, Math.min(markCanvas.width - boxW, newStartX));
                                    newStartY = Math.max(0, Math.min(markCanvas.height - boxH, newStartY));

                                    activeBox.startX = newStartX;
                                    activeBox.startY = newStartY;
                                    activeBox.endX = newStartX + boxW;
                                    activeBox.endY = newStartY + boxH;

                                    if (activeBox.type === 'text') {
                                        $('#positionX').val(newStartX);
                                        $('#positionY').val(newStartY);
                                    }

                                    redrawSignatureBoxes();
                                }

                                function onResizeMove(e) {
                                    if (!isResizing || !activeBox) return;

                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var minW = 40, minH = 30;
                                    var newEndX = Math.max(activeBox.startX + minW, mouseX);
                                    var newEndY = Math.max(activeBox.startY + minH, mouseY);

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
                            };

                            var markCanvas = document.getElementById('mark-layer');
                            markCanvas.addEventListener('click', markEventListener);

                            // Enhanced signature function for insert page with drag and resize
                            markEventListenerInsert = function (e) {
                                var markCanvas = document.getElementById('mark-layer-insert');
                                var markCtx = markCanvas.getContext('2d');
                                var rect = markCanvas.getBoundingClientRect();

                                // Default position
                                var defaultTextWidth = 213;
                                var defaultTextHeight = 60;
                                var defaultImageWidth = 240;
                                var defaultImageHeight = 130;

                                var startX = (markCanvas.width - defaultTextWidth) / 2;
                                var startY = (markCanvas.height - (defaultTextHeight + defaultImageHeight + 20)) / 2;

                                // Create two separate boxes
                                var textBox = {
                                    startX: startX,
                                    startY: startY,
                                    endX: startX + defaultTextWidth,
                                    endY: startY + defaultTextHeight,
                                    type: 'text'
                                };

                                var imageBox = {
                                    startX: startX - 13,
                                    startY: startY + defaultTextHeight + 20,
                                    endX: startX + defaultImageWidth - 13,
                                    endY: startY + defaultTextHeight + 20 + defaultImageHeight,
                                    type: 'image'
                                };

                                signatureCoordinates = {
                                    textBox: textBox,
                                    imageBox: imageBox
                                };

                                $('#positionX').val(startX);
                                $('#positionY').val(startY);
                                $('#positionPages').val(2);

                                // Draw initial boxes
                                redrawSignatureBoxesInsert();

                                // Variables for drag and resize
                                var isDragging = false;
                                var isResizing = false;
                                var activeBox = null;
                                var dragOffsetX = 0;
                                var dragOffsetY = 0;
                                var resizeHandleSize = 16;

                                function redrawSignatureBoxesInsert() {
                                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

                                    var text = $('#modal-text').val();
                                    var lineBreakCount = countLineBreaks(text);
                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();

                                    // Draw text box
                                    var textBox = signatureCoordinates.textBox;
                                    markCtx.save();
                                    markCtx.strokeStyle = 'blue';
                                    markCtx.lineWidth = 1;
                                    markCtx.strokeRect(textBox.startX, textBox.startY,
                                        textBox.endX - textBox.startX, textBox.endY - textBox.startY);

                                    // Draw resize handle for text box
                                    markCtx.fillStyle = '#fff';
                                    markCtx.strokeStyle = '#007bff';
                                    markCtx.lineWidth = 2;
                                    markCtx.fillRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.strokeRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize,
                                        resizeHandleSize, resizeHandleSize);
                                    markCtx.restore();

                                    // Draw text content
                                    var textScale = Math.min(
                                        (textBox.endX - textBox.startX) / 213,
                                        (textBox.endY - textBox.startY) / 60
                                    );
                                    textScale = Math.max(0.5, Math.min(2.5, textScale));

                                    drawTextHeaderSignatureInsert((15 * textScale).toFixed(1) + 'px Sarabun',
                                        (textBox.startX + textBox.endX) / 2, textBox.startY + 20 * textScale, text);

                                    var i = 0;
                                    var checkbox_text = '';
                                    var plus_y = 20;

                                    checkedValues.forEach(element => {
                                        if (element != 4) {
                                            switch (element) {
                                                case '1':
                                                    checkbox_text = `({{$users->fullname}})`;
                                                    break;
                                                case '2':
                                                    checkbox_text = `{{$permission_data->permission_name}}`;
                                                    break;
                                                case '3':
                                                    checkbox_text = `{{convertDateToThai(date("Y-m-d"))}}`;
                                                    break;
                                            }
                                            drawTextHeaderSignatureInsert((15 * textScale).toFixed(1) + 'px Sarabun',
                                                (textBox.startX + textBox.endX) / 2,
                                                textBox.startY + (plus_y + 20) * textScale + (20 * i * textScale),
                                                checkbox_text);
                                            i++;
                                        }
                                    });

                                    // Draw image box if checkbox 4 is selected
                                    var hasImage = checkedValues.includes('4');
                                    if (hasImage) {
                                        var imageBox = signatureCoordinates.imageBox;
                                        markCtx.save();
                                        markCtx.strokeStyle = 'green';
                                        markCtx.lineWidth = 1;
                                        markCtx.strokeRect(imageBox.startX, imageBox.startY,
                                            imageBox.endX - imageBox.startX, imageBox.endY - imageBox.startY);

                                        // Draw resize handle for image box
                                        markCtx.fillStyle = '#fff';
                                        markCtx.strokeStyle = '#28a745';
                                        markCtx.lineWidth = 2;
                                        markCtx.fillRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize,
                                            resizeHandleSize, resizeHandleSize);
                                        markCtx.strokeRect(imageBox.endX - resizeHandleSize, imageBox.endY - resizeHandleSize,
                                            resizeHandleSize, resizeHandleSize);
                                        markCtx.restore();

                                        // Draw signature image
                                        var img = new Image();
                                        img.src = signature;
                                        img.onload = function () {
                                            var imgWidth = imageBox.endX - imageBox.startX;
                                            var imgHeight = imageBox.endY - imageBox.startY;
                                            markCtx.drawImage(img, imageBox.startX, imageBox.startY, imgWidth, imgHeight);

                                            imgData = {
                                                x: imageBox.startX,
                                                y: imageBox.startY,
                                                width: imgWidth,
                                                height: imgHeight
                                            };
                                        }
                                    }
                                }

                                // Helper functions
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
                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    if (hasImage && isInBox(mouseX, mouseY, signatureCoordinates.imageBox)) {
                                        return signatureCoordinates.imageBox;
                                    } else if (isInBox(mouseX, mouseY, signatureCoordinates.textBox)) {
                                        return signatureCoordinates.textBox;
                                    }
                                    return null;
                                }

                                // Mouse events
                                markCanvas.addEventListener('mousemove', function (e) {
                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    if (isOnResizeHandle(mouseX, mouseY, signatureCoordinates.textBox) ||
                                        (hasImage && isOnResizeHandle(mouseX, mouseY, signatureCoordinates.imageBox))) {
                                        markCanvas.style.cursor = 'se-resize';
                                    } else if (getActiveBox(mouseX, mouseY)) {
                                        markCanvas.style.cursor = 'move';
                                    } else {
                                        markCanvas.style.cursor = 'default';
                                    }
                                });

                                markCanvas.onmousedown = function (e) {
                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var checkedValues = $('input[type="checkbox"]:checked').map(function () {
                                        return $(this).val();
                                    }).get();
                                    var hasImage = checkedValues.includes('4');

                                    // Check resize handles first
                                    if (isOnResizeHandle(mouseX, mouseY, signatureCoordinates.textBox)) {
                                        isResizing = true;
                                        activeBox = signatureCoordinates.textBox;
                                        e.preventDefault();
                                        window.addEventListener('mousemove', onResizeMoveInsert);
                                        window.addEventListener('mouseup', onResizeEndInsert);
                                    } else if (hasImage && isOnResizeHandle(mouseX, mouseY, signatureCoordinates.imageBox)) {
                                        isResizing = true;
                                        activeBox = signatureCoordinates.imageBox;
                                        e.preventDefault();
                                        window.addEventListener('mousemove', onResizeMoveInsert);
                                        window.addEventListener('mouseup', onResizeEndInsert);
                                    } else {
                                        // Check drag
                                        activeBox = getActiveBox(mouseX, mouseY);
                                        if (activeBox) {
                                            isDragging = true;
                                            dragOffsetX = mouseX - activeBox.startX;
                                            dragOffsetY = mouseY - activeBox.startY;
                                            e.preventDefault();
                                            window.addEventListener('mousemove', onDragMoveInsert);
                                            window.addEventListener('mouseup', onDragEndInsert);
                                        }
                                    }
                                };

                                function onDragMoveInsert(e) {
                                    if (!isDragging || !activeBox) return;

                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var boxW = activeBox.endX - activeBox.startX;
                                    var boxH = activeBox.endY - activeBox.startY;
                                    var newStartX = mouseX - dragOffsetX;
                                    var newStartY = mouseY - dragOffsetY;

                                    newStartX = Math.max(0, Math.min(markCanvas.width - boxW, newStartX));
                                    newStartY = Math.max(0, Math.min(markCanvas.height - boxH, newStartY));

                                    activeBox.startX = newStartX;
                                    activeBox.startY = newStartY;
                                    activeBox.endX = newStartX + boxW;
                                    activeBox.endY = newStartY + boxH;

                                    if (activeBox.type === 'text') {
                                        $('#positionX').val(newStartX);
                                        $('#positionY').val(newStartY);
                                    }

                                    redrawSignatureBoxesInsert();
                                }

                                function onResizeMoveInsert(e) {
                                    if (!isResizing || !activeBox) return;

                                    var rect = markCanvas.getBoundingClientRect();
                                    var mouseX = e.clientX - rect.left;
                                    var mouseY = e.clientY - rect.top;

                                    var minW = 40, minH = 30;
                                    var newEndX = Math.max(activeBox.startX + minW, mouseX);
                                    var newEndY = Math.max(activeBox.startY + minH, mouseY);

                                    newEndX = Math.min(markCanvas.width, newEndX);
                                    newEndY = Math.min(markCanvas.height, newEndY);

                                    activeBox.endX = newEndX;
                                    activeBox.endY = newEndY;

                                    redrawSignatureBoxesInsert();
                                }

                                function onDragEndInsert(e) {
                                    isDragging = false;
                                    activeBox = null;
                                    window.removeEventListener('mousemove', onDragMoveInsert);
                                    window.removeEventListener('mouseup', onDragEndInsert);
                                }

                                function onResizeEndInsert(e) {
                                    isResizing = false;
                                    activeBox = null;
                                    window.removeEventListener('mousemove', onResizeMoveInsert);
                                    window.removeEventListener('mouseup', onResizeEndInsert);
                                }
                            };

                            var markCanvasInsert = document.getElementById('mark-layer-insert');
                            markCanvasInsert.addEventListener('click', markEventListenerInsert);
                        } else {
                            $('#exampleModal').modal('hide');
                            Swal.fire("", response.message, "error");
                        }
                    }
                });
            });

            function countLineBreaks(text) {
                var lines = text.split('\n');
                return lines.length - 1;
            }

            // Updated drawMark function with resize handle
            function drawMark(startX, startY, endX, endY) {
                //เคลียร์กรอบเดิมของหน้าเกษียณพับครึ่ง
                var markCanvas = document.getElementById('mark-layer-insert');
                var markCtx = markCanvas.getContext('2d');
                markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

                var markCanvas = document.getElementById('mark-layer');
                var markCtx = markCanvas.getContext('2d');
                markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

                markCtx.beginPath();
                markCtx.rect(startX, startY, endX - startX, endY - startY);
                markCtx.lineWidth = 1;
                markCtx.strokeStyle = 'blue';
                markCtx.stroke();

                // Draw resize handle (bottom-right)
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
                        img.onload = function () {
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


            function drawTextHeaderClassic(type, startX, startY, text) {
                var markCanvas = document.getElementById('mark-layer');
                var markCtx = markCanvas.getContext('2d');

                markCtx.font = type;
                markCtx.fillStyle = "blue";
                markCtx.fillText(text, startX, startY);
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

                markCanvas.addEventListener('click', function (event) {
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
                        img.onload = function () {
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

            function drawTextHeaderClassicInsert(type, startX, startY, text) {
                var markCanvas = document.getElementById('mark-layer-insert');
                var markCtx = markCanvas.getContext('2d');

                markCtx.font = type;
                markCtx.fillStyle = "blue";
                markCtx.fillText(text, startX, startY);
            }

            function drawTextHeaderSignatureInsert(type, startX, startY, text) {
                var markCanvas = document.getElementById('mark-layer-insert');
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
        }

        let markEventListener = null;
        let markEventListenerInsert = null;

        function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
            $('.btn-default').hide();
            document.getElementById('add-stamp').disabled = false;
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
            document.getElementById('add-stamp').disabled = true;
            if (status == 3) {
                $('#insert-pages').show();
                $('#add-stamp').show();
                $('#save-stamp').show();
            }
            if (status == 3.5) {
                if (position_id != 1) {
                    document.getElementById('send-signature').disabled = false;
                    $('#send-signature').show();
                    $('#signature-save').show();
                    $('#insert-pages').show();
                } else {
                    $('#sendTo').show();
                }
            }
            if (status == 4) {
                if (!permission.includes('3,3.5,4,5')) {
                    document.getElementById('send-signature').disabled = false;
                    $('#send-signature').show();
                    $('#signature-save').show();
                } else {
                    $('#send-to').show();
                    $('#send-save').show();
                }
            }
            if (status == 5) {
                $('#send-to').show();
                $('#send-save').show();
            }
            if (status == 14) {
                document.getElementById('directory-save').disabled = false;
                $('#directory-save').show();
            }
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

        selectPageTable.addEventListener('change', function () {
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
            document.getElementById('add-stamp').disabled = false;
            document.getElementById('save-stamp').disabled = true;
            document.getElementById('send-save').disabled = true;
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
                success: function (response) {
                    if (response.status == true) {
                        $('#box-card-item').empty();
                        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                        response.book.forEach(element => {
                            var color = 'info';
                            var text = '';
                            if (element.type != 1) {
                                var color = 'warning';
                            }
                            if (element.status == 14) {
                                text = '';
                                color = 'success';
                            }
                            $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ',' + "'" + element.type + "'" + ',' + "'" + element.is_number_stamp + "'" + ',' + "'" + element.inputBookregistNumber + "'" + ',' + "'" + element.position_id + "'" + ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + text + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                            $('#box-card-item').append($html);
                        });
                    }
                }
            });
        }

        $('#search_btn').click(function (e) {
            e.preventDefault();
            $('#id').val('');
            $('#positionX').val('');
            $('#positionY').val('');
            $('.btn-default').hide();
            $('#txt_label').text('');
            $('#users_id').val('');
            document.getElementById('add-stamp').disabled = false;
            document.getElementById('save-stamp').disabled = true;
            document.getElementById('send-save').disabled = true;
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
                success: function (response) {
                    if (response.status == true) {
                        $('#box-card-item').html('');
                        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                        pageNumTalbe = 1;
                        pageTotal = response.totalPages;
                        response.book.forEach(element => {
                            var color = 'info';
                            var text = '';
                            if (element.type != 1) {
                                color = 'warning';
                            }
                            if (element.status == 14) {
                                text = '';
                                color = 'success';
                            }
                            $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ',' + "'" + element.type + "'" + ',' + "'" + element.is_number_stamp + "'" + ',' + "'" + element.inputBookregistNumber + "'" + ',' + "'" + element.position_id + "'" + ')"><div class="card border-' + color + ' mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + text + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
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

        $('#save-stamp').click(function (e) {
            e.preventDefault();
            var id = $('#id').val();
            var positionX = $('#positionX').val();
            var positionY = $('#positionY').val();
            var positionPages = $('#positionPages').val();
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
                        // Calculate width and height of the stamp box
                        var boxW = markCoordinates.endX - markCoordinates.startX;
                        var boxH = markCoordinates.endY - markCoordinates.startY;
                        $.ajax({
                            type: "post",
                            url: "/book/admin_stamp",
                            data: {
                                id: id,
                                positionX: positionX,
                                positionY: positionY,
                                positionPages: positionPages,
                                pages: pages,
                                width: boxW,
                                height: boxH
                            },
                            dataType: "json",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (response) {
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

        $('#sendTo').click(function (e) {
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
                        url: "/book/send_to_adminParent",
                        data: {
                            id: id,
                            position_id: result.value
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function (response) {
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
        $('#send-to').click(function (e) {
            e.preventDefault();
            $.ajax({
                type: "post",
                url: "/book/checkbox_send",
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    Swal.fire({
                        title: 'แทงเรื่อง',
                        html: response,
                        allowOutsideClick: false,
                        focusConfirm: true,
                        confirmButtonText: 'ตกลง',
                        showCancelButton: true,
                        cancelButtonText: `ยกเลิก`,
                        preConfirm: () => {
                            var selectedCheckboxes = [];
                            var textCheckboxes = [];
                            $('input[name="flexCheckChecked[]"]:checked').each(function () {
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

        $('#send-save').click(function (e) {
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
                            status: 6
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function (response) {
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
        $('#signature-save').click(function (e) {
            e.preventDefault();
            var id = $('#id').val();
            var positionX = $('#positionX').val();
            var positionY = $('#positionY').val();
            var pages = $('#page-select').find(":selected").val();
            var positionPages = $('#positionPages').val();
            var text = $('#modal-text').val();
            var checkedValues = $('input[type="checkbox"]:checked').map(function () {
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
                        // Prepare signature data with box coordinates
                        var signatureData = {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            positionPages: positionPages,
                            pages: pages,
                            text: text,
                            checkedValues: checkedValues
                        };

                        // Add box coordinates if they exist
                        if (signatureCoordinates) {
                            signatureData.textBox = {
                                startX: signatureCoordinates.textBox.startX,
                                startY: signatureCoordinates.textBox.startY,
                                width: signatureCoordinates.textBox.endX - signatureCoordinates.textBox.startX,
                                height: signatureCoordinates.textBox.endY - signatureCoordinates.textBox.startY
                            };

                            if (checkedValues.includes('4')) {
                                signatureData.imageBox = {
                                    startX: signatureCoordinates.imageBox.startX,
                                    startY: signatureCoordinates.imageBox.startY,
                                    width: signatureCoordinates.imageBox.endX - signatureCoordinates.imageBox.startX,
                                    height: signatureCoordinates.imageBox.endY - signatureCoordinates.imageBox.startY
                                };
                            }
                        }

                        $.ajax({
                            type: "post",
                            url: "/book/signature_stamp",
                            data: signatureData,
                            dataType: "json",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (response) {
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
                cancelBtn.onclick = function () {
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

        document.addEventListener('DOMContentLoaded', function () {
            hideCancelStampBtn();
        });

        const _oldRemoveMarkListener = removeMarkListener;
        removeMarkListener = function () {
            hideCancelStampBtn();
            _oldRemoveMarkListener.apply(this, arguments);
        };

        $(document).ready(function () {
            $('#send-signature').click(function (e) {
                e.preventDefault();
            });
            $('#insert-pages').click(function (e) {
                e.preventDefault();
                $('#insert_tab').show();
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
        $('#directory-save').click(function (e) {
            e.preventDefault();
            Swal.fire({
                title: "",
                text: "ท่านต้องการจัดเก็บไฟล์นี้ใช่หรือไม่",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "ยกเลิก",
                confirmButtonText: "จัดเก็บ"
            }).then((result) => {
                if (result.isConfirmed) {
                    var id = $('#id').val();
                    $.ajax({
                        type: "post",
                        url: "/book/directory_save",
                        data: {
                            id: id,
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.status) {
                                Swal.fire("", "จัดเก็บเรียบร้อยแล้ว", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                Swal.fire("", "จัดเก็บไม่สำเร็จ", "error");
                            }
                        }
                    });
                }
            });
        });
    </script>
@endsection