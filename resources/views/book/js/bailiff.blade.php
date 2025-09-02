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

    // helper กัน null
    function getCanvasOrNull(id) {
        var c = document.getElementById(id);
        if (!c) return null;
        var ctx = c.getContext ? c.getContext('2d') : null;
        if (!ctx) return null;
        return { canvas: c, ctx: ctx };
    }

    // preload ลายเซ็น
    var signatureImg = new Image();
    var signatureImgLoaded = false;
    signatureImg.onload = function(){ signatureImgLoaded = true; };
    signatureImg.src = signature;

    // เก็บพิกัดกล่อง
    var signatureCoordinates = null;

    function pdf(url) {
        const thisLoad = ++pdfLoadSeq;
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCtx = pdfCanvas.getContext('2d'),
            markCanvas = document.getElementById('mark-layer'),
            markCtx = markCanvas.getContext('2d'),
            selectPage = document.getElementById('page-select'),
            additionalContainer = document.getElementById('pdf-additional');

        // Replace select to clear options and previous listeners
        if (selectPage) {
            const cleanSelect = selectPage.cloneNode(false);
            selectPage.parentNode.replaceChild(cleanSelect, selectPage);
            selectPage = cleanSelect;
        }
        if (additionalContainer) additionalContainer.innerHTML = '';

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
                renderTask.promise.then(function(){
                    pageRendering = false;
                    if (pageNumPending !== null) { renderPage(pageNumPending); pageNumPending = null; }
                });
            });
            if (selectPage) selectPage.value = num;
        }

        function queueRenderPage(num){ pageRendering ? pageNumPending = num : renderPage(num); }
        function onNextPage(){ if (pageNum < pdfDoc.numPages){ pageNum++; queueRenderPage(pageNum);} }
        function onPrevPage(){ if (pageNum > 1){ pageNum--; queueRenderPage(pageNum);} }

        if (selectPage) {
            selectPage.addEventListener('change', function(){
                let n = parseInt(this.value);
                if (n && n>=1 && n<=pdfDoc.numPages){ pageNum = n; queueRenderPage(n); }
            });
        }

        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            if (thisLoad !== pdfLoadSeq) return;
            pdfDoc = pdfDoc_;
            if (selectPage) {
                for (let i=1;i<=pdfDoc.numPages;i++){
                    let op = document.createElement('option');
                    op.value=i; op.textContent=i; selectPage.appendChild(op);
                }
            }
            renderPage(pageNum);
            if (additionalContainer) {
                for (let i=2;i<=pdfDoc.numPages;i++){
                    pdfDoc.getPage(i).then(function(page){
                        const viewport = page.getViewport({ scale: scale });
                        const wrapper = document.createElement('div');
                        wrapper.style.position = 'relative';
                        wrapper.style.margin = '20px auto 0';
                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        wrapper.appendChild(canvas);
                        const ctx = canvas.getContext('2d');
                        page.render({ canvasContext: ctx, viewport: viewport });
                        additionalContainer.appendChild(wrapper);
                    });
                }
            }
            var btnSig = document.getElementById('manager-sinature');
            if (btnSig) btnSig.disabled = false;
        });

        var btnNext = document.getElementById('next');
        var btnPrev = document.getElementById('prev');
        if (btnNext) btnNext.onclick = onNextPage;
        if (btnPrev) btnPrev.onclick = onPrevPage;

        function drawTextHeaderSignature(type, cx, y, text){
            var main = getCanvasOrNull('mark-layer'); if (!main) return;
            main.ctx.font = type; main.ctx.fillStyle = "blue";
            var lines = String(text||'').split('\n'), lh = 20;
            for (var i=0;i<lines.length;i++){
                var tw = main.ctx.measureText(lines[i]).width;
                main.ctx.fillText(lines[i], cx - (tw/2), y + (i*lh));
            }
        }

        $('#modalForm').on('submit', function(e){
            e.preventDefault();
            var formData = new FormData(this);
            $('#exampleModal').modal('hide');
            Swal.showLoading();

            $.ajax({
                type: "post",
                url: "/book/confirm_signature",
                data: formData,
                dataType: "json",
                contentType: false, processData: false,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(response){
                    if (!response.status){
                        $('#exampleModal').modal('hide');
                        Swal.fire("", response.message || "เกิดข้อผิดพลาด", "error");
                        return;
                    }

                    setTimeout(()=>{ swal.close(); }, 500);
                    resetMarking(); removeMarkListener();
                    document.getElementById('manager-save').disabled = false;

                    var btnSig = document.getElementById('manager-sinature');
                    if (btnSig) btnSig.disabled = true;

                    var main = getCanvasOrNull('mark-layer'); if (!main) return;
                    var markCanvas = main.canvas, markCtx = main.ctx;

                    // ===== ขนาดกล่องเริ่มต้น =====
                    var defaultTextWidth = 220, defaultTextHeight = 40;
                    var defaultImageWidth = 240, defaultImageHeight = 130;
                    var defaultBottomBoxHeight = 80;
                    var gap = 10;

                    // ===== จัดเรียง "บน → ลายเซ็น → ล่าง" =====
                    var startX = (markCanvas.width - defaultTextWidth) / 2;
                    var totalHeight = defaultTextHeight + gap + defaultImageHeight + gap + defaultBottomBoxHeight + 20;
                    var startY = (markCanvas.height - totalHeight) / 2;

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
                            startY: startY + defaultTextHeight + gap, // ต่อจากกล่องบน
                            endX: (startX - 13) + defaultImageWidth,
                            endY: startY + defaultTextHeight + gap + defaultImageHeight,
                            type: 'image'
                        },
                        bottomBox: {
                            startX: startX,
                            startY: startY + defaultTextHeight + gap + defaultImageHeight + gap, // ต่อจากรูป
                            endX: startX + defaultTextWidth,
                            endY: startY + defaultTextHeight + gap + defaultImageHeight + gap + defaultBottomBoxHeight,
                            type: 'bottom'
                        }
                    };

                    $('#positionX').val(startX);
                    $('#positionY').val(startY);
                    $('#positionPages').val(1);

                    var isDragging=false, isResizing=false, activeBox=null;
                    var dragOffsetX=0, dragOffsetY=0, resizeHandleSize=16;

                    function isOnResizeHandle(x,y,box){
                        return (x>=box.endX-resizeHandleSize && x<=box.endX && y>=box.endY-resizeHandleSize && y<=box.endY);
                    }
                    function isInBox(x,y,box){
                        return (x>=box.startX && x<=box.endX && y>=box.startY && y<=box.endY);
                    }
                    function getChecked(){ return $('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get(); }

                    function redrawSignatureBoxes(){
                        markCtx.clearRect(0,0,markCanvas.width,markCanvas.height);
                        var checkedValues = getChecked();

                        // ===== 1) วาดกล่องข้อความบน =====
                        var textBox = signatureCoordinates.textBox;
                        markCtx.save();
                        markCtx.strokeStyle = 'blue'; markCtx.lineWidth = 0.5;
                        markCtx.strokeRect(textBox.startX, textBox.startY, textBox.endX-textBox.startX, textBox.endY-textBox.startY);
                        // handle
                        markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#007bff'; markCtx.lineWidth = 2;
                        markCtx.fillRect(textBox.endX-16, textBox.endY-16, 16, 16);
                        markCtx.strokeRect(textBox.endX-16, textBox.endY-16, 16, 16);
                        markCtx.restore();

                        var textScale = Math.min((textBox.endX-textBox.startX)/220, (textBox.endY-textBox.startY)/40);
                        textScale = Math.max(0.5, Math.min(2.5, textScale));
                        var topText = $('#modal-text').val();
                        drawTextHeaderSignature((15*textScale).toFixed(1)+'px Sarabun',
                            (textBox.startX+textBox.endX)/2,
                            textBox.startY + 25*textScale,
                            topText);

                        // ===== 2) วาดกรอบ/รูป "ลายเซ็น" กล่องที่สอง =====
                        if (false && checkedValues.includes('4') && signatureImgLoaded) {
                            var imgBox = signatureCoordinates.imageBox;
                            var w = Math.max(40, imgBox.endX - imgBox.startX);
                            var h = Math.max(30, imgBox.endY - imgBox.startY);

                            markCtx.save();
                            markCtx.strokeStyle = 'green'; markCtx.lineWidth = 0.5;
                            markCtx.strokeRect(imgBox.startX, imgBox.startY, w, h);
                            markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#28a745'; markCtx.lineWidth = 2;
                            markCtx.fillRect(imgBox.endX-16, imgBox.endY-16, 16, 16);
                            markCtx.strokeRect(imgBox.endX-16, imgBox.endY-16, 16, 16);
                            markCtx.restore();

                            markCtx.drawImage(signatureImg, imgBox.startX, imgBox.startY, w, h);
                        }

                        // ===== 3) วาดกล่องข้อความล่าง =====
                        var bottomBox = signatureCoordinates.bottomBox;
                        markCtx.save();
                        markCtx.strokeStyle = 'purple'; markCtx.lineWidth = 0.5;
                        markCtx.strokeRect(bottomBox.startX, bottomBox.startY, bottomBox.endX-bottomBox.startX, bottomBox.endY-bottomBox.startY);
                        // handle
                        markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#6f42c1'; markCtx.lineWidth = 2;
                        markCtx.fillRect(bottomBox.endX-16, bottomBox.endY-16, 16, 16);
                        markCtx.strokeRect(bottomBox.endX-16, bottomBox.endY-16, 16, 16);
                        markCtx.restore();

                        var bottomScale = Math.min((bottomBox.endX-bottomBox.startX)/220, (bottomBox.endY-bottomBox.startY)/80);
                        bottomScale = Math.max(0.5, Math.min(2.5, bottomScale));
                        // Draw signature inside bottom box then details below
                        var baseY = bottomBox.startY + 25*bottomScale;
                        if (checkedValues.includes('4') && signatureImgLoaded) {
                            var innerPad = 8;
                            var contentW = (bottomBox.endX - bottomBox.startX) - innerPad*2;
                            var contentH = (bottomBox.endY - bottomBox.startY) - innerPad*2;
                            var ar = 240/130;
                            var maxImgW = contentW;
                            var maxImgH = Math.max(30, contentH * 0.55);
                            var imgW = maxImgW;
                            var imgH = imgW / ar;
                            if (imgH > maxImgH) { imgH = maxImgH; imgW = imgH * ar; }
                            var imgX = bottomBox.startX + ((bottomBox.endX - bottomBox.startX) - imgW)/2;
                            var imgY = bottomBox.startY + innerPad;
                            markCtx.drawImage(signatureImg, imgX, imgY, imgW, imgH);
                            imgData = { x: imgX, y: imgY, width: imgW, height: imgH };
                            baseY = imgY + imgH + 10;
                        }
                        var i = 0;
                        checkedValues.forEach(function (element) {
                            if (element == '4') return; // ข้ามลายเซ็น
                            var line = '';
                            switch (element) {
                                case '1': line = `({{$users->fullname}})`; break;
                                case '2': line = `{{$permission_data->permission_name}}`; break;
                                case '3': line = `{{convertDateToThai(date("Y-m-d"))}}`; break;
                            }
                            drawTextHeaderSignature((15*bottomScale).toFixed(1)+'px Sarabun',
                                (bottomBox.startX+bottomBox.endX)/2,
                                baseY + (20*i*bottomScale),
                                line);
                            i++;
                        });
                    }

                    function getActiveBox(x,y){
                        var checkedValues = getChecked();
                        var hasImage = checkedValues.includes('4');
                        if (hasImage && isInBox(x,y, signatureCoordinates.imageBox)) return signatureCoordinates.imageBox;
                        else if (isInBox(x,y, signatureCoordinates.textBox)) return signatureCoordinates.textBox;
                        else if (isInBox(x,y, signatureCoordinates.bottomBox)) return signatureCoordinates.bottomBox;
                        return null; // <<< วงเล็บครบ
                    }

                    // cursor
                    markCanvas.addEventListener('mousemove', function(e){
                        var r = markCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
                        var checkedValues = getChecked(), hasImage = checkedValues.includes('4');
                        if (isOnResizeHandle(x,y, signatureCoordinates.textBox) ||
                            (hasImage && isOnResizeHandle(x,y, signatureCoordinates.imageBox)) ||
                            isOnResizeHandle(x,y, signatureCoordinates.bottomBox)) {
                            markCanvas.style.cursor = 'se-resize';
                        } else if (getActiveBox(x,y)) {
                            markCanvas.style.cursor = 'move';
                        } else {
                            markCanvas.style.cursor = 'default';
                        }
                    });

                    markCanvas.onmousedown = function(e){
                        var r = markCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
                        var checkedValues = getChecked(), hasImage = checkedValues.includes('4');

                        if (isOnResizeHandle(x,y, signatureCoordinates.textBox)) {
                            isResizing = true; activeBox = signatureCoordinates.textBox;
                            e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                        } else if (hasImage && isOnResizeHandle(x,y, signatureCoordinates.imageBox)) {
                            isResizing = true; activeBox = signatureCoordinates.imageBox;
                            e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                        } else if (isOnResizeHandle(x,y, signatureCoordinates.bottomBox)) {
                            isResizing = true; activeBox = signatureCoordinates.bottomBox;
                            e.preventDefault(); window.addEventListener('mousemove', onResizeMove); window.addEventListener('mouseup', onResizeEnd);
                        } else {
                            activeBox = getActiveBox(x,y);
                            if (activeBox){
                                isDragging = true; dragOffsetX = x - activeBox.startX; dragOffsetY = y - activeBox.startY;
                                e.preventDefault(); window.addEventListener('mousemove', onDragMove); window.addEventListener('mouseup', onDragEnd);
                            }
                        }
                    };

                    function onDragMove(e){
                        if (!isDragging || !activeBox) return;
                        var r = markCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
                        var w = activeBox.endX - activeBox.startX, h = activeBox.endY - activeBox.startY;
                        var nsx = Math.max(0, Math.min(markCanvas.width - w, x - dragOffsetX));
                        var nsy = Math.max(0, Math.min(markCanvas.height - h, y - dragOffsetY));
                        activeBox.startX = nsx; activeBox.startY = nsy; activeBox.endX = nsx + w; activeBox.endY = nsy + h;
                        if (activeBox.type === 'text'){ $('#positionX').val(nsx); $('#positionY').val(nsy); }
                        redrawSignatureBoxes();
                    }
                    function onDragEnd(){ isDragging=false; activeBox=null; window.removeEventListener('mousemove', onDragMove); window.removeEventListener('mouseup', onDragEnd); }
                    function onResizeMove(e){
                        if (!isResizing || !activeBox) return;
                        var r = markCanvas.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
                        var minW=40, minH=30;
                        activeBox.endX = Math.min(markCanvas.width, Math.max(activeBox.startX + minW, x));
                        activeBox.endY = Math.min(markCanvas.height, Math.max(activeBox.startY + minH, y));
                        redrawSignatureBoxes();
                    }
                    function onResizeEnd(){ isResizing=false; activeBox=null; window.removeEventListener('mousemove', onResizeMove); window.removeEventListener('mouseup', onResizeEnd); }

                    // วาดครั้งแรก
                    redrawSignatureBoxes();
                }
            });
        });
    }

    let markEventListener = null;
    let markEventListenerInsert = null;

    function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
        $('.btn-default').hide();
        var rejectBtn = document.getElementById('reject-book'); if (rejectBtn) rejectBtn.disabled = true;
        var btnSig = document.getElementById('manager-sinature'); if (btnSig) btnSig.disabled = false;
        var saveStamp = document.getElementById('save-stamp'); if (saveStamp) saveStamp.disabled = true;
        var sendSave = document.getElementById('send-save'); if (sendSave) sendSave.disabled = true;

        $('#div-canvas').html(
            '<div style="position: relative;">' +
                '<canvas id="pdf-render"></canvas>' +
                '<canvas id="mark-layer" style="position:absolute;left:0;top:0;"></canvas>' +
            '</div>' +
            '<div id="pdf-additional"></div>'
        );
        pdf(url);

        $('#id').val(id);
        $('#position_id').val(position_id);
        $('#positionX').val(''); $('#positionY').val('');
        $('#txt_label').text(''); $('#users_id').val('');
        document.getElementById('manager-save').disabled = true;

        if (status == STATUS.BAILIFF_SIGNATURE) { $('#manager-sinature').show(); $('#manager-save').show(); $('#insert-pages').show(); }
        if (status == STATUS.BAILIFF_SENT) { $('#manager-send').show(); $('#send-save').show(); }
        $.get('/book/created_position/' + id, function(res){
            if (status >= STATUS.ADMIN_PROCESS && status < STATUS.ARCHIVED && position_id != res.position_id) {
                var rb = document.getElementById('reject-book'); if (rb){ rb.disabled = false; $('#reject-book').show(); }
            }
        });

        resetMarking(); removeMarkListener();
    }

    function removeMarkListener(){
        var markCanvas = document.getElementById('mark-layer');
        var markCanvasInsert = document.getElementById('mark-layer-insert');
        if (markEventListener && markCanvas){ markCanvas.removeEventListener('click', markEventListener); markEventListener=null; }
        if (markEventListenerInsert && markCanvasInsert){ markCanvasInsert.removeEventListener('click', markEventListenerInsert); markEventListenerInsert=null; }
    }

    function resetMarking(){
        var main = getCanvasOrNull('mark-layer'); if (main) main.ctx.clearRect(0,0,main.canvas.width, main.canvas.height);
        var ins = getCanvasOrNull('mark-layer-insert'); if (ins) ins.ctx.clearRect(0,0,ins.canvas.width, ins.canvas.height);
    }

    // ========== Print helpers for list cards ==========
    function printPdf(url){
        try{
            var w=window.open(url,'_blank'); if(!w) return; var fired=false; var doPrint=function(){ if(fired) return; fired=true; try{ w.focus(); w.print(); }catch(e){} };
            w.addEventListener && w.addEventListener('load', doPrint);
            setTimeout(doPrint, 1500);
        }catch(e){}
    }
    function addPrintButtons(){
        try{
            $('#box-card-item .card .card-body .row .col-3.fw-bold').each(function(){
                if($(this).find('button._printBtn').length) return;
                var parent=$(this).closest('a'); var oc=parent.attr('onclick')||''; var m=oc.match(/openPdf\('([^']+)'/); var pdf=m?m[1]:null; if(!pdf) return;
                var btn=$('<button type="button" class="btn btn-sm btn-outline-secondary ms-2 _printBtn"><i class="fa fa-print"></i></button>');
                btn.on('click', function(ev){ ev.stopPropagation(); printPdf(pdf); });
                $(this).append(btn);
            });
        }catch(e){}
    }
    setInterval(addPrintButtons, 1200);

    // ====== ส่วนตารางรายการ ======
    if (selectPageTable) {
        selectPageTable.addEventListener('change', function(){ ajaxTable(parseInt(this.value)); });
    }

    function onNextPageTable(){ if (pageNumTalbe < pageTotal){ pageNumTalbe++; if (selectPageTable) selectPageTable.value = pageNumTalbe; ajaxTable(pageNumTalbe);} }
    function onPrevPageTable(){ if (pageNumTalbe > 1){ pageNumTalbe--; if (selectPageTable) selectPageTable.value = pageNumTalbe; ajaxTable(pageNumTalbe);} }

    var btnNextPage = document.getElementById('nextPage');
    var btnPrevPage = document.getElementById('prevPage');
    if (btnNextPage) btnNextPage.addEventListener('click', onNextPageTable);
    if (btnPrevPage) btnPrevPage.addEventListener('click', onPrevPageTable);

    function ajaxTable(pages){
        $('#id,#positionX,#positionY,#users_id').val('');
        $('#txt_label').text('');
        var btnSig = document.getElementById('manager-sinature'); if (btnSig) btnSig.disabled = false;
        document.getElementById('manager-save').disabled = true;

        $.ajax({
            type:"post", url:"/book/dataList",
            data:{ pages: pages },
            headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            dataType:"json",
            success:function(response){
                if (!response.status) return;
                $('#box-card-item').empty();
                $('#div-canvas').html(
                    '<div style="position: relative;">' +
                        '<canvas id="pdf-render"></canvas>' +
                        '<canvas id="mark-layer" style="position:absolute;left:0;top:0;"></canvas>' +
                    '</div>' +
                    '<div id="pdf-additional"></div>'
                );
                response.book.forEach(el=>{
                    var color = (el.type!=1)?'warning':'info';
                    var html = '<a href="javascript:void(0)" onclick="openPdf('+"'"+el.url+"'"+','+"'"+el.id+"'"+','+"'"+el.status+"'"+','+"'"+el.type+"'"+','+"'"+el.is_number_stamp+"'"+','+"'"+el.inputBookregistNumber+"'"+','+"'"+el.position_id+"'"+')">'+
                        '<div class="card border-'+color+' mb-2">'+
                            '<div class="card-header text-dark fw-bold">'+el.inputSubject+'</div>'+
                            '<div class="card-body text-dark"><div class="row">'+
                                '<div class="col-9">'+el.selectBookFrom+'</div>'+
                                '<div class="col-3 fw-bold">'+el.showTime+' น.</div>'+
                            '</div></div></div></a>';
                    $('#box-card-item').append(html);
                });
            }
        });
    }

    $('#search_btn').click(function(e){
        e.preventDefault();
        $('#id,#positionX,#positionY,#users_id').val(''); $('.btn-default').hide(); $('#txt_label').text('');
        var btnSig = document.getElementById('manager-sinature'); if (btnSig) btnSig.disabled = false;
        document.getElementById('manager-save').disabled = true;

        $.ajax({
            type:"post", url:"/book/dataListSearch",
            data:{ pages:1, search: $('#inputSearch').val() },
            headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            dataType:"json",
            success:function(response){
                if (!response.status) return;
                $('#box-card-item').html('');
                $('#div-canvas').html(
                    '<div style="position: relative;">' +
                        '<canvas id="pdf-render"></canvas>' +
                        '<canvas id="mark-layer" style="position:absolute;left:0;top:0;"></canvas>' +
                    '</div>' +
                    '<div id="pdf-additional"></div>'
                );
                pageNumTalbe = 1; pageTotal = response.totalPages;
                response.book.forEach(el=>{
                    var color = (el.type!=1)?'warning':'info';
                    var html = '<a href="javascript:void(0)" onclick="openPdf('+"'"+el.url+"'"+','+"'"+el.id+"'"+','+"'"+el.status+"'"+','+"'"+el.type+"'"+','+"'"+el.is_number_stamp+"'"+','+"'"+el.inputBookregistNumber+"'"+','+"'"+el.position_id+"'"+')">'+
                        '<div class="card border-'+color+' mb-2">'+
                            '<div class="card-header text-dark fw-bold">'+el.inputSubject+'</div>'+
                            '<div class="card-body text-dark"><div class="row">'+
                                '<div class="col-9">'+el.selectBookFrom+'</div>'+
                                '<div class="col-3 fw-bold">'+el.showTime+' น.</div>'+
                            '</div></div></div></a>';
                    $('#box-card-item').append(html);
                });
                var sel = $("#page-select-card"); if (sel.length){ sel.empty(); for (let i=1;i<=pageTotal;i++) sel.append('<option value="'+i+'">'+i+'</option>'); }
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

    $('#manager-save').click(function(e){
        e.preventDefault();
        var id = $('#id').val(), position_id = $('#position_id').val();
        var positionPages = $('#positionPages').val();
        var pages = 1;
        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
        var textBox = signatureCoordinates ? signatureCoordinates.textBox : null;
        var imageBox = signatureCoordinates ? signatureCoordinates.imageBox : null;
        var bottomBox = signatureCoordinates ? signatureCoordinates.bottomBox : null;

        if (!id || !textBox){ return Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info"); }

        Swal.fire({ title:"ยืนยันการลงลายเซ็น", showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก", icon:'question' })
            .then((r)=>{
                if (!r.isConfirmed) return;
                var data = {
                    id:id, positionX:textBox.startX, positionY:textBox.startY,
                    pages:pages, positionPages:positionPages, status:9,
                    text:text, checkedValues:checkedValues,
                    width: textBox.endX - textBox.startX,
                    height: textBox.endY - textBox.startY,
                    position_id: position_id
                };
                if (bottomBox){
                    var bbStartY = bottomBox.startY;
                    if (imgData && checkedValues.includes('4')){
                        bbStartY = imgData.y + imgData.height + 10; // text region starts below image
                    }
                    // Enlarge to match preview text scale (x2) while keeping center aligned
                    var factor = 2;
                    var origW = (bottomBox.endX - bottomBox.startX);
                    var origH = Math.max(10, bottomBox.endY - bbStartY);
                    var newW = origW * factor;
                    var newH = origH * factor;
                    var centerX = bottomBox.startX + origW/2;
                    var newStartX = centerX - newW/2;
                    data.bottomBox = {
                        startX: newStartX,
                        startY: bbStartY,
                        width: newW,
                        height: newH
                    };
                }
                if (checkedValues.includes('4')){
                    if (imgData){
                        data.imageBox = { startX: imgData.x, startY: imgData.y, width: imgData.width, height: imgData.height };
                    } else if (imageBox) {
                        data.imageBox = {
                            startX: imageBox.startX, startY: imageBox.startY,
                            width: imageBox.endX - imageBox.startX,
                            height: imageBox.endY - imageBox.startY
                        };
                    }
                }
                $.ajax({
                    type:"post", url:"/book/manager_stamp", data:data, dataType:"json",
                    headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success:function(res){
                        if (res.status){ Swal.fire("", "บันทึกลายเซ็นเรียบร้อยแล้ว", "success"); setTimeout(()=>location.reload(), 1200); }
                        else { Swal.fire("", "บันทึกไม่สำเร็จ", "error"); }
                    }
                });
            });
    });

    $('#manager-send').click(function(e){
        e.preventDefault();
        $.ajax({
            type:"post", url:"{{ route('book.checkbox_send') }}",
            headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success:function(response){
                Swal.fire({
                    title:'แทงเรื่อง', html:response, allowOutsideClick:false, focusConfirm:true,
                    confirmButtonText:'ตกลง', showCancelButton:true, cancelButtonText:'ยกเลิก',
                    preConfirm:()=>{
                        var ids=[], texts=[];
                        $('input[name="flexCheckChecked[]"]:checked').each(function(){
                            ids.push($(this).val()); texts.push($(this).next('label').text().trim());
                        });
                        if (ids.length===0) Swal.showValidationMessage('กรุณาเลือกตัวเลือก');
                        return { id:ids, text:texts };
                    }
                }).then((r)=>{
                    if (!r.isConfirmed) return;
                    $('#txt_label').text('- แทงเรื่อง ('+ r.value.text.join(',') +') -');
                    $('#users_id').val(r.value.id.join(','));
                    var sendBtn = document.getElementById('send-save'); if (sendBtn) sendBtn.disabled = false;
                });
            }
        });
    });

    $('#send-save').click(function(e){
        e.preventDefault();
        var id = $('#id').val(), users_id = $('#users_id').val(), position_id = $('#position_id').val();
        Swal.fire({ title:"ยืนยันการแทงเรื่อง", showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก", icon:'question' })
            .then((r)=>{
                if (!r.isConfirmed) return;
                $.ajax({
                    type:"post", url:"/book/send_to_save",
                    data:{ id:id, users_id:users_id, status:12, position_id:position_id },
                    dataType:"json", headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success:function(res){
                        if (res.status){ Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success"); setTimeout(()=>location.reload(), 1200); }
                        else { Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error"); }
                    }
                });
            });
    });

    $(document).ready(function(){
        $('#manager-sinature').click(function(e){ e.preventDefault(); });
        $('#insert-pages').click(function(e){ e.preventDefault(); $('#insert_tab').show(); });

        $('#reject-book').click(function(e){
            e.preventDefault();
            Swal.fire({
                title:"", text:"ยืนยันการปฏิเสธหนังสือหรือไม่", icon:"warning",
                input:'textarea', inputPlaceholder:'กรอกเหตุผลการปฏิเสธ',
                showCancelButton:true, confirmButtonColor:"#3085d6", cancelButtonColor:"#d33",
                cancelButtonText:"ยกเลิก", confirmButtonText:"ตกลง",
                preConfirm:(note)=>{ if(!note) Swal.showValidationMessage('กรุณากรอกเหตุผล'); return note; }
            }).then((r)=>{
                if (!r.isConfirmed) return;
                $.ajax({
                    type:"post", url:"/book/reject",
                    data:{ id: $('#id').val(), note: r.value },
                    dataType:"json", headers:{ 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success:function(res){
                        if (res.status){ Swal.fire("", "ปฏิเสธเรียบร้อย", "success"); setTimeout(()=>location.reload(), 1200); }
                        else { Swal.fire("", "ปฏิเสธไม่สำเร็จ", "error"); }
                    }
                });
            });
        });

        // preview insert (ถ้ามี)
        async function createAndRenderPDF(){
            const pdfDoc = await PDFLib.PDFDocument.create(); pdfDoc.addPage([600,800]);
            const pdfBytes = await pdfDoc.save();
            const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
            loadingTask.promise.then(pdf=>pdf.getPage(1)).then(page=>{
                const scale = 1.5, viewport = page.getViewport({scale});
                const c = document.getElementById("pdf-render-insert"); if (!c) return;
                const ctx = c.getContext("2d"); c.width=viewport.width; c.height=viewport.height;
                return page.render({ canvasContext: ctx, viewport }).promise;
            }).catch(err=>console.error("Error rendering PDF:", err));
        }
        if (document.getElementById("pdf-render-insert")) createAndRenderPDF();
    });
</script>
@endsection
