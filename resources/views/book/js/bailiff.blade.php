@section('script')
<?php $position = [1 => 'สำนักงานปลัด', 2 => 'งานกิจการสภา', 3 => 'กองคลัง', 4 => 'กองช่าง', 5 => 'กองการศึกษา ศาสนาและวัฒนธรรม', 6 => 'ฝ่ายศูนย์รับเรื่องร้องเรียน-ร้องทุกข์', 7 => 'ฝ่ายเลือกตั้ง', 8 => 'ฝ่ายสปสช.', 9 => 'ศูนย์ข้อมูลข่าวสาร']; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    @include('book.js.constants')
    $('.btn-default').hide();

    // ===== ค่าพื้นฐาน/สถานะ =====
    var signature = '{{$signature}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;

    // ===== ตัวแปรใช้ร่วมสำหรับลาก/ย่อ-ขยาย =====
    var imgData = null;

    // พรีโหลดรูปเซ็น (สำหรับ checkbox=4)
    var signatureImg = new Image();
    var signatureImgLoaded = false;
    signatureImg.onload = function(){ signatureImgLoaded = true; };
    signatureImg.src = signature;

    // state สำหรับกล่องตรารับธรรมดา และลายเซ็น (แบ่งเป็นกล่อง text/bottom/image)
    var markCoordinates = null;
    var signatureCoordinates = null;

    let markEventListener = null;
    let markEventListenerInsert = null;

    // ===== helper วาดกรอบ + ข้อความ =====
    function drawBox(ctx, box, color, strokeColor, handleSize){
        ctx.save();
        ctx.strokeStyle = color;
        ctx.lineWidth = 0.5;
        ctx.strokeRect(box.startX, box.startY, box.endX - box.startX, box.endY - box.startY);
        // แฮนด์จับย่อ-ขยาย มุมขวาล่าง
        ctx.fillStyle = '#fff';
        ctx.strokeStyle = strokeColor;
        ctx.lineWidth = 2;
        ctx.fillRect(box.endX - handleSize, box.endY - handleSize, handleSize, handleSize);
        ctx.strokeRect(box.endX - handleSize, box.endY - handleSize, handleSize, handleSize);
        ctx.restore();
    }

    function drawTextCentered(ctx, font, box, text, lineHeight=20, offsetTop=20){
        ctx.font = font;
        ctx.fillStyle = "blue";
        var lines = String(text||'').split('\n');
        for (var i = 0; i < lines.length; i++) {
            var w = ctx.measureText(lines[i]).width;
            var x = (box.startX + box.endX)/2 - (w/2);
            var y = box.startY + offsetTop + (i * lineHeight);
            ctx.fillText(lines[i], x, y);
        }
    }

    function clamp(val, min, max){ return Math.max(min, Math.min(max, val)); }

    // ===== โหลด PDF และเตรียม canvas =====
    function pdf(url){
        var pdfDoc=null, pageNum=1, pageRendering=false, pageNumPending=null, scale=1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCtx    = pdfCanvas.getContext('2d'),
            markCanvas= document.getElementById('mark-layer'),
            selectPage= document.getElementById('page-select');

        document.getElementById('add-stamp').disabled   = true;
        document.getElementById('save-stamp').disabled  = true;
        document.getElementById('signature-save').disabled = true;

        function renderPage(num){
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page){
                let viewport = page.getViewport({ scale: scale });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width  = viewport.width;
                markCanvas.height= viewport.height;
                markCanvas.width = viewport.width;

                let renderTask = page.render({ canvasContext: pdfCtx, viewport: viewport });
                renderTask.promise.then(function(){
                    pageRendering = false;
                    if (pageNumPending !== null){
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
            selectPage.value = num;
        }

        function queueRenderPage(num){
            if (pageRendering){ pageNumPending = num; }
            else { renderPage(num); }
        }

        // เลือกหน้า
        selectPage.addEventListener('change', function(){
            let p = parseInt(this.value);
            if (p && p >= 1 && p <= pdfDoc.numPages){ pageNum = p; queueRenderPage(p); }
        });

        // โหลดเอกสาร
        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_){
            pdfDoc = pdfDoc_;
            // เติม dropdown หน้า
            for (let i=1;i<=pdfDoc.numPages;i++){
                let op = document.createElement('option'); op.value=i; op.textContent=i;
                selectPage.appendChild(op);
            }
            renderPage(pageNum);
            document.getElementById('add-stamp').disabled = false; // เปิดปุ่มวางตรารับ
        });

        // ปุ่มถัดไป/ก่อนหน้า
        document.getElementById('next').addEventListener('click', function(){
            if (pageNum < pdfDoc.numPages){ pageNum++; queueRenderPage(pageNum); }
        });
        document.getElementById('prev').addEventListener('click', function(){
            if (pageNum > 1){ pageNum--; queueRenderPage(pageNum); }
        });

        // ====== วาง “ตรารับ” (กล่องเดียว) แบบลาก/ย่อ/ขยาย ======
        $('#add-stamp').off('click').on('click', function(e){
            e.preventDefault();
            removeMarkListener();
            this.disabled = true;
            document.getElementById('save-stamp').disabled = false;

            var canvas = document.getElementById('mark-layer');
            var ctx    = canvas.getContext('2d');

            // กล่องเริ่มต้นกึ่งกลาง
            var defaultW = 220, defaultH = 115;
            var sx = (canvas.width - defaultW)/2;
            var sy = (canvas.height - defaultH)/2;
            var ex = sx + defaultW, ey = sy + defaultH;

            markCoordinates = { startX:sx, startY:sy, endX:ex, endY:ey };
            $('#positionX').val(sx);
            $('#positionY').val(sy);
            $('#positionPages').val(1);

            // วาดกรอบ + ข้อความหัวตรา
            function redrawStampBox(){
                ctx.clearRect(0,0,canvas.width,canvas.height);
                var boxW = markCoordinates.endX - markCoordinates.startX;
                var boxH = markCoordinates.endY - markCoordinates.startY;
                var scaleW = boxW / defaultW, scaleH = boxH / defaultH;
                var scale = clamp(Math.min(scaleW, scaleH), 0.5, 2.5);

                // กล่อง
                drawBox(ctx, markCoordinates, 'blue', '#007bff', 16);

                // ข้อความในตรา (หัว + 3 บรรทัด)
                var posName = '{{$position_name ?? ""}}';
                var dynX;
                if (posName.length >= 30)      dynX = 5*scale;
                else if (posName.length >= 20) dynX = 10*scale;
                else if (posName.length >= 15) dynX = 60*scale;
                else if (posName.length >= 13) dynX = 75*scale;
                else if (posName.length >= 10) dynX = 70*scale;
                else                            dynX = 80*scale;

                ctx.font = (15*scale).toFixed(1)+'px Sarabun';
                ctx.fillStyle = 'blue';
                ctx.fillText(posName, markCoordinates.startX + dynX, markCoordinates.startY + 25*scale);

                ctx.font = (12*scale).toFixed(1)+'px Sarabun';
                ctx.fillText('รับที่..........................................................', markCoordinates.startX + 8*scale, markCoordinates.startY + 55*scale);
                ctx.fillText('วันที่.........เดือน......................พ.ศ.........',       markCoordinates.startX + 8*scale, markCoordinates.startY + 80*scale);
                ctx.fillText('เวลา......................................................น.',  markCoordinates.startX + 8*scale, markCoordinates.startY + 100*scale);

                // ปุ่มยกเลิกมุมบนซ้ายของกล่อง
                showCancelStampBtn(markCoordinates.endX, markCoordinates.startY);
            }
            redrawStampBox();

            // ลาก/ย่อ-ขยาย
            var dragging=false, resizing=false, active=null, dx=0, dy=0, hsize=16;
            function isOnHandle(mx,my,box){
                return (mx >= box.endX-hsize && mx <= box.endX && my >= box.endY-hsize && my <= box.endY);
            }
            function inBox(mx,my,box){
                return (mx>=box.startX && mx<=box.endX && my>=box.startY && my<=box.endY);
            }

            canvas.addEventListener('mousemove', function(e){
                var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                if (isOnHandle(mx,my, markCoordinates)) canvas.style.cursor='se-resize';
                else if (inBox(mx,my, markCoordinates)) canvas.style.cursor='move';
                else canvas.style.cursor='default';
            });

            canvas.onmousedown = function(e){
                var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                if (isOnHandle(mx,my, markCoordinates)){
                    resizing = true; e.preventDefault();
                    window.addEventListener('mousemove', onResizeMove);
                    window.addEventListener('mouseup',   onResizeEnd);
                }else if (inBox(mx,my, markCoordinates)){
                    dragging = true;
                    dx = mx - markCoordinates.startX; dy = my - markCoordinates.startY;
                    e.preventDefault();
                    window.addEventListener('mousemove', onDragMove);
                    window.addEventListener('mouseup',   onDragEnd);
                }
            };

            function onDragMove(e){
                if (!dragging) return;
                var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                var w = markCoordinates.endX - markCoordinates.startX;
                var h = markCoordinates.endY - markCoordinates.startY;
                var nsx = clamp(mx - dx, 0, canvas.width - w);
                var nsy = clamp(my - dy, 0, canvas.height- h);
                markCoordinates.startX = nsx;
                markCoordinates.startY = nsy;
                markCoordinates.endX   = nsx + w;
                markCoordinates.endY   = nsy + h;
                $('#positionX').val(nsx);
                $('#positionY').val(nsy);
                redrawStampBox();
            }
            function onDragEnd(){ dragging=false; window.removeEventListener('mousemove', onDragMove); window.removeEventListener('mouseup', onDragEnd); }

            function onResizeMove(e){
                if (!resizing) return;
                var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                var minW=40,minH=30;
                markCoordinates.endX = clamp(Math.max(markCoordinates.startX + minW, mx), 0, canvas.width);
                markCoordinates.endY = clamp(Math.max(markCoordinates.startY + minH, my), 0, canvas.height);
                redrawStampBox();
            }
            function onResizeEnd(){ resizing=false; window.removeEventListener('mousemove', onResizeMove); window.removeEventListener('mouseup', onResizeEnd); }
        });

        // ยืนยันรหัสผ่านเพื่อเปิด “โหมดลายเซ็น” (ลาก/ย่อ-ขยาย 2-3 กล่อง)
        $('#modalForm').off('submit').on('submit', function(e){
            e.preventDefault();
            var formData = new FormData(this);
            $('#exampleModal').modal('hide');
            Swal.showLoading();
            $.ajax({
                type: "post",
                url: "{{ route('book.confirm_signature') }}",
                data: formData, dataType: "json",
                contentType: false, processData: false,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).done(function(res){
                if (!res.status){ Swal.fire("", res.message||"ยืนยันไม่สำเร็จ", "error"); return; }
                Swal.close();
                document.getElementById('signature-save').disabled = false;

                // คลิกเพื่อวางกล่องลายเซ็น (หน้าเอกสารจริง)
                markEventListener = function(){
                    var canvas = document.getElementById('mark-layer');
                    var ctx    = canvas.getContext('2d');

                    // สร้าง 3 กล่องครั้งแรก
                    if (!signatureCoordinates){
                        var tw=220, th=40, bh=80, iw=240, ih=130;
                        var sx=(canvas.width - tw)/2, sy=(canvas.height - (th+bh+ih+40))/2;
                        signatureCoordinates = {
                            textBox:  { startX:sx, startY:sy,          endX:sx+tw,     endY:sy+th,              type:'text'   },
                            bottomBox:{ startX:sx, startY:sy+th+10,     endX:sx+tw,     endY:sy+th+10+bh,        type:'bottom' },
                            imageBox: { startX:sx-13, startY:sy+th+bh+20,endX:sx+iw-13, endY:sy+th+bh+20+ih,     type:'image'  }
                        };
                        $('#positionX').val(sx);
                        $('#positionY').val(sy);
                        $('#positionPages').val(1);
                    }

                    redrawSignatureBoxes();

                    var dragging=false, resizing=false, active=null, dx=0, dy=0, hsize=16;
                    function redrawSignatureBoxes(){
                        ctx.clearRect(0,0,canvas.width,canvas.height);
                        var text = $('#modal-text').val();
                        var checks = $('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
                        var hasImg = checks.includes('4');

                        // textBox
                        var tBox = signatureCoordinates.textBox;
                        drawBox(ctx, tBox, 'blue', '#007bff', hsize);
                        var tScale = clamp(Math.min((tBox.endX-tBox.startX)/220, (tBox.endY-tBox.startY)/40), 0.5, 2.5);
                        drawTextCentered(ctx, (15*tScale).toFixed(1)+'px Sarabun', tBox, text, 20, 20);

                        // bottomBox
                        var bBox = signatureCoordinates.bottomBox;
                        drawBox(ctx, bBox, 'purple', '#6f42c1', hsize);
                        var bScale = clamp(Math.min((bBox.endX-bBox.startX)/220, (bBox.endY-bBox.startY)/80), 0.5, 2.5);
                        var lines = [];
                        checks.forEach(v=>{
                            if (v==='1') lines.push(`({{$users->fullname}})`);
                            if (v==='2') lines.push(`{{$permission_data->permission_name}}`);
                            if (v==='3') lines.push(`{{ convertDateToThai(date('Y-m-d')) }}`);
                        });
                        lines.forEach((ln,i)=> drawTextCentered(ctx, (15*bScale).toFixed(1)+'px Sarabun', bBox, ln, 20, 25*bScale + (20*i*bScale)));

                        // imageBox
                        if (hasImg){
                            var iBox = signatureCoordinates.imageBox;
                            drawBox(ctx, iBox, 'green', '#28a745', hsize);
                            var iw = iBox.endX - iBox.startX, ih = iBox.endY - iBox.startY;
                            if (signatureImgLoaded){
                                ctx.drawImage(signatureImg, iBox.startX, iBox.startY, iw, ih);
                                imgData = { x:iBox.startX, y:iBox.startY, width:iw, height:ih };
                            }
                        }
                    }

                    function isOnHandle(mx,my,box){
                        return (mx>=box.endX-16 && mx<=box.endX && my>=box.endY-16 && my<=box.endY);
                    }
                    function inBox(mx,my,box){
                        return (mx>=box.startX && mx<=box.endX && my>=box.startY && my<=box.endY);
                    }
                    function getActive(mx,my){
                        var checks = $('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();
                        var hasImg = checks.includes('4');
                        if (inBox(mx,my, signatureCoordinates.bottomBox)) return signatureCoordinates.bottomBox;
                        if (hasImg && inBox(mx,my, signatureCoordinates.imageBox)) return signatureCoordinates.imageBox;
                        if (inBox(mx,my, signatureCoordinates.textBox)) return signatureCoordinates.textBox;
                        return null;
                    }

                    canvas.addEventListener('mousemove', function(e){
                        var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                        if (isOnHandle(mx,my, signatureCoordinates.textBox) ||
                            isOnHandle(mx,my, signatureCoordinates.bottomBox) ||
                            isOnHandle(mx,my, signatureCoordinates.imageBox)) canvas.style.cursor='se-resize';
                        else if (getActive(mx,my)) canvas.style.cursor='move';
                        else canvas.style.cursor='default';
                    });

                    canvas.onmousedown = function(e){
                        var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                        // ลำดับ: จับมุมก่อน > ลาก
                        if (isOnHandle(mx,my, signatureCoordinates.textBox)){ active=signatureCoordinates.textBox; resizing=true; }
                        else if (isOnHandle(mx,my, signatureCoordinates.bottomBox)){ active=signatureCoordinates.bottomBox; resizing=true; }
                        else if (isOnHandle(mx,my, signatureCoordinates.imageBox)){ active=signatureCoordinates.imageBox; resizing=true; }
                        else { active = getActive(mx,my); if (active){ dragging=true; dx=mx-active.startX; dy=my-active.startY; } }
                        if (dragging || resizing){ e.preventDefault(); window.addEventListener('mousemove', onMove); window.addEventListener('mouseup', onUp); }
                    };

                    function onMove(e){
                        var r=canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
                        if (dragging && active){
                            var w=active.endX-active.startX, h=active.endY-active.startY;
                            var nsx = clamp(mx - dx, 0, canvas.width - w);
                            var nsy = clamp(my - dy, 0, canvas.height- h);
                            active.startX = nsx; active.startY = nsy; active.endX = nsx + w; active.endY = nsy + h;
                            if (active.type==='text'){ $('#positionX').val(nsx); $('#positionY').val(nsy); }
                            redrawSignatureBoxes();
                        } else if (resizing && active){
                            var minW=40,minH=30;
                            active.endX = clamp(Math.max(active.startX + minW, mx), 0, canvas.width);
                            active.endY = clamp(Math.max(active.startY + minH, my), 0, canvas.height);
                            redrawSignatureBoxes();
                        }
                    }
                    function onUp(){ dragging=false; resizing=false; active=null; window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); }
                };
                document.getElementById('mark-layer').addEventListener('click', markEventListener);
            });
        });
    }

    // ===== ปุ่มเปิด PDF จากการ์ด =====
    function openPdf(url, id, status, type, is_check, number_id, position_id){
        $('.btn-default').hide();
        document.getElementById('reject-book').disabled   = true;
        document.getElementById('add-stamp').disabled     = false;
        document.getElementById('save-stamp').disabled    = true;
        document.getElementById('signature-save').disabled= true;
        document.getElementById('send-save').disabled     = true;

        // เคลียร์ canvas แล้วโหลดใหม่
        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
        pdf(url);

        // ตั้งค่า hidden fields
        $('#id').val(id);
        $('#position_id').val(position_id);
        $('#positionX').val(''); $('#positionY').val(''); $('#positionPages').val('');
        $('#txt_label').text(''); $('#users_id').val('');

        // แสดงปุ่มตามสถานะ bailiff
        if (status == STATUS.ADMIN_PROCESS){
            $('#insert-pages').show(); $('#add-stamp').show(); $('#save-stamp').show();
        }
        if (status == STATUS.WAITING_SIGNATURE){
            $('#insert-pages').show(); $('#signature-save').show(); $('#send-to').show();
        }
        if (status == STATUS.SIGNED){
            $('#send-to').show(); $('#send-save').show();
        }
        if (status == STATUS.DIRECTORY){
            document.getElementById('directory-save').disabled = false;
            $('#directory-save').show();
        }

        // อนุญาต reject ได้เฉพาะคนละตำแหน่งผู้สร้าง
        $.get("{{ route('book.created_position', ':id') }}".replace(':id', id), function(res){
            if (status >= STATUS.ADMIN_PROCESS && status < STATUS.ARCHIVED && position_id != res.position_id){
                document.getElementById('reject-book').disabled = false;
                $('#reject-book').show();
            }
        });

        resetMarking();
        removeMarkListener();
    }

    // ===== จัดการลบลิสเนอร์/รีเซ็ตวาด =====
    function removeMarkListener(){
        var c1=document.getElementById('mark-layer'), c2=document.getElementById('mark-layer-insert');
        if (markEventListener && c1){ c1.removeEventListener('click', markEventListener); markEventListener=null; }
        if (markEventListenerInsert && c2){ c2.removeEventListener('click', markEventListenerInsert); markEventListenerInsert=null; }
        hideCancelStampBtn();
    }
    function resetMarking(){
        ['mark-layer', 'mark-layer-insert'].forEach(id=>{
            var c=document.getElementById(id); if(!c) return;
            var ctx=c.getContext('2d'); ctx.clearRect(0,0,c.width,c.height);
        });
        markCoordinates = null; signatureCoordinates = null;
    }

    // ===== ปุ่มยกเลิกกรอบตรา (เล็กๆ ติดกรอบ) =====
    function showCancelStampBtn(x, y){
        let btn=document.getElementById('cancel-stamp-btn');
        var canvas=document.getElementById('mark-layer');
        if(!btn){
            btn=document.createElement('button');
            btn.id='cancel-stamp-btn'; btn.className='btn btn-danger btn-sm'; btn.innerText='x';
            btn.style.position='fixed'; btn.style.zIndex=1000;
            btn.onclick=function(){
                var ctx=canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height);
                removeMarkListener();
                document.getElementById('add-stamp').disabled=false;
                document.getElementById('save-stamp').disabled=true;
                btn.remove();
            };
            document.body.appendChild(btn);
        }
        const rect=canvas.getBoundingClientRect();
        btn.style.left = (rect.left + x) + 'px';
        btn.style.top  = (rect.top  + y - 40) + 'px';
        btn.style.display='block';
    }
    function hideCancelStampBtn(){ let btn=document.getElementById('cancel-stamp-btn'); if(btn) btn.remove(); }

    // ===== เปลี่ยนหน้า list การ์ด =====
    selectPageTable.addEventListener('change', function(){ ajaxTable(parseInt(this.value)); });
    document.getElementById('nextPage').addEventListener('click', function(){
        if (pageNumTalbe < pageTotal){ pageNumTalbe++; selectPageTable.value = pageNumTalbe; ajaxTable(pageNumTalbe); }
    });
    document.getElementById('prevPage').addEventListener('click', function(){
        if (pageNumTalbe > 1){ pageNumTalbe--; selectPageTable.value = pageNumTalbe; ajaxTable(pageNumTalbe); }
    });

    function ajaxTable(pages){
        $('#id,#positionX,#positionY,#users_id').val('');
        $('#txt_label').text('');
        document.getElementById('add-stamp').disabled=false;
        document.getElementById('save-stamp').disabled=true;
        document.getElementById('send-save').disabled=true;

        $.ajax({
            type:"post",
            url:"{{ route('book.dataList') }}",
            headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
            data:{ pages: pages }, dataType:"json"
        }).done(function(res){
            if (!res.status) return;
            $('#box-card-item').empty();
            $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
            res.book.forEach(el=>{
                var color = (el.type!=1) ? 'warning':'info';
                if (el.status==14){ color='success'; }
                var html = `<a href="javascript:void(0)" onclick="openPdf('${el.url}','${el.id}','${el.status}','${el.type}','${el.is_number_stamp}','${el.inputBookregistNumber}','${el.position_id}')">
                    <div class="card border-${color} mb-2">
                        <div class="card-header text-dark fw-bold">${el.inputSubject}</div>
                        <div class="card-body text-dark"><div class="row"><div class="col-9">${el.selectBookFrom}</div><div class="col-3 fw-bold">${el.showTime} น.</div></div></div>
                    </div></a>`;
                $('#box-card-item').append(html);
            });
        });
    }

    // ===== ค้นหาหนังสือ =====
    $('#search_btn').on('click', function(e){
        e.preventDefault();
        $('#id,#positionX,#positionY,#users_id').val('');
        $('.btn-default').hide();
        $('#txt_label').text('');
        document.getElementById('add-stamp').disabled=false;
        document.getElementById('save-stamp').disabled=true;
        document.getElementById('send-save').disabled=true;

        $.ajax({
            type:"post",
            url:"{{ route('book.dataListSearch') }}",
            headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
            data:{ pages:1, search: $('#inputSearch').val() }, dataType:"json"
        }).done(function(res){
            if (!res.status) return;
            $('#box-card-item').empty();
            $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
            pageNumTalbe = 1; pageTotal = res.totalPages;
            res.book.forEach(el=>{
                var color=(el.type!=1)?'warning':'info'; if (el.status==14){ color='success'; }
                var html = `<a href="javascript:void(0)" onclick="openPdf('${el.url}','${el.id}','${el.status}','${el.type}','${el.is_number_stamp}','${el.inputBookregistNumber}','${el.position_id}')">
                    <div class="card border-${color} mb-2">
                        <div class="card-header text-dark fw-bold">${el.inputSubject}</div>
                        <div class="card-body text-dark"><div class="row"><div class="col-9">${el.selectBookFrom}</div><div class="col-3 fw-bold">${el.showTime} น.</div></div></div>
                    </div></a>`;
                $('#box-card-item').append(html);
            });
            $("#page-select-card").empty();
            for (let i=1;i<=pageTotal;i++){ $('#page-select-card').append('<option value="'+i+'">'+i+'</option>'); }
        });
    });

    // ===== บันทึก “ตรารับ” (ตำแหน่ง/ขนาด) ไปหลังบ้าน =====
    $('#save-stamp').on('click', function(e){
        e.preventDefault();
        var id=$('#id').val(), x=$('#positionX').val(), y=$('#positionY').val(), positionPages=$('#positionPages').val(),
            pages=$('#page-select').val();
        if (!id || x==='' || y===''){ Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info"); return; }

        Swal.fire({ title:"ยืนยันการลงบันทึกเวลา", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
        .then((r)=>{
            if (!r.isConfirmed) return;
            var w = markCoordinates ? (markCoordinates.endX - markCoordinates.startX) : null;
            var h = markCoordinates ? (markCoordinates.endY - markCoordinates.startY) : null;
            $.ajax({
                type:"post",
                url:"{{ route('book.admin_stamp') }}",
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                data:{ id:id, positionX:x, positionY:y, positionPages:positionPages, pages:pages, width:w, height:h },
                dataType:"json"
            }).done(function(res){
                if (res.status){ Swal.fire("","บันทึกเรียบร้อย","success"); setTimeout(()=>location.reload(),1200); }
                else { Swal.fire("","บันทึกไม่สำเร็จ","error"); }
            });
        });
    });

    // ===== ส่งต่อ: เปิดรายการ checkbox ผู้รับ (ใช้ route) =====
    $('#send-to').on('click', function(e){
        e.preventDefault();
        $.post("{{ route('book.checkbox_send') }}", {_token:'{{ csrf_token() }}'}).done(function(html){
            Swal.fire({
                title:'แทงเรื่อง', html:html, showCancelButton:true,
                confirmButtonText:'ตกลง', cancelButtonText:'ยกเลิก',
                allowOutsideClick:false, focusConfirm:true,
                preConfirm:()=>{
                    const ids = Array.from(document.querySelectorAll('input[name="flexCheckChecked[]"]:checked')).map(el=>el.value);
                    if (ids.length===0){ Swal.showValidationMessage('กรุณาเลือกอย่างน้อย 1 คน'); }
                    return ids;
                }
            }).then((r)=>{
                if (!r.isConfirmed) return;
                const idList = r.value;
                const idStr = idList.join(',');
                const textList = idList.map(id=>{
                    const lab = document.querySelector('input[name="flexCheckChecked[]"][value="'+id+'"] + label');
                    return lab ? lab.textContent.trim(): id;
                });
                $('#users_id').val(idStr);
                $('#txt_label').text('- แทงเรื่อง ('+textList.join(', ')+') -');
                document.getElementById('send-save').disabled = false;
            });
        }).fail(()=> Swal.fire('', 'โหลดรายชื่อผู้รับไม่สำเร็จ', 'error'));
    });

    // ===== ยืนยันส่งต่อ =====
    $('#send-save').on('click', function(e){
        e.preventDefault();
        var id=$('#id').val(), users_id=$('#users_id').val(), position_id=$('#position_id').val();
        Swal.fire({ title:"ยืนยันการแทงเรื่อง", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
        .then((r)=>{
            if (!r.isConfirmed) return;
            $.ajax({
                type:"post",
                url:"{{ route('book.send_to_save') }}",
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                data:{ id:id, users_id:users_id, status:6, position_id:position_id }, dataType:"json"
            }).done((res)=>{
                if (res.status){ Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success"); setTimeout(()=>location.reload(),1200); }
                else { Swal.fire("", res.message||"แทงเรื่องไม่สำเร็จ", "error"); }
            });
        });
    });

    // ===== บันทึกลายเซ็น (ส่งพิกัด/กล่องทั้งหมด) =====
    $('#signature-save').on('click', function(e){
        e.preventDefault();
        var id=$('#id').val(), pages=$('#page-select').val(), positionPages=$('#positionPages').val(),
            text=$('#modal-text').val(), checks=$('input[type="checkbox"]:checked').map(function(){return $(this).val();}).get();

        // textBox จำเป็นต้องมี
        if (!signatureCoordinates || !signatureCoordinates.textBox){ Swal.fire("", "กรุณาวางกล่องลายเซ็น", "info"); return; }

        var textBox  = signatureCoordinates.textBox;
        var bottomBox= signatureCoordinates.bottomBox || null;
        var imageBox = signatureCoordinates.imageBox  || null;

        var payload = {
            id: id,
            positionX: textBox.startX,
            positionY: textBox.startY,
            positionPages: positionPages || 1,
            pages: pages,
            text: text,
            checkedValues: checks,
            width: (textBox.endX - textBox.startX),
            height:(textBox.endY - textBox.startY)
        };
        if (bottomBox){
            payload.bottomBox = {
                startX: bottomBox.startX,
                startY: bottomBox.startY,
                width:  bottomBox.endX - bottomBox.startX,
                height: bottomBox.endY - bottomBox.startY
            };
        }
        if (imageBox && checks.includes('4')){
            payload.imageBox = {
                startX: imageBox.startX,
                startY: imageBox.startY,
                width:  imageBox.endX - imageBox.startX,
                height: imageBox.endY - imageBox.startY
            };
        }

        Swal.fire({ title:"ยืนยันการลงเกษียณหนังสือ", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
        .then((r)=>{
            if (!r.isConfirmed) return;
            $.ajax({
                type:"post",
                url:"{{ route('book.signature_stamp') }}",
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                data: payload, dataType:"json"
            }).done(function(res){
                if (res.status){ Swal.fire("", "ลงบันทึกเกษียณหนังสือเรียบร้อย", "success"); setTimeout(()=>location.reload(),1200); }
                else { Swal.fire("", "บันทึกไม่สำเร็จ", "error"); }
            });
        });
    });

    // ===== จัดเก็บเข้าแฟ้ม/ไดเรกทอรี =====
    $('#directory-save').on('click', function(e){
        e.preventDefault();
        var id=$('#id').val();
        Swal.fire({ title:"", text:"ท่านต้องการจัดเก็บไฟล์นี้ใช่หรือไม่", icon:"question",
            showCancelButton:true, confirmButtonText:"จัดเก็บ", cancelButtonText:"ยกเลิก" })
        .then((r)=>{
            if (!r.isConfirmed) return;
            $.ajax({
                type:"post",
                url:"{{ route('book.directory_save') }}",
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                data:{ id:id }, dataType:"json"
            }).done(function(res){
                if (res.status){ Swal.fire("", "จัดเก็บเรียบร้อยแล้ว", "success"); setTimeout(()=>location.reload(),1200); }
                else { Swal.fire("", "จัดเก็บไม่สำเร็จ", "error"); }
            });
        });
    });

    // ===== ปฏิเสธหนังสือ =====
    $('#reject-book').on('click', function(e){
        e.preventDefault();
        Swal.fire({
            title:"", text:"ยืนยันการปฏิเสธหนังสือหรือไม่", icon:"warning",
            input:'textarea', inputPlaceholder:'กรอกเหตุผลการปฏิเสธ',
            showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก",
            preConfirm: (note)=>{ if(!note){ Swal.showValidationMessage('กรุณากรอกเหตุผล'); } return note; }
        }).then((r)=>{
            if (!r.isConfirmed) return;
            $.ajax({
                type:"post",
                url:"{{ route('book.reject') }}",
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                data:{ id: $('#id').val(), note: r.value }, dataType:"json"
            }).done(function(res){
                if (res.status){ Swal.fire("", "ปฏิเสธเรียบร้อย", "success"); setTimeout(()=>location.reload(),1200); }
                else { Swal.fire("", "ปฏิเสธไม่สำเร็จ", "error"); }
            });
        });
    });

    // ===== เปิดแท็บ Insert Pages (ถ้าต้องใช้) =====
    $(document).ready(function(){
        $('#insert-pages').on('click', function(e){ e.preventDefault(); $('#insert_tab').show(); });

        // สร้าง PDF เปล่าและแสดงใน canvas insert
        (async function createAndRenderPDF(){
            const pdfDoc = await PDFLib.PDFDocument.create(); pdfDoc.addPage([600,800]);
            const pdfBytes = await pdfDoc.save();
            const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
            loadingTask.promise.then(pdf => pdf.getPage(1)).then(page=>{
                const scale=1.5, viewport=page.getViewport({scale});
                const canvas=document.getElementById('pdf-render-insert'), ctx=canvas.getContext('2d');
                canvas.width = viewport.width; canvas.height = viewport.height;
                return page.render({canvasContext:ctx, viewport}).promise;
            });
        })();
    });
</script>
@endsection
