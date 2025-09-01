@section('script')
<?php $position = $item; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  @include('book.js.constants')
  $('.btn-default').hide();

  var signature = '{{$signature}}';
  var permission = '{{$permission}}';
  var selectPageTable = document.getElementById('page-select-card');
  var pageTotal = '{{$totalPages}}';
  var pageNumTalbe = 1;
  // Prevent duplicate UI updates from overlapping loads
  var pdfLoadSeq = 0;

  // รูปลายเซ็น
  var imgData = null;
  var signatureImg = new Image();
  var signatureImgLoaded = false;
  signatureImg.onload = function () { signatureImgLoaded = true; };
  signatureImg.src = signature;

  // ตัวแปรส่วนกลาง
  var markCoordinates = null;       // สำหรับกล่องตราประทับของแอดมิน
  var signatureCoordinates = null;  // สำหรับกล่องลายเซ็น (text / image / bottom)

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
        pdfCtxInsert = pdfCanvasInsert.getContext('2d'),
        markCanvas = document.getElementById('mark-layer'),
        markCtx = markCanvas.getContext('2d'),
        selectPage = document.getElementById('page-select');

    // Reset page select by replacing node to remove options and old listeners
    if (selectPage) {
      const cleanSelect = selectPage.cloneNode(false);
      selectPage.parentNode.replaceChild(cleanSelect, selectPage);
      selectPage = cleanSelect;
    }

    document.getElementById('add-stamp').disabled = true;

    function renderPage(num) {
      pageRendering = true;
      pdfDoc.getPage(num).then(function(page) {
        let viewport = page.getViewport({ scale: scale });
        pdfCanvas.height = viewport.height;
        pdfCanvas.width  = viewport.width;
        markCanvas.height = viewport.height;
        markCanvas.width  = viewport.width;

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
      selectPage.value = num;
    }

    function queueRenderPage(num) {
      if (pageRendering) pageNumPending = num;
      else renderPage(num);
    }

    function onNextPage() {
      if (pageNum >= pdfDoc.numPages) return;
      pageNum++; queueRenderPage(pageNum);
    }

    function onPrevPage() {
      if (pageNum <= 1) return;
      pageNum--; queueRenderPage(pageNum);
    }

    selectPage.addEventListener('change', function() {
      let selectedPage = parseInt(this.value);
      if (selectedPage && selectedPage >= 1 && selectedPage <= pdfDoc.numPages) {
        pageNum = selectedPage; queueRenderPage(selectedPage);
      }
    });

    pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
      if (thisLoad !== pdfLoadSeq) return; // stale load
      pdfDoc = pdfDoc_;
      for (let i = 1; i <= pdfDoc.numPages; i++) {
        let option = document.createElement('option');
        option.value = i; option.textContent = i;
        selectPage.appendChild(option);
      }
      renderPage(pageNum);
      document.getElementById('add-stamp').disabled = false;
    });

    // overwrite instead of stacking listeners
    document.getElementById('next').onclick = onNextPage;
    document.getElementById('prev').onclick = onPrevPage;

    // ==========================
    // ปุ่ม Add Stamp (แอดมิน)
    // ==========================
    $('#add-stamp').click(function (e) {
      e.preventDefault();
      removeMarkListener();
      document.getElementById('add-stamp').disabled = true;
      document.getElementById('save-stamp').disabled = false;

      var markCanvas = document.getElementById('mark-layer');
      var markCtx = markCanvas.getContext('2d');

      // กล่องเริ่มกลางหน้า
      var defaultWidth = 220, defaultHeight = 115;
      var startX = (markCanvas.width - defaultWidth) / 2;
      var startY = (markCanvas.height - defaultHeight) / 2;
      var endX = startX + defaultWidth, endY = startY + defaultHeight;

      markCoordinates = { startX, startY, endX, endY };
      drawMark(startX, startY, endX, endY);

      $('#positionX').val(startX);
      $('#positionY').val(startY);
      $('#positionPages').val(1);

      var text = '{{$position_name}}';
      var dynamicX = (text.length >= 30) ? 5 : (text.length >= 20) ? 10 :
                     (text.length >= 15) ? 60 : (text.length >= 13) ? 75 :
                     (text.length >= 10) ? 70 : 80;

      drawTextHeaderClassic('15px Sarabun', startX + dynamicX, startY + 25, text);
      drawTextHeaderClassic('12px Sarabun', startX + 8,  startY + 55, 'รับที่..........................................................');
      drawTextHeaderClassic('12px Sarabun', startX + 8,  startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
      drawTextHeaderClassic('12px Sarabun', startX + 8,  startY + 100,'เวลา......................................................น.');

      // drag/resize
      var isDragging = false, isResizing = false;
      var dragOffsetX = 0, dragOffsetY = 0;
      var resizeHandleSize = 16;

      function redrawStampBox() {
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
        var boxW = markCoordinates.endX - markCoordinates.startX;
        var boxH = markCoordinates.endY - markCoordinates.startY;
        var scale = Math.min(boxW / 220, boxH / 115);
        scale = Math.max(0.5, Math.min(2.5, scale));

        drawMark(markCoordinates.startX, markCoordinates.startY, markCoordinates.endX, markCoordinates.endY);

        var text = '{{$position_name}}';
        var dynamicX = (text.length >= 30) ? 5*scale : (text.length >= 20) ? 10*scale :
                       (text.length >= 15) ? 60*scale : (text.length >= 13) ? 75*scale :
                       (text.length >= 10) ? 70*scale : 80*scale;

        drawTextHeaderClassic((15*scale).toFixed(1)+'px Sarabun', markCoordinates.startX + dynamicX,      markCoordinates.startY + 25*scale, text);
        drawTextHeaderClassic((12*scale).toFixed(1)+'px Sarabun', markCoordinates.startX + 8*scale,       markCoordinates.startY + 55*scale, 'รับที่..........................................................');
        drawTextHeaderClassic((12*scale).toFixed(1)+'px Sarabun', markCoordinates.startX + 8*scale,       markCoordinates.startY + 80*scale, 'วันที่.........เดือน......................พ.ศ.........');
        drawTextHeaderClassic((12*scale).toFixed(1)+'px Sarabun', markCoordinates.startX + 8*scale,       markCoordinates.startY + 100*scale,'เวลา......................................................น.');
      }

      function isOnResizeHandle(mx, my) {
        return (mx >= markCoordinates.endX - resizeHandleSize && mx <= markCoordinates.endX &&
                my >= markCoordinates.endY - resizeHandleSize && my <= markCoordinates.endY);
      }

      markCanvas.addEventListener('mousemove', function (e) {
        var rect = markCanvas.getBoundingClientRect();
        var mx = e.clientX - rect.left, my = e.clientY - rect.top;
        if (isOnResizeHandle(mx, my)) markCanvas.style.cursor = 'se-resize';
        else if (mx >= markCoordinates.startX && mx <= markCoordinates.endX && my >= markCoordinates.startY && my <= markCoordinates.endY)
          markCanvas.style.cursor = 'move';
        else markCanvas.style.cursor = 'default';
      });

      markCanvas.onmousedown = function (e) {
        var rect = markCanvas.getBoundingClientRect();
        var mx = e.clientX - rect.left, my = e.clientY - rect.top;
        if (isOnResizeHandle(mx, my)) {
          isResizing = true;
          e.preventDefault();
          window.addEventListener('mousemove', onResizeMove);
          window.addEventListener('mouseup', onResizeEnd);
        } else if (mx >= markCoordinates.startX && mx <= markCoordinates.endX && my >= markCoordinates.startY && my <= markCoordinates.endY) {
          isDragging = true;
          dragOffsetX = mx - markCoordinates.startX;
          dragOffsetY = my - markCoordinates.startY;
          e.preventDefault();
          window.addEventListener('mousemove', onDragMove);
          window.addEventListener('mouseup', onDragEnd);
        }
      };

      markCanvas.addEventListener('click', function (e) {
        var rect = markCanvas.getBoundingClientRect();
        var mx = e.clientX - rect.left, my = e.clientY - rect.top;
        if (!isDragging && !isResizing &&
            (mx < markCoordinates.startX || mx > markCoordinates.endX || my < markCoordinates.startY || my > markCoordinates.endY)) {
          e.stopPropagation(); e.preventDefault();
        }
      }, true);

      function onDragMove(e) {
        if (!isDragging) return;
        var rect = markCanvas.getBoundingClientRect();
        var mx = e.clientX - rect.left, my = e.clientY - rect.top;
        var w = markCoordinates.endX - markCoordinates.startX;
        var h = markCoordinates.endY - markCoordinates.startY;
        var nsx = Math.max(0, Math.min(markCanvas.width  - w, mx - dragOffsetX));
        var nsy = Math.max(0, Math.min(markCanvas.height - h, my - dragOffsetY));
        var nex = nsx + w, ney = nsy + h;
        if (nex > markCanvas.width)  { nex = markCanvas.width;  nsx = nex - w; }
        if (ney > markCanvas.height) { ney = markCanvas.height; nsy = ney - h; }
        markCoordinates.startX = nsx; markCoordinates.startY = nsy;
        markCoordinates.endX   = nex; markCoordinates.endY   = ney;
        $('#positionX').val(nsx); $('#positionY').val(nsy);
        redrawStampBox();
        showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
      }

      function onResizeMove(e) {
        if (!isResizing) return;
        var rect = markCanvas.getBoundingClientRect();
        var mx = e.clientX - rect.left, my = e.clientY - rect.top;
        var minW = 40, minH = 30;
        markCoordinates.endX = Math.min(markCanvas.width,  Math.max(markCoordinates.startX + minW, mx));
        markCoordinates.endY = Math.min(markCanvas.height, Math.max(markCoordinates.startY + minH, my));
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

      // Insert page (พับครึ่ง)
      markEventListenerInsert = function (e) {
        var markCanvas = document.getElementById('mark-layer-insert');
        var rect = markCanvas.getBoundingClientRect();
        var startX = (e.clientX - rect.left);
        var startY = (e.clientY - rect.top);
        var endX = startX + 220, endY = startY + 115;

        markCoordinates = { startX, startY, endX, endY };
        drawMarkInsert(startX, startY, endX, endY);

        $('#positionX').val(startX);
        $('#positionY').val(startY);
        $('#positionPages').val(2);

        var text = '{{$position_name}}';
        var dynamicX = (text.length >= 30) ? 5 : (text.length >= 20) ? 10 :
                       (text.length >= 15) ? 60 : (text.length >= 13) ? 75 :
                       (text.length >= 10) ? 70 : 80;

        drawTextHeaderClassicInsert('15px Sarabun', startX + dynamicX, startY + 25, text);
        drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
        drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
        drawTextHeaderClassicInsert('12px Sarabun', startX + 8, startY + 100,'เวลา......................................................น.');
      };

      var markCanvasInsert = document.getElementById('mark-layer-insert');
      markCanvasInsert.addEventListener('click', markEventListenerInsert);
    });

    // ==========================
    // Modal ยืนยันลายเซ็น -> โหมดวางลายเซ็น
    // ==========================
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
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function (response) {
          if (!response.status) {
            $('#exampleModal').modal('hide');
            Swal.fire("", response.message, "error");
            return;
          }

          setTimeout(() => swal.close(), 1500);
          resetMarking();
          removeMarkListener();
          document.getElementById('signature-save').disabled = false;

          // คลิกเพื่อเข้าสู่โหมดวางกล่องลายเซ็น (หน้าเอกสารหลัก)
          markEventListener = function () {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');

            // สร้างกล่องครั้งแรกถ้ายังไม่มี
            if (!signatureCoordinates) {
              var defaultTextWidth = 220, defaultTextHeight = 40;
              var defaultBottomBoxHeight = 80;
              var defaultImageWidth = 240, defaultImageHeight = 130;
              var gap = 10;

              var startX = (markCanvas.width - defaultTextWidth) / 2;
              var totalH = defaultTextHeight + gap + defaultImageHeight + gap + defaultBottomBoxHeight;
              var startY = (markCanvas.height - totalH) / 2;

              var textBox = {
                startX: startX,
                startY: startY,
                endX: startX + defaultTextWidth,
                endY: startY + defaultTextHeight,
                type: 'text'
              };

              // ประกาศ imageBox ก่อน bottomBox
              var imageBox = {
                startX: startX - 13,
                startY: startY + defaultTextHeight + gap,
                endX: (startX - 13) + defaultImageWidth,
                endY: startY + defaultTextHeight + gap + defaultImageHeight,
                type: 'image'
              };

              var bottomBox = {
                startX: startX,
                startY: imageBox.endY + gap,
                endX: startX + defaultTextWidth,
                endY: imageBox.endY + gap + defaultBottomBoxHeight,
                type: 'bottom'
              };

              signatureCoordinates = { textBox, bottomBox, imageBox };
              $('#positionX').val(startX);
              $('#positionY').val(startY);
              $('#positionPages').val(1);
            }

            // state โต้ตอบ
            var isDragging = false, isResizing = false, activeBox = null;
            var dragOffsetX = 0, dragOffsetY = 0, resizeHandleSize = 16;

            function redrawSignatureBoxes() {
              markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
              var text = $('#modal-text').val();
              var checkedValues = $('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();

              // กล่องบน (ข้อความ)
              var textBox = signatureCoordinates.textBox;
              markCtx.save();
              markCtx.strokeStyle = 'blue'; markCtx.lineWidth = 0.5;
              markCtx.strokeRect(textBox.startX, textBox.startY, textBox.endX - textBox.startX, textBox.endY - textBox.startY);
              markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#007bff'; markCtx.lineWidth = 2;
              markCtx.fillRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
              markCtx.strokeRect(textBox.endX - resizeHandleSize, textBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
              markCtx.restore();

              var textScale = Math.min((textBox.endX - textBox.startX)/220, (textBox.endY - textBox.startY)/40);
              textScale = Math.max(0.5, Math.min(2.5, textScale));
              drawTextHeaderSignature((15*textScale).toFixed(1)+'px Sarabun',
                (textBox.startX + textBox.endX)/2, textBox.startY + 25*textScale, text);

              // กล่องกลาง (รูปเซ็น)
              var hasImage = checkedValues.includes('4');
              if (hasImage) {
                var imageBox = signatureCoordinates.imageBox;
                markCtx.save();
                markCtx.strokeStyle = 'green'; markCtx.lineWidth = 0.5;
                markCtx.strokeRect(imageBox.startX, imageBox.startY, imageBox.endX - imageBox.startX, imageBox.endY - imageBox.startY);
                markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#28a745'; markCtx.lineWidth = 2;
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

              // กล่องล่าง (ข้อมูลเสริม)
              var bottomBox = signatureCoordinates.bottomBox;
              markCtx.save();
              markCtx.strokeStyle = 'purple'; markCtx.lineWidth = 0.5;
              markCtx.strokeRect(bottomBox.startX, bottomBox.startY, bottomBox.endX - bottomBox.startX, bottomBox.endY - bottomBox.startY);
              markCtx.fillStyle = '#fff'; markCtx.strokeStyle = '#6f42c1'; markCtx.lineWidth = 2;
              markCtx.fillRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
              markCtx.strokeRect(bottomBox.endX - resizeHandleSize, bottomBox.endY - resizeHandleSize, resizeHandleSize, resizeHandleSize);
              markCtx.restore();

              var bottomScale = Math.min((bottomBox.endX - bottomBox.startX)/220, (bottomBox.endY - bottomBox.startY)/80);
              bottomScale = Math.max(0.5, Math.min(2.5, bottomScale));

              var i = 0;
              checkedValues.forEach(function (element) {
                if (element == '4') return;
                var t = '';
                switch (element) {
                  case '1': t = `({{$users->fullname}})`; break;
                  case '2': t = `{{$permission_data->permission_name}}`; break;
                  case '3': t = `{{convertDateToThai(date("Y-m-d"))}}`; break;
                }
                drawTextHeaderSignature((15*bottomScale).toFixed(1)+'px Sarabun',
                  (bottomBox.startX + bottomBox.endX)/2,
                  bottomBox.startY + 25*bottomScale + (20*i*bottomScale),
                  t);
                i++;
              });
            }

            function isOnResizeHandle(x,y,box){return (x>=box.endX-16&&x<=box.endX&&y>=box.endY-16&&y<=box.endY);}
            function isInBox(x,y,box){return (x>=box.startX&&x<=box.endX&&y>=box.startY&&y<=box.endY);}
            function getActiveBox(x,y){
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
              var hasImage=checked.includes('4');
              if(isInBox(x,y,signatureCoordinates.bottomBox)) return signatureCoordinates.bottomBox;
              if(hasImage && isInBox(x,y,signatureCoordinates.imageBox)) return signatureCoordinates.imageBox;
              if(isInBox(x,y,signatureCoordinates.textBox)) return signatureCoordinates.textBox;
              return null;
            }

            markCanvas.addEventListener('mousemove', function (e) {
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left, y=e.clientY-r.top;
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
              var hasImage=checked.includes('4');
              if (isOnResizeHandle(x,y,signatureCoordinates.textBox) ||
                  isOnResizeHandle(x,y,signatureCoordinates.bottomBox) ||
                  (hasImage && isOnResizeHandle(x,y,signatureCoordinates.imageBox))) {
                markCanvas.style.cursor='se-resize';
              } else if (getActiveBox(x,y)) {
                markCanvas.style.cursor='move';
              } else {
                markCanvas.style.cursor='default';
              }
            });

            markCanvas.onmousedown = function (e) {
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left, y=e.clientY-r.top;
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
              var hasImage=checked.includes('4');

              if (isOnResizeHandle(x,y,signatureCoordinates.textBox)) {
                isResizing=true; activeBox=signatureCoordinates.textBox;
              } else if (isOnResizeHandle(x,y,signatureCoordinates.bottomBox)) {
                isResizing=true; activeBox=signatureCoordinates.bottomBox;
              } else if (hasImage && isOnResizeHandle(x,y,signatureCoordinates.imageBox)) {
                isResizing=true; activeBox=signatureCoordinates.imageBox;
              } else {
                activeBox=getActiveBox(x,y);
                if (activeBox) {
                  isDragging=true;
                  dragOffsetX = x - activeBox.startX;
                  dragOffsetY = y - activeBox.startY;
                }
              }

              if (isResizing) {
                e.preventDefault();
                window.addEventListener('mousemove', onResizeMove);
                window.addEventListener('mouseup', onResizeEnd);
              } else if (isDragging) {
                e.preventDefault();
                window.addEventListener('mousemove', onDragMove);
                window.addEventListener('mouseup', onDragEnd);
              }
            };

            function onDragMove(e){
              if(!isDragging||!activeBox) return;
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left, y=e.clientY-r.top;
              var w=activeBox.endX-activeBox.startX, h=activeBox.endY-activeBox.startY;
              var nsx=Math.max(0,Math.min(markCanvas.width -w, x-dragOffsetX));
              var nsy=Math.max(0,Math.min(markCanvas.height-h, y-dragOffsetY));
              activeBox.startX=nsx; activeBox.startY=nsy; activeBox.endX=nsx+w; activeBox.endY=nsy+h;
              if(activeBox.type==='text'){ $('#positionX').val(nsx); $('#positionY').val(nsy); }
              redrawSignatureBoxes();
            }
            function onResizeMove(e){
              if(!isResizing||!activeBox) return;
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left, y=e.clientY-r.top;
              var minW=40, minH=30;
              activeBox.endX=Math.min(markCanvas.width ,Math.max(activeBox.startX+minW,x));
              activeBox.endY=Math.min(markCanvas.height,Math.max(activeBox.startY+minH,y));
              redrawSignatureBoxes();
            }
            function onDragEnd(){ isDragging=false; activeBox=null; window.removeEventListener('mousemove', onDragMove); window.removeEventListener('mouseup', onDragEnd); }
            function onResizeEnd(){ isResizing=false; activeBox=null; window.removeEventListener('mousemove', onResizeMove); window.removeEventListener('mouseup', onResizeEnd); }

            redrawSignatureBoxes();
          };

          var markCanvas = document.getElementById('mark-layer');
          markCanvas.addEventListener('click', markEventListener);

          // โหมด insert page
          markEventListenerInsert = function () {
            var markCanvas = document.getElementById('mark-layer-insert');
            var markCtx = markCanvas.getContext('2d');

            var defaultTextWidth = 220, defaultTextHeight = 60;
            var defaultImageWidth = 240, defaultImageHeight = 130;

            var startX = (markCanvas.width - defaultTextWidth)/2;
            var startY = (markCanvas.height - (defaultTextHeight + defaultImageHeight + 20))/2;

            var textBox = { startX:startX, startY:startY, endX:startX+defaultTextWidth, endY:startY+defaultTextHeight, type:'text' };
            var imageBox= { startX:startX-13, startY:startY+defaultTextHeight+20, endX:startX+defaultImageWidth-13, endY:startY+defaultTextHeight+20+defaultImageHeight, type:'image' };

            signatureCoordinates = { textBox, imageBox };
            $('#positionX').val(startX); $('#positionY').val(startY); $('#positionPages').val(2);

            var isDragging=false, isResizing=false, activeBox=null, dragOffsetX=0, dragOffsetY=0, resizeHandleSize=16;

            function redrawSignatureBoxesInsert(){
              markCtx.clearRect(0,0,markCanvas.width,markCanvas.height);
              var text=$('#modal-text').val();
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();

              var textBox=signatureCoordinates.textBox;
              markCtx.save();
              markCtx.strokeStyle='blue'; markCtx.lineWidth=0.5;
              markCtx.strokeRect(textBox.startX,textBox.startY,textBox.endX-textBox.startX,textBox.endY-textBox.startY);
              markCtx.fillStyle='#fff'; markCtx.strokeStyle='#007bff'; markCtx.lineWidth=2;
              markCtx.fillRect(textBox.endX-16,textBox.endY-16,16,16);
              markCtx.strokeRect(textBox.endX-16,textBox.endY-16,16,16);
              markCtx.restore();

              var scale=Math.min((textBox.endX-textBox.startX)/220,(textBox.endY-textBox.startY)/60);
              scale=Math.max(0.5,Math.min(2.5,scale));
              drawTextHeaderSignatureInsert((15*scale).toFixed(1)+'px Sarabun',(textBox.startX+textBox.endX)/2,textBox.startY+20*scale,text);

              var i=0, plus_y=20;
              checked.forEach(function(el){
                if(el==='4') return;
                var t='';
                switch(el){
                  case '1': t=`({{$users->fullname}})`; break;
                  case '2': t=`{{$permission_data->permission_name}}`; break;
                  case '3': t=`{{convertDateToThai(date("Y-m-d"))}}`; break;
                }
                drawTextHeaderSignatureInsert((15*scale).toFixed(1)+'px Sarabun',
                  (textBox.startX+textBox.endX)/2, textBox.startY+(plus_y+20)*scale+(20*i*scale), t);
                i++;
              });

              if (checked.includes('4')){
                var imageBox=signatureCoordinates.imageBox;
                markCtx.save();
                markCtx.strokeStyle='green'; markCtx.lineWidth=0.5;
                markCtx.strokeRect(imageBox.startX,imageBox.startY,imageBox.endX-imageBox.startX,imageBox.endY-imageBox.startY);
                markCtx.fillStyle='#fff'; markCtx.strokeStyle='#28a745'; markCtx.lineWidth=2;
                markCtx.fillRect(imageBox.endX-16,imageBox.endY-16,16,16);
                markCtx.strokeRect(imageBox.endX-16,imageBox.endY-16,16,16);
                markCtx.restore();

                var w=imageBox.endX-imageBox.startX, h=imageBox.endY-imageBox.startY;
                if(signatureImgLoaded){
                  markCtx.drawImage(signatureImg,imageBox.startX,imageBox.startY,w,h);
                  imgData={x:imageBox.startX,y:imageBox.startY,width:w,height:h};
                }
              }
            }

            function isOnResizeHandle(x,y,box){return (x>=box.endX-16&&x<=box.endX&&y>=box.endY-16&&y<=box.endY);}
            function isInBox(x,y,box){return (x>=box.startX&&x<=box.endX&&y>=box.startY&&y<=box.endY);}
            function getActiveBox(x,y){
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
              if (checked.includes('4') && isInBox(x,y,signatureCoordinates.imageBox)) return signatureCoordinates.imageBox;
              if (isInBox(x,y,signatureCoordinates.textBox)) return signatureCoordinates.textBox;
              return null;
            }

            markCanvas.addEventListener('mousemove', function(e){
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left,y=e.clientY-r.top;
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
              if (isOnResizeHandle(x,y,signatureCoordinates.textBox) ||
                  (checked.includes('4') && isOnResizeHandle(x,y,signatureCoordinates.imageBox))) {
                markCanvas.style.cursor='se-resize';
              } else if (getActiveBox(x,y)) {
                markCanvas.style.cursor='move';
              } else {
                markCanvas.style.cursor='default';
              }
            });

            markCanvas.onmousedown=function(e){
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left,y=e.clientY-r.top;
              var checked=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();

              if (isOnResizeHandle(x,y,signatureCoordinates.textBox)) {
                isResizing=true; activeBox=signatureCoordinates.textBox;
              } else if (checked.includes('4') && isOnResizeHandle(x,y,signatureCoordinates.imageBox)) {
                isResizing=true; activeBox=signatureCoordinates.imageBox;
              } else {
                activeBox=getActiveBox(x,y);
                if (activeBox){
                  isDragging=true; dragOffsetX=x-activeBox.startX; dragOffsetY=y-activeBox.startY;
                }
              }

              if (isResizing){ e.preventDefault(); window.addEventListener('mousemove', onResizeMoveInsert); window.addEventListener('mouseup', onResizeEndInsert); }
              else if (isDragging){ e.preventDefault(); window.addEventListener('mousemove', onDragMoveInsert); window.addEventListener('mouseup', onDragEndInsert); }
            };

            function onDragMoveInsert(e){
              if(!isDragging||!activeBox) return;
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left,y=e.clientY-r.top;
              var w=activeBox.endX-activeBox.startX, h=activeBox.endY-activeBox.startY;
              var nsx=Math.max(0,Math.min(markCanvas.width -w, x-dragOffsetX));
              var nsy=Math.max(0,Math.min(markCanvas.height-h, y-dragOffsetY));
              activeBox.startX=nsx; activeBox.startY=nsy; activeBox.endX=nsx+w; activeBox.endY=nsy+h;
              if(activeBox.type==='text'){ $('#positionX').val(nsx); $('#positionY').val(nsy); }
              redrawSignatureBoxesInsert();
            }
            function onResizeMoveInsert(e){
              if(!isResizing||!activeBox) return;
              var r=markCanvas.getBoundingClientRect(); var x=e.clientX-r.left,y=e.clientY-r.top;
              var minW=40,minH=30;
              activeBox.endX=Math.min(markCanvas.width ,Math.max(activeBox.startX+minW,x));
              activeBox.endY=Math.min(markCanvas.height,Math.max(activeBox.startY+minH,y));
              redrawSignatureBoxesInsert();
            }
            function onDragEndInsert(){ isDragging=false; activeBox=null; window.removeEventListener('mousemove', onDragMoveInsert); window.removeEventListener('mouseup', onDragEndInsert); }
            function onResizeEndInsert(){ isResizing=false; activeBox=null; window.removeEventListener('mousemove', onResizeMoveInsert); window.removeEventListener('mouseup', onResizeEndInsert); }

            redrawSignatureBoxesInsert();
          };

          var markCanvasInsert = document.getElementById('mark-layer-insert');
          markCanvasInsert.addEventListener('click', markEventListenerInsert);
        }
      });
    });

    function countLineBreaks(text){ return text.split('\n').length - 1; }

    // ===== Helpers: วาดกรอบ/ข้อความ =====
    function drawMark(sx,sy,ex,ey){
      var can = document.getElementById('mark-layer-insert'), ctx = can.getContext('2d');
      ctx.clearRect(0,0,can.width,can.height);
      can = document.getElementById('mark-layer'); ctx = can.getContext('2d');
      ctx.clearRect(0,0,can.width,can.height);

      ctx.beginPath(); ctx.rect(sx,sy,ex-sx,ey-sy);
      ctx.lineWidth=0.5; ctx.strokeStyle='blue'; ctx.stroke();

      var sz=16;
      ctx.save();
      ctx.beginPath(); ctx.rect(ex-sz,ey-sz,sz,sz);
      ctx.fillStyle='#fff'; ctx.strokeStyle='#007bff'; ctx.lineWidth=2;
      ctx.fill(); ctx.stroke(); ctx.restore();
    }

    function drawTextHeaderClassic(font,x,y,text){
      var ctx = document.getElementById('mark-layer').getContext('2d');
      ctx.font = font; ctx.fillStyle="blue"; ctx.fillText(text,x,y);
    }
    function drawTextHeaderSignature(font,cx,y,text){
      var ctx = document.getElementById('mark-layer').getContext('2d');
      ctx.font = font; ctx.fillStyle="blue";
      var lines = text.split('\n'), lh=20;
      for (var i=0;i<lines.length;i++){
        var w = ctx.measureText(lines[i]).width;
        ctx.fillText(lines[i], cx - w/2, y + (i*lh));
      }
    }

    function drawMarkInsert(sx,sy,ex,ey){
      var can = document.getElementById('mark-layer'), ctx = can.getContext('2d');
      ctx.clearRect(0,0,can.width,can.height);
      can = document.getElementById('mark-layer-insert'); ctx = can.getContext('2d');
      ctx.clearRect(0,0,can.width,can.height);

      ctx.beginPath(); ctx.rect(sx,sy,ex-sx,ey-sy);
      ctx.lineWidth=0.5; ctx.strokeStyle='blue'; ctx.stroke();

      var cross=10;
      ctx.beginPath();
      ctx.moveTo(ex-cross,sy+cross); ctx.lineTo(ex,sy);
      ctx.moveTo(ex,sy+cross); ctx.lineTo(ex-cross,sy);
      ctx.lineWidth=2; ctx.strokeStyle='red'; ctx.stroke();

      // ปุ่มปิดกรอบเล็ก (มุมขวาบน)
      var canvasEl = document.getElementById('mark-layer-insert');
      canvasEl.addEventListener('click', function(ev){
        var r=canvasEl.getBoundingClientRect();
        var cx = ev.clientX - r.left, cy = ev.clientY - r.top;
        if (cx>=ex-cross && cx<=ex && cy>=sy && cy<=sy+cross){
          removeMarkListener();
          var cctx = canvasEl.getContext('2d');
          cctx.clearRect(0,0,canvasEl.width,canvasEl.height);
        }
      });
    }

    function drawTextHeaderClassicInsert(font,x,y,text){
      var ctx = document.getElementById('mark-layer-insert').getContext('2d');
      ctx.font = font; ctx.fillStyle="blue"; ctx.fillText(text,x,y);
    }
    function drawTextHeaderSignatureInsert(font,cx,y,text){
      var ctx = document.getElementById('mark-layer-insert').getContext('2d');
      ctx.font = font; ctx.fillStyle="blue";
      var lines = text.split('\n'), lh=20;
      for (var i=0;i<lines.length;i++){
        var w = ctx.measureText(lines[i]).width;
        ctx.fillText(lines[i], cx - w/2, y + (i*lh));
      }
    }
  }

  // ========= ตัวช่วยทั่วไป =========
  let markEventListener = null;
  let markEventListenerInsert = null;

  function openPdf(url,id,status,type,is_check='',number_id,position_id){
    $('.btn-default').hide();
    document.getElementById('reject-book').disabled = true;
    document.getElementById('add-stamp').disabled  = false;
    document.getElementById('save-stamp').disabled = true;
    document.getElementById('send-save').disabled  = true;
    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
    pdf(url);
    $('#id').val(id);
    $('#position_id').val(position_id);
    $('#positionX').val(''); $('#positionY').val(''); $('#txt_label').text(''); $('#users_id').val('');
    document.getElementById('add-stamp').disabled = true;

    if (status == STATUS.ADMIN_PROCESS) {
      $('#insert-pages').show(); $('#add-stamp').show(); $('#save-stamp').show();
    }
    if (status == STATUS.WAITING_SIGNATURE) {
      const perms = permission.split(',').map(p => p.trim());
      if (perms.includes('3.5') || perms.includes('4') || perms.includes('5')) {
        document.getElementById('send-signature').disabled = false;
        $('#send-signature').show(); $('#signature-save').show(); $('#insert-pages').show();
      } else {
        $('#sendTo').show();
      }
    }
    if (status == STATUS.SIGNED) {
      const perms = permission.split(',').map(p => p.trim());
      if (!perms.includes('3.5') && !perms.includes('4') && !perms.includes('5')) {
        document.getElementById('send-signature').disabled = false;
        $('#send-signature').show(); $('#signature-save').show();
      } else {
        $('#send-to').show(); $('#send-save').show();
      }
    }
    if (status == STATUS.SENT) { $('#send-to').show(); $('#send-save').show(); }
    if (status == STATUS.DIRECTORY) {
      document.getElementById('directory-save').disabled = false; $('#directory-save').show();
    }

    $.get('/book/created_position/' + id, function(res) {
      if (status >= STATUS.ADMIN_PROCESS && status < STATUS.ARCHIVED && position_id != res.position_id) {
        document.getElementById('reject-book').disabled = false; $('#reject-book').show();
      }
    });

    resetMarking();
    removeMarkListener();
  }

  function removeMarkListener(){
    var markCanvas = document.getElementById('mark-layer');
    var markCanvasInsert = document.getElementById('mark-layer-insert');
    if (markEventListener){ markCanvas.removeEventListener('click', markEventListener); markEventListener = null; }
    if (markEventListenerInsert){ markCanvasInsert.removeEventListener('click', markEventListenerInsert); markEventListenerInsert = null; }
  }

  function resetMarking(){
    var markCanvas = document.getElementById('mark-layer');
    var markCanvasInsert = document.getElementById('mark-layer-insert');
    var markCtx = markCanvas.getContext('2d');
    var markCtxInsert = markCanvasInsert.getContext('2d');
    markCtx.clearRect(0,0,markCanvas.width,markCanvas.height);
    markCtxInsert.clearRect(0,0,markCanvasInsert.width,markCanvasInsert.height);
  }

  selectPageTable.addEventListener('change', function(){ ajaxTable(parseInt(this.value)); });
  function onNextPageTable(){ if(pageNumTalbe>=pageTotal)return; pageNumTalbe++; selectPageTable.value=pageNumTalbe; ajaxTable(pageNumTalbe); }
  function onPrevPageTable(){ if(pageNumTalbe<=1)return; pageNumTalbe--; selectPageTable.value=pageNumTalbe; ajaxTable(pageNumTalbe); }
  document.getElementById('nextPage').addEventListener('click', onNextPageTable);
  document.getElementById('prevPage').addEventListener('click', onPrevPageTable);

  function ajaxTable(pages){
    $('#id').val(''); $('#positionX').val(''); $('#positionY').val(''); $('#txt_label').text(''); $('#users_id').val('');
    document.getElementById('add-stamp').disabled=false; document.getElementById('save-stamp').disabled=true; document.getElementById('send-save').disabled=true;
    $.ajax({
      type:'post', url:'/book/dataList', data:{pages:pages},
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, dataType:'json',
      success:function(res){
        if(res.status===true){
          $('#box-card-item').empty();
          $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
          res.book.forEach(el=>{
            var color = (el.type!=1)?'warning':'info';
            var text = '';
            if (el.status==14){ text=''; color='success'; }
            var html = '<a href="javascript:void(0)" onclick="openPdf('+"'"+el.url+"'"+','+"'"+el.id+"'"+','+"'"+el.status+"'"+','+"'"+el.type+"'"+','+"'"+el.is_number_stamp+"'"+','+"'"+el.inputBookregistNumber+"'"+','+"'"+el.position_id+"'"+')">'+
                       '<div class="card border-'+color+' mb-2"><div class="card-header text-dark fw-bold">'+el.inputSubject+text+'</div>'+
                       '<div class="card-body text-dark"><div class="row"><div class="col-9">'+el.selectBookFrom+'</div><div class="col-3 fw-bold">'+el.showTime+' น.</div></div></div></div></a>';
            $('#box-card-item').append(html);
          });
        }
      }
    });
  }

  $('#search_btn').click(function(e){
    e.preventDefault();
    $('#id').val(''); $('#positionX').val(''); $('#positionY').val(''); $('.btn-default').hide(); $('#txt_label').text(''); $('#users_id').val('');
    document.getElementById('add-stamp').disabled=false; document.getElementById('save-stamp').disabled=true; document.getElementById('send-save').disabled=true;
    $.ajax({
      type:'post', url:'/book/dataListSearch',
      data:{ pages:1, search:$('#inputSearch').val() },
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, dataType:'json',
      success:function(res){
        if(res.status===true){
          $('#box-card-item').html('');
          $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
          pageNumTalbe=1; pageTotal=res.totalPages;
          res.book.forEach(el=>{
            var color=(el.type!=1)?'warning':'info'; var text='';
            if(el.status==14){ text=''; color='success'; }
            var html = '<a href="javascript:void(0)" onclick="openPdf('+"'"+el.url+"'"+','+"'"+el.id+"'"+','+"'"+el.status+"'"+','+"'"+el.type+"'"+','+"'"+el.is_number_stamp+"'"+','+"'"+el.inputBookregistNumber+"'"+','+"'"+el.position_id+"'"+')">'+
                       '<div class="card border-'+color+' mb-2"><div class="card-header text-dark fw-bold">'+el.inputSubject+text+'</div>'+
                       '<div class="card-body text-dark"><div class="row"><div class="col-9">'+el.selectBookFrom+'</div><div class="col-3 fw-bold">'+el.showTime+' น.</div></div></div></div></a>';
            $('#box-card-item').append(html);
          });
          $("#page-select-card").empty();
          for (let i=1;i<=pageTotal;i++){ $('#page-select-card').append('<option value="'+i+'">'+i+'</option>'); }
        }
      }
    });
  });

  // Allow pressing Enter in the search box to trigger search
  $('#inputSearch').on('keydown', function(e){
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault();
      $('#search_btn').click();
    }
  });

  // SAVE stamp (แอดมิน)
  $('#save-stamp').click(function(e){
    e.preventDefault();
    var id=$('#id').val(), positionX=$('#positionX').val(), positionY=$('#positionY').val();
    var positionPages=$('#positionPages').val();
    var pages=$('#page-select').find(":selected").val();
    if (id && positionX!=='' && positionY!==''){
      Swal.fire({title:"ยืนยันการลงบันทึกเวลา",showCancelButton:true,confirmButtonText:"ตกลง",cancelButtonText:"ยกเลิก",icon:'question'})
      .then((r)=>{
        if(!r.isConfirmed) return;
        var boxW = markCoordinates.endX - markCoordinates.startX;
        var boxH = markCoordinates.endY - markCoordinates.startY;
        $.ajax({
          type:'post', url:'/book/admin_stamp',
          data:{ id, positionX, positionY, positionPages, pages, width:boxW, height:boxH },
          dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
          success:function(res){
            if(res.status){ Swal.fire("","บันทึกเรียบร้อย","success"); setTimeout(()=>location.reload(),1500); }
            else { Swal.fire("","บันทึกไม่สำเร็จ","error"); }
          }
        });
      });
    } else {
      Swal.fire("","กรุณาเลือกตำแหน่งของตราประทับ","info");
    }
  });

  // เลือกหน่วยงาน
  $('#sendTo').click(function(e){
    e.preventDefault();
    Swal.fire({
      title:'เลือกหน่วยงานที่ต้องการแทงเรื่อง',
      html:`<select id="select_position_id" name="states[]" multiple="multiple" class="swal2-input" style="width:80%;">@foreach($position as $key => $rec)<option value="{{$key}}">{{$rec}}</option>@endforeach</select>`,
      didOpen:()=>{ $('#select_position_id').select2({ dropdownParent: $('.swal2-container') }); },
      allowOutsideClick:false, focusConfirm:true, confirmButtonText:'ตกลง', showCancelButton:true, cancelButtonText:'ยกเลิก',
      preConfirm:()=>{ const v=$('#select_position_id').val(); if(!v){ Swal.showValidationMessage('ท่านยังไม่ได้เลือกหน่วยงาน'); } return v; }
    }).then((r)=>{
      if(!r.isConfirmed) return;
      var id=$('#id').val();
      $.ajax({
        type:'post', url:'/book/send_to_adminParent',
        data:{ id:id, position_id:r.value },
        dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        success:function(res){
          if(res.status){ Swal.fire("","แทงเรื่องเรียบร้อยแล้ว","success"); setTimeout(()=>location.reload(),1500); }
          else { Swal.fire("","แทงเรื่องไม่สำเร็จ","error"); }
        }
      });
    });
  });

  // เลือกผู้รับเป็นรายบุคคล
  $('#send-to').click(function(e){
    e.preventDefault();
    $.post('/book/checkbox_send', {_token:'{{ csrf_token() }}'}).done(function(html){
      Swal.fire({
        title:'แทงเรื่อง', html, showCancelButton:true, confirmButtonText:'ตกลง', cancelButtonText:'ยกเลิก', focusConfirm:true, allowOutsideClick:false,
        preConfirm:()=>{
          const ids = Array.from(document.querySelectorAll('input[name="flexCheckChecked[]"]:checked')).map(el=>el.value);
          if(ids.length===0){ Swal.showValidationMessage('กรุณาเลือกอย่างน้อย 1 คน'); }
          return ids;
        }
      }).then((r)=>{
        if(!r.isConfirmed) return;
        const id=$('#id').val(); const position_id=$('#position_id').val()||''; const users_id=r.value; const status=6;
        $.ajax({
          type:'post', url:'/book/send_to_save', dataType:'json',
          headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
          data:{ id, status, position_id, 'users_id[]': users_id }, traditional:true
        }).done(res=>{
          if(res.status){ Swal.fire('','แทงเรื่องเรียบร้อยแล้ว','success'); setTimeout(()=>location.reload(),1200); }
          else { Swal.fire('', res.message || 'แทงเรื่องไม่สำเร็จ', 'error'); }
        }).fail(()=> Swal.fire('','เกิดข้อผิดพลาดในการส่ง','error'));
      });
    }).fail(()=> Swal.fire('','โหลดรายชื่อผู้รับไม่สำเร็จ','error'));
  });

  // ส่งระบุ users_id
  $('#send-save').click(function(e){
    e.preventDefault();
    var id=$('#id').val(), users_id=$('#users_id').val();
    Swal.fire({title:"ยืนยันการแทงเรื่อง",showCancelButton:true,confirmButtonText:"ตกลง",cancelButtonText:"ยกเลิก",icon:'question'})
    .then((r)=>{
      if(!r.isConfirmed) return;
      $.ajax({
        type:'post', url:'/book/send_to_save', data:{ id, users_id, status:6 },
        dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        success:function(res){
          if(res.status){ Swal.fire("","แทงเรื่องเรียบร้อยแล้ว","success"); setTimeout(()=>location.reload(),1500); }
          else { Swal.fire("","แทงเรื่องไม่สำเร็จ","error"); }
        }
      });
    });
  });

  // บันทึกลายเซ็น (ลงเกษียณหนังสือ)
  $('#signature-save').click(function(e){
    e.preventDefault();
    var id=$('#id').val();
    var pages=$('#page-select').find(":selected").val();
    var positionPages=$('#positionPages').val();
    var text=$('#modal-text').val();
    var checkedValues=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();

    var textBox=null, imageBox=null, bottomBox=null;
    if (signatureCoordinates){
      if (signatureCoordinates.textBox)  textBox  = { startX:signatureCoordinates.textBox.startX,  startY:signatureCoordinates.textBox.startY,  endX:signatureCoordinates.textBox.endX,  endY:signatureCoordinates.textBox.endY };
      if (signatureCoordinates.bottomBox) bottomBox= { startX:signatureCoordinates.bottomBox.startX,startY:signatureCoordinates.bottomBox.startY,endX:signatureCoordinates.bottomBox.endX,endY:signatureCoordinates.bottomBox.endY };
      if (signatureCoordinates.imageBox)  imageBox = { startX:signatureCoordinates.imageBox.startX,  startY:signatureCoordinates.imageBox.startY,  endX:signatureCoordinates.imageBox.endX,  endY:signatureCoordinates.imageBox.endY };
    }

    var positionX=null, positionY=null, width=null, height=null;
    if (textBox){ positionX=textBox.startX; positionY=textBox.startY; width=textBox.endX-textBox.startX; height=textBox.endY-textBox.startY; }

    if (id && positionX!==null && positionY!==null){
      Swal.fire({title:"ยืนยันการลงเกษียณหนังสือ",showCancelButton:true,confirmButtonText:"ตกลง",cancelButtonText:"ยกเลิก",icon:'question'})
      .then((r)=>{
        if(!r.isConfirmed) return;
        var payload = {
          id, positionX, positionY, positionPages, pages, text, checkedValues, width, height
        };
        if (bottomBox){
          payload.bottomBox = {
            startX: bottomBox.startX, startY: bottomBox.startY,
            width:  bottomBox.endX - bottomBox.startX,
            height: bottomBox.endY - bottomBox.startY
          };
        }
        if (imageBox && checkedValues.includes('4')){
          payload.imageBox = {
            startX: imageBox.startX, startY: imageBox.startY,
            width:  imageBox.endX - imageBox.startX,
            height: imageBox.endY - imageBox.startY
          };
        }
        $.ajax({
          type:'post', url:'/book/signature_stamp', data:payload, dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
          success:function(res){
            if(res.status){ Swal.fire("","ลงบันทึกเกษียณหนังสือเรียบร้อย","success"); setTimeout(()=>location.reload(),1500); }
            else { Swal.fire("","บันทึกไม่สำเร็จ","error"); }
          }
        });
      });
    } else {
      Swal.fire("","กรุณาเลือกตำแหน่งเกษียณหนังสือ","info");
    }
  });

  function showCancelStampBtn(x,y){
    let btn=document.getElementById('cancel-stamp-btn');
    var markCanvas=document.getElementById('mark-layer');
    if(!btn){
      btn=document.createElement('button');
      btn.id='cancel-stamp-btn'; btn.className='btn btn-danger btn-sm'; btn.innerText='x';
      btn.style.position='fixed'; btn.style.zIndex=1000;
      btn.onclick=function(){
        var ctx=markCanvas.getContext('2d'); ctx.clearRect(0,0,markCanvas.width,markCanvas.height);
        removeMarkListener(); document.getElementById('add-stamp').disabled=false; document.getElementById('save-stamp').disabled=true; btn.remove();
      };
      document.body.appendChild(btn);
    }
    const rect=markCanvas.getBoundingClientRect();
    btn.style.left = (rect.left + x) + 'px';
    btn.style.top  = (rect.top  + y - 40) + 'px';
    btn.style.display='block';
  }
  function hideCancelStampBtn(){ let btn=document.getElementById('cancel-stamp-btn'); if(btn) btn.remove(); }
  document.addEventListener('DOMContentLoaded', function(){ hideCancelStampBtn(); });
  const _oldRemoveMarkListener = removeMarkListener;
  removeMarkListener = function(){ hideCancelStampBtn(); _oldRemoveMarkListener.apply(this, arguments); };

  $(document).ready(function(){
    $('#send-signature').click(function(e){ e.preventDefault(); });
    $('#insert-pages').click(function(e){ e.preventDefault(); $('#insert_tab').show(); });

    async function createAndRenderPDF() {
      const pdfDoc = await PDFLib.PDFDocument.create(); pdfDoc.addPage([600,800]);
      const pdfBytes = await pdfDoc.save();
      const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
      loadingTask.promise.then(pdf => pdf.getPage(1))
        .then(page=>{
          const scale=1.5, viewport=page.getViewport({scale});
          const canvas=document.getElementById("pdf-render-insert"); const ctx=canvas.getContext("2d");
          canvas.width=viewport.width; canvas.height=viewport.height;
          return page.render({canvasContext:ctx, viewport}).promise;
        }).catch(err=>console.error("Error rendering PDF:", err));
    }
    createAndRenderPDF();
  });

  $('#directory-save').click(function(e){
    e.preventDefault();
    Swal.fire({title:"",text:"ท่านต้องการจัดเก็บไฟล์นี้ใช่หรือไม่",icon:"question",showCancelButton:true,confirmButtonColor:"#3085d6",cancelButtonColor:"#d33",cancelButtonText:"ยกเลิก",confirmButtonText:"จัดเก็บ"})
    .then((r)=>{
      if(!r.isConfirmed) return;
      var id=$('#id').val();
      $.ajax({
        type:'post', url:'/book/directory_save', data:{id}, dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        success:function(res){
          if(res.status){ Swal.fire("","จัดเก็บเรียบร้อยแล้ว","success"); setTimeout(()=>location.reload(),1500); }
          else { Swal.fire("","จัดเก็บไม่สำเร็จ","error"); }
        }
      });
    });
  });

  $('#reject-book').click(function(e){
    e.preventDefault();
    Swal.fire({
      title:"", text:"ยืนยันการปฏิเสธหนังสือหรือไม่", icon:"warning",
      input:'textarea', inputPlaceholder:'กรอกเหตุผลการปฏิเสธ',
      showCancelButton:true, confirmButtonColor:"#3085d6", cancelButtonColor:"#d33",
      cancelButtonText:"ยกเลิก", confirmButtonText:"ตกลง",
      preConfirm:(note)=>{ if(!note){ Swal.showValidationMessage('กรุณากรอกเหตุผล'); } return note; }
    }).then((r)=>{
      if(!r.isConfirmed) return;
      var id=$('#id').val(), note=r.value;
      $.ajax({
        type:'post', url:'/book/reject', data:{id,note}, dataType:'json', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        success:function(res){
          if(res.status){ Swal.fire("","ปฏิเสธเรียบร้อย","success"); setTimeout(()=>location.reload(),1500); }
          else { Swal.fire("","ปฏิเสธไม่สำเร็จ","error"); }
        }
      });
    });
  });
</script>
@endsection
