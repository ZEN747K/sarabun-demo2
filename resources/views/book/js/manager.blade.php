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
// === Preload signature & globals for multi-box ===
var signatureImg = new Image();
var signatureImgLoaded = false;
signatureImg.onload = function(){ signatureImgLoaded = true; };
signatureImg.src = signature;

// persistent coordinates for multi-boxes on main/insert canvases
var signatureCoordinates = null;
var signatureCoordinatesInsert = null;

// always show meta (name/rank/date) regardless of checkboxes
var ALWAYS_SHOW_META = true;


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
                // expose scale/viewport for save conversion if backend needs points
                window.__pdfScale = scale;
                window.__pdfViewport = {width: viewport.width, height: viewport.height};
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

        
// === helpers: state + draw + handlers (multibox) ===
function buildDefaultState(canvas, withBottomBox=true, withImageBox=true) {
    const cw = canvas.width, ch = canvas.height;
    const textW=220, textH=40, metaH=80, imgW=240, imgH=130, gap=10;
    const startX = (cw - textW)/2;
    const startY = (ch - (textH + (withBottomBox? gap+metaH : 0) + (withImageBox? gap+imgH : 0)))/2;
    const state = { textBox: { startX:startX, startY:startY, endX:startX+textW, endY:startY+textH, type:'text' } };
    let cursorY = startY + textH;
    if (withBottomBox) {
        state.bottomBox = { startX:startX, startY:cursorY+gap, endX:startX+textW, endY:cursorY+gap+metaH, type:'bottom' };
        cursorY = state.bottomBox.endY;
    }
    if (withImageBox) {
        state.imageBox  = { startX:startX-13, startY:cursorY+gap, endX:startX-13+imgW, endY:cursorY+gap+imgH, type:'image' };
    }
    return state;
}

function drawMultiBoxes(canvas, state, opts) {
    const ctx = canvas.getContext('2d');
    const {
        text, checkedValues, signatureImgLoaded, signatureImg,
        showImage = (checkedValues||[]).includes('4'),
        fullName = `({{$users->fullname}})`,
        rankText = `{{$permission_data->permission_name}}`,
        dateText = `{{convertDateToThai(date("Y-m-d"))}}`
    } = opts || {};

    ctx.clearRect(0,0,canvas.width,canvas.height);
    const handle = 14;

    // text box (blue)
    if (state.textBox) {
        const b = state.textBox, w=b.endX-b.startX, h=b.endY-b.startY;
        ctx.save();
        ctx.strokeStyle='blue'; ctx.lineWidth=.8; ctx.strokeRect(b.startX,b.startY,w,h);
        ctx.fillStyle='#fff'; ctx.strokeStyle='#007bff'; ctx.lineWidth=2;
        ctx.fillRect(b.endX-handle, b.endY-handle, handle, handle);
        ctx.strokeRect(b.endX-handle, b.endY-handle, handle, handle);
        const scale = Math.max(.5, Math.min(2.5, Math.min(w/220, h/40)));
        ctx.font=(15*scale).toFixed(1)+'px Sarabun'; ctx.fillStyle='blue';
        const lines = (text||'').split('\n'); const lh=20*scale;
        for (let i=0;i<lines.length;i++){ const tw=ctx.measureText(lines[i]).width;
            const cx=(b.startX+b.endX)/2 - tw/2; ctx.fillText(lines[i], cx, b.startY+25*scale+i*lh); }
        ctx.restore();
    }

    // bottom meta (purple) - always show if ALWAYS_SHOW_META
    if (state.bottomBox) {
        const b = state.bottomBox, w=b.endX-b.startX, h=b.endY-b.startY;
        ctx.save();
        ctx.strokeStyle='purple'; ctx.lineWidth=.8; ctx.strokeRect(b.startX,b.startY,w,h);
        ctx.fillStyle='#fff'; ctx.strokeStyle='#6f42c1'; ctx.lineWidth=2;
        ctx.fillRect(b.endX-handle, b.endY-handle, handle, handle);
        ctx.strokeRect(b.endX-handle, b.endY-handle, handle, handle);
        const metaScale = Math.max(.5, Math.min(2.5, Math.min(w/220, h/80)));
        ctx.font=(15*metaScale).toFixed(1)+'px Sarabun'; ctx.fillStyle='blue';
        const metaLines = ALWAYS_SHOW_META ? [fullName, rankText, dateText] : [];
        const lh=20*metaScale;
        metaLines.forEach((line,i)=>{ const tw=ctx.measureText(line).width;
            const cx=(b.startX+b.endX)/2 - tw/2; ctx.fillText(line, cx, b.startY + 25*metaScale + i*lh); });
        ctx.restore();
    }

    // image box (green)
    if (state.imageBox) {
        const b = state.imageBox, w=b.endX-b.startX, h=b.endY-b.startY;
        if (showImage) {
            ctx.save();
            ctx.strokeStyle='green'; ctx.lineWidth=.8; ctx.strokeRect(b.startX,b.startY,w,h);
            ctx.fillStyle='#fff'; ctx.strokeStyle='#28a745'; ctx.lineWidth=2;
            ctx.fillRect(b.endX-handle, b.endY-handle, handle, handle);
            ctx.strokeRect(b.endX-handle, b.endY-handle, handle, handle);
            if (signatureImgLoaded) ctx.drawImage(signatureImg, b.startX, b.startY, w, h);
            ctx.restore();
        } else {
            ctx.save(); ctx.setLineDash([6,6]); ctx.strokeStyle='rgba(0,128,0,.5)'; ctx.lineWidth=.8;
            ctx.strokeRect(b.startX,b.startY,w,h); ctx.restore();
        }
    }
}

function setupMultiBoxHandlers(canvas, state, options) {
    const { onChange, getShowImage, textProvider, signatureImgLoaded, signatureImg } = options || {};
    let isDragging=false, isResizing=false, activeBox=null, dx=0, dy=0;
    const handle=14;

    function hitResize(x,y,b){ return x>=b.endX-handle && x<=b.endX && y>=b.endY-handle && y<=b.endY; }
    function hitBox(x,y,b){ return x>=b.startX && x<=b.endX && y>=b.startY && y<=b.endY; }
    function pick(x,y){
        if (state.bottomBox && hitBox(x,y,state.bottomBox)) return state.bottomBox;
        if (state.imageBox  && hitBox(x,y,state.imageBox))  return state.imageBox;
        if (state.textBox   && hitBox(x,y,state.textBox))   return state.textBox;
        return null;
    }

    canvas.addEventListener('mousemove', (e)=>{
        const r=canvas.getBoundingClientRect(), x=e.clientX-r.left, y=e.clientY-r.top;
        const showImg=!!getShowImage?.();
        if ((state.textBox && hitResize(x,y,state.textBox)) ||
            (state.bottomBox && hitResize(x,y,state.bottomBox)) ||
            (showImg && state.imageBox && hitResize(x,y,state.imageBox))) {
            canvas.style.cursor='se-resize';
        } else if (pick(x,y)) canvas.style.cursor='move';
        else canvas.style.cursor='default';
    });

    canvas.onmousedown=(e)=>{
        const r=canvas.getBoundingClientRect(), x=e.clientX-r.left, y=e.clientY-r.top;
        const showImg=!!getShowImage?.();
        const candidates=[state.textBox, state.bottomBox, showImg? state.imageBox:null].filter(Boolean);
        for(const b of candidates){
            if (hitResize(x,y,b)){ activeBox=b; isResizing=true; window.addEventListener('mousemove', onResize); window.addEventListener('mouseup', onUp); return; }
        }
        const b=pick(x,y);
        if (b){ activeBox=b; isDragging=true; dx=x-b.startX; dy=y-b.startY; window.addEventListener('mousemove', onDrag); window.addEventListener('mouseup', onUp); }
    };

    function onDrag(e){
        if (!isDragging || !activeBox) return;
        const r=canvas.getBoundingClientRect(), x=e.clientX-r.left, y=e.clientY-r.top;
        const w=activeBox.endX-activeBox.startX, h=activeBox.endY-activeBox.startY;
        let sx=Math.max(0, Math.min(canvas.width-w, x-dx));
        let sy=Math.max(0, Math.min(canvas.height-h, y-dy));
        activeBox.startX=sx; activeBox.startY=sy; activeBox.endX=sx+w; activeBox.endY=sy+h;
        onChange?.(activeBox);
        drawMultiBoxes(canvas, state, {
            text: textProvider?.(), signatureImgLoaded, signatureImg, showImage: getShowImage?.()
        });
    }
    function onResize(e){
        if (!isResizing || !activeBox) return;
        const r=canvas.getBoundingClientRect(), x=e.clientX-r.left, y=e.clientY-r.top;
        const minW=40,minH=30;
        activeBox.endX=Math.min(canvas.width,  Math.max(activeBox.startX+minW, x));
        activeBox.endY=Math.min(canvas.height, Math.max(activeBox.startY+minH, y));
        onChange?.(activeBox);
        drawMultiBoxes(canvas, state, {
            text: textProvider?.(), signatureImgLoaded, signatureImg, showImage: getShowImage?.()
        });
    }
    function onUp(){ isDragging=false; isResizing=false; activeBox=null;
        window.removeEventListener('mousemove', onDrag);
        window.removeEventListener('mousemove', onResize);
        window.removeEventListener('mouseup', onUp);
    }

    // initial draw
    drawMultiBoxes(canvas, state, {
        text: textProvider?.(), signatureImgLoaded, signatureImg, showImage: getShowImage?.()
    });
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


// Build payload for saving multi-box signature (text + bottom + image)
function buildSignaturePayload(opts){
    const id = $('#id').val();
    const pages = $('#page-select').find(":selected").val();
    const positionPages = $('#positionPages').val() || 1;
    const text = $('#modal-text').val() || '';
    let checkedValues = $('input[type="checkbox"]:checked').map(function(){ return $(this).val(); }).get() || [];

    // If we always show meta, make sure 1,2,3 are included so backend renders too
    if (ALWAYS_SHOW_META) {
        ['1','2','3'].forEach(v=>{ if(!checkedValues.includes(v)) checkedValues.push(v); });
    }

    const s = (opts && opts.state) || window.signatureCoordinates;
    const boxData = {};
    if (s?.textBox)   boxData.textBox   = { startX: s.textBox.startX,   startY: s.textBox.startY,   width: s.textBox.endX - s.textBox.startX,   height: s.textBox.endY - s.textBox.startY };
    if (s?.bottomBox) boxData.bottomBox = { startX: s.bottomBox.startX, startY: s.bottomBox.startY, width: s.bottomBox.endX - s.bottomBox.startX, height: s.bottomBox.endY - s.bottomBox.startY };
    if (s?.imageBox)  boxData.imageBox  = { startX: s.imageBox.startX,  startY: s.imageBox.startY,  width: s.imageBox.endX - s.imageBox.startX,  height: s.imageBox.endY - s.imageBox.startY };

    // include scale hints
    const payload = {
        id, pages, positionPages,
        text,
        'checkedValues[]': checkedValues, // jQuery traditional:true will keep array form
        includeMeta: ALWAYS_SHOW_META ? 1 : 0,
        canvasWidth: (window.__pdfViewport && window.__pdfViewport.width)  || $('#mark-layer').get(0)?.width || null,
        canvasHeight:(window.__pdfViewport && window.__pdfViewport.height) || $('#mark-layer').get(0)?.height || null,
        scale: window.__pdfScale || 1,
        // legacy top-left for compatibility (anchor at textBox if present)
        positionX: s?.textBox ? s.textBox.startX : ($('#positionX').val() || 0),
        positionY: s?.textBox ? s.textBox.startY : ($('#positionY').val() || 0),
        width:  s?.textBox ? (s.textBox.endX - s.textBox.startX) : null,
        height: s?.textBox ? (s.textBox.endY - s.textBox.startY) : null
    };

    // also send explicit names for server variants
    if (boxData.textBox)   payload.textBox   = boxData.textBox;
    if (boxData.bottomBox) payload.bottomBox = boxData.bottomBox;
    if (boxData.imageBox)  {
        payload.imageBox  = boxData.imageBox;
        payload.imageWidth  = boxData.imageBox.width;
        payload.imageHeight = boxData.imageBox.height;
        // ratio relative to original default
        payload.imageScaleX = boxData.imageBox.width  / 240.0;
        payload.imageScaleY = boxData.imageBox.height / 130.0;
    }
    return payload;
}
// === initialize multi-box frames after confirm (show frames immediately) ===
(function initMultiBoxAfterConfirm(){
  try {
    const mainCanvas = document.getElementById('mark-layer');
    const insertCanvas = document.getElementById('mark-layer-insert');

    // Build default state + handlers for main canvas
    signatureCoordinates = buildDefaultState(mainCanvas, true, true);
    const syncMain = (box)=>{
      if (box && box.type === 'text') {
        $('#positionX').val(Math.round(box.startX));
        $('#positionY').val(Math.round(box.startY));
        $('#positionPages').val(1);
      }
    };
    setupMultiBoxHandlers(mainCanvas, signatureCoordinates, {
      textProvider: ()=> $('#modal-text').val(),
      getShowImage: ()=> $('input[type="checkbox"][value="4"]').is(':checked'),
      signatureImgLoaded: signatureImgLoaded,
      signatureImg: signatureImg,
      onChange: syncMain
    });
    syncMain(signatureCoordinates.textBox);

    // Insert canvas (if present)
    if (insertCanvas) {
      signatureCoordinatesInsert = buildDefaultState(insertCanvas, true, true);
      const syncIns = (box)=>{
        if (box && box.type === 'text') {
          $('#positionX').val(Math.round(box.startX));
          $('#positionY').val(Math.round(box.startY));
          $('#positionPages').val(2);
        }
      };
      setupMultiBoxHandlers(insertCanvas, signatureCoordinatesInsert, {
        textProvider: ()=> $('#modal-text').val(),
        getShowImage: ()=> $('input[type="checkbox"][value="4"]').is(':checked'),
        signatureImgLoaded: signatureImgLoaded,
        signatureImg: signatureImg,
        onChange: syncIns
      });
    }

    // live redraw on text/checkbox change
    $(document).off('input.multibox change.multibox', '#modal-text, input[type="checkbox"]');
    $(document).on('input.multibox change.multibox', '#modal-text, input[type="checkbox"]', function(){
      drawMultiBoxes(mainCanvas, signatureCoordinates, {
        text: $('#modal-text').val(),
        signatureImgLoaded: signatureImgLoaded,
        signatureImg: signatureImg,
        showImage: $('input[type="checkbox"][value="4"]').is(':checked')
      });
      if (insertCanvas) {
        drawMultiBoxes(insertCanvas, signatureCoordinatesInsert, {
          text: $('#modal-text').val(),
          signatureImgLoaded: signatureImgLoaded,
          signatureImg: signatureImg,
          showImage: $('input[type="checkbox"][value="4"]').is(':checked')
        });
      }
    });
  } catch(err) {
    console.error('initMultiBoxAfterConfirm error:', err);
  }
})();


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
                        data: (function(){ var s=window.signatureCoordinates; var extra={}; if(s&&s.textBox){ extra.width = s.textBox.endX - s.textBox.startX; extra.height = s.textBox.endY - s.textBox.startY; } return { id:id, positionX:positionX, positionY:positionY, pages:pages, positionPages:positionPages, status:7, text:text, 'checkedValues[]':checkedValues, width: extra.width||undefined, height: extra.height||undefined, scale: window.__pdfScale||1, canvasWidth: (window.__pdfViewport&&window.__pdfViewport.width)||null, canvasHeight:(window.__pdfViewport&&window.__pdfViewport.height)||null }; })(),
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        traditional: true,
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
                        traditional: true,
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
$('#signature-save').click(function(e){
  e.preventDefault();
  var id = $('#id').val();
  if(!id){ return Swal.fire('', 'ไม่พบรหัสเอกสาร', 'info'); }
  Swal.fire({title:'ยืนยันการลงเกษียณหนังสือ', showCancelButton:true, icon:'question', confirmButtonText:'ตกลง', cancelButtonText:'ยกเลิก'})
   .then((r)=>{
     if(!r.isConfirmed) return;
     $.ajax({
       type:'post', url:'/book/signature_stamp',
       data: buildSignaturePayload({state: window.signatureCoordinates}),
       dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, traditional:true
     }).done(function(resp){
       if(resp.status){ Swal.fire('', 'ลงบันทึกเกษียณหนังสือเรียบร้อย', 'success'); setTimeout(()=>location.reload(), 1200); }
       else { Swal.fire('', resp.message || 'บันทึกไม่สำเร็จ', 'error'); }
     });
   });
});
