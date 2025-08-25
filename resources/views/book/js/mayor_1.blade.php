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

// === พรีโหลดรูปเซ็น สำหรับ checkbox "ลายเซ็น" (ค่า 4)
var signatureImg = new Image();
var signatureImgLoaded = false;
signatureImg.onload = function(){ signatureImgLoaded = true; };
signatureImg.src = signature;

// === state สำหรับลายเส้น/กล่อง
var markEventListener = null;            // listener คลิกเพื่อเริ่มวางกล่อง
var signatureCoordinates = null;         // { textBox:{...}, imageBox:{...} }
var HANDLE = 16;

// ===== helper =====
function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }
function drawBox(ctx, box, color, strokeColor){
  ctx.save();
  ctx.strokeStyle = color;
  ctx.lineWidth = .5;
  ctx.strokeRect(box.startX, box.startY, box.endX-box.startX, box.endY-box.startY);
  // handle มุมล่างขวา
  ctx.fillStyle = '#fff';
  ctx.strokeStyle = strokeColor;
  ctx.lineWidth = 2;
  ctx.fillRect(box.endX-HANDLE, box.endY-HANDLE, HANDLE, HANDLE);
  ctx.strokeRect(box.endX-HANDLE, box.endY-HANDLE, HANDLE, HANDLE);
  ctx.restore();
}
function drawTextCentered(ctx, font, box, text, lineHeight=20, offsetTop=20){
  ctx.font = font;
  ctx.fillStyle = "blue";
  var lines = String(text||'').split('\n');
  for (let i=0;i<lines.length;i++){
    const w = ctx.measureText(lines[i]).width;
    const x = (box.startX + box.endX)/2 - w/2;
    const y = box.startY + offsetTop + (i*lineHeight);
    ctx.fillText(lines[i], x, y);
  }
}
function isOnHandle(mx,my,box){
  return (mx>=box.endX-HANDLE && mx<=box.endX && my>=box.endY-HANDLE && my<=box.endY);
}
function inBox(mx,my,box){
  return (mx>=box.startX && mx<=box.endX && my>=box.startY && my<=box.endY);
}
function countLineBreaks(text){ return String(text||'').split('\n').length - 1; }

// ===== PDF viewer (ย่อ) =====
function pdf(url) {
  var pdfDoc=null, pageNum=1, pageRendering=false, pageNumPending=null, scale=1.5,
      pdfCanvas=document.getElementById('pdf-render'),
      pdfCtx=pdfCanvas.getContext('2d'),
      markCanvas=document.getElementById('mark-layer'),
      selectPage=document.getElementById('page-select');

  document.getElementById('manager-save').disabled = true;

  function renderPage(num){
    pageRendering=true;
    pdfDoc.getPage(num).then(function(page){
      let viewport = page.getViewport({ scale });
      pdfCanvas.height=viewport.height; pdfCanvas.width=viewport.width;
      markCanvas.height=viewport.height; markCanvas.width=viewport.width;
      return page.render({ canvasContext: pdfCtx, viewport }).promise;
    }).then(function(){
      pageRendering=false;
      if (pageNumPending!==null){ renderPage(pageNumPending); pageNumPending=null; }
    });
    selectPage.value=num;
  }
  function queueRenderPage(num){ pageRendering ? pageNumPending=num : renderPage(num); }

  document.getElementById('next').addEventListener('click', function(){
    if (pageNum < pdfDoc.numPages){ pageNum++; queueRenderPage(pageNum); }
  });
  document.getElementById('prev').addEventListener('click', function(){
    if (pageNum > 1){ pageNum--; queueRenderPage(pageNum); }
  });
  selectPage.addEventListener('change', function(){
    let p=parseInt(this.value); if(p>=1 && p<=pdfDoc.numPages){ pageNum=p; queueRenderPage(p); }
  });

  pdfjsLib.getDocument(url).promise.then(function(pdfDoc_){
    pdfDoc=pdfDoc_;
    for(let i=1;i<=pdfDoc.numPages;i++){ let o=document.createElement('option'); o.value=i; o.textContent=i; selectPage.appendChild(o); }
    renderPage(pageNum);
    document.getElementById('manager-sinature').disabled = false;
  });
}

// ===== เปิดไฟล์จาก card =====
function openPdf(url, id, status, type, is_number, number, position_id){
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

  if (status == STATUS.MAYOR1_SIGNATURE){ $('#manager-sinature').show(); $('#manager-save').show(); }
  if (status == STATUS.MAYOR1_SENT){ $('#manager-send').show(); $('#send-save').show(); }

  $.get('/book/created_position/'+id, function(res){
    if (status >= STATUS.ADMIN_PROCESS && status < STATUS.ARCHIVED && position_id != res.position_id){
      document.getElementById('reject-book').disabled = false;
      $('#reject-book').show();
    }
  });

  resetMarking();
  removeMarkListener();
}

// ===== จัดการลบ listener / ล้าง canvas =====
function removeMarkListener(){
  const canvas = document.getElementById('mark-layer');
  if (markEventListener){ canvas.removeEventListener('click', markEventListener); markEventListener=null; }
}
function resetMarking(){
  const canvas = document.getElementById('mark-layer');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0,0,canvas.width,canvas.height);
  signatureCoordinates = null;
}

// ===== ตารางหน้า/ค้นหา (ของเดิม) =====
selectPageTable.addEventListener('change', function(){ ajaxTable(parseInt(this.value)); });
function onNextPageTable(){ if(pageNumTalbe<pageTotal){ pageNumTalbe++; selectPageTable.value=pageNumTalbe; ajaxTable(pageNumTalbe); } }
function onPrevPageTable(){ if(pageNumTalbe>1){ pageNumTalbe--; selectPageTable.value=pageNumTalbe; ajaxTable(pageNumTalbe); } }
document.getElementById('nextPage').addEventListener('click', onNextPageTable);
document.getElementById('prevPage').addEventListener('click', onPrevPageTable);

function ajaxTable(pages){
  $('#id,#positionX,#positionY,#users_id').val('');
  $('#txt_label').text('');
  document.getElementById('manager-sinature').disabled=false;
  document.getElementById('manager-save').disabled=true;
  $.ajax({
    type:"post", url:"/book/dataList", headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
    data:{ pages }, dataType:"json",
    success:function(res){
      if (!res.status) return;
      $('#box-card-item').empty();
      $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
      res.book.forEach(el=>{
        const html = `<a href="javascript:void(0)" onclick="openPdf('${el.url}','${el.id}','${el.status}')">
            <div class="card border-dark mb-2">
              <div class="card-header text-dark fw-bold">${el.inputSubject}</div>
              <div class="card-body text-dark"><div class="row"><div class="col-9">${el.selectBookFrom}</div><div class="col-3 fw-bold">${el.showTime} น.</div></div></div>
            </div></a>`;
        $('#box-card-item').append(html);
      });
    }
  });
}

$('#search_btn').on('click', function(e){
  e.preventDefault();
  $('#id,#positionX,#positionY,#users_id').val('');
  $('.btn-default').hide();
  $('#txt_label').text('');
  document.getElementById('manager-sinature').disabled=false;
  document.getElementById('manager-save').disabled=true;
  $.ajax({
    type:"post", url:"/book/dataListSearch", headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
    data:{ pages:1, search: $('#inputSearch').val() }, dataType:"json",
    success:function(res){
      if (!res.status) return;
      $('#box-card-item').html('');
      $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
      pageNumTalbe = 1; pageTotal = res.totalPages;
      res.book.forEach(el=>{
        const html = `<a href="javascript:void(0)" onclick="openPdf('${el.url}','${el.id}','${el.status}')">
            <div class="card border-dark mb-2">
              <div class="card-header text-dark fw-bold">${el.inputSubject}</div>
              <div class="card-body text-dark"><div class="row"><div class="col-9">${el.selectBookFrom}</div><div class="col-3 fw-bold">${el.showTime} น.</div></div></div>
            </div></a>`;
        $('#box-card-item').append(html);
      });
      $("#page-select-card").empty();
      for(let i=1;i<=pageTotal;i++){ $('#page-select-card').append('<option value="'+i+'">'+i+'</option>'); }
    }
  });
});

// ======= เปิด modal เกษียณ =======
$(document).ready(function(){
  $('#manager-sinature').on('click', function(e){
    e.preventDefault();
    $('#exampleModal').modal('show');
  });
  $('#exampleModal').on('show.bs.modal', function(){
    $('input[type="password"]').val('');
    // ไม่รีเซ็ต textarea เผื่อแก้ข้อความเดิม
  });
});

// ======= ยืนยันรหัสผ่าน -> เปิดโหมด "ลาก/ย่อ/ขยาย" กล่องลายเซ็น =======
$('#modalForm').on('submit', function(e){
  e.preventDefault();
  var formData = new FormData(this);
  $('#exampleModal').modal('hide');
  Swal.showLoading();
  $.ajax({
    type:"post", url:"/book/confirm_signature", data:formData,
    dataType:"json", contentType:false, processData:false,
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}
  }).done(function(resp){
    if(!resp.status){ Swal.fire("", resp.message||"ยืนยันไม่สำเร็จ", "error"); return; }
    Swal.close();
    resetMarking();
    removeMarkListener();
    document.getElementById('manager-save').disabled = false;

    const canvas = document.getElementById('mark-layer');
    const ctx = canvas.getContext('2d');

    markEventListener = function(){
      // สร้างกล่องครั้งแรก
      if (!signatureCoordinates){
        const tW=220, tH=60, iW=240, iH=130;
        const startX = (canvas.width - tW)/2;
        const startY = (canvas.height - (tH + iH + 20))/2;

        signatureCoordinates = {
          textBox:  { startX:startX,       startY:startY,         endX:startX+tW,   endY:startY+tH,   type:'text'  },
          imageBox: { startX:startX-10,    startY:startY+tH+20,   endX:startX+iW-10,endY:startY+tH+20+iH, type:'image' }
        };
        // เก็บตำแหน่งหลัก (อิงกล่องข้อความ)
        $('#positionX').val(signatureCoordinates.textBox.startX);
        $('#positionY').val(signatureCoordinates.textBox.startY);
      }

      redraw();

      // ลาก/ย่อ-ขยาย
      let dragging=false, resizing=false, active=null, dx=0, dy=0;

      function redraw(){
        ctx.clearRect(0,0,canvas.width,canvas.height);
        const text = $('#modal-text').val();
        const checks = $('input[name="modal-check[]"]:checked').map(function(){return $(this).val();}).get();
        const hasImg = checks.includes('4');

        // textBox
        const t = signatureCoordinates.textBox;
        drawBox(ctx, t, 'blue', '#007bff');
        const tScale = clamp(Math.min((t.endX-t.startX)/220, (t.endY-t.startY)/60), .5, 2.5);
        drawTextCentered(ctx, (15*tScale).toFixed(1)+'px Sarabun', t, text, 20, 20);

        // ข้อความส่วนล่าง (ชื่อ/ตำแหน่ง/วันที่) แทรกต่อใน textBox เอง (บรรทัดถัดไป)
        let bottomY = t.startY + 20*tScale + (countLineBreaks(text)*20);
        const lines = [];
        checks.forEach(v=>{
          if (v==='1') lines.push(`({{$users->fullname}})`);
          if (v==='2') lines.push(`{{$permission_data->permission_name}}`);
          if (v==='3') lines.push(`{{ convertDateToThai(date('Y-m-d')) }}`);
        });
        // วาดบรรทัดล่างๆ ให้อยู่ในกรอบ textBox (ถ้าอยากให้กล่องล่างแยก เพิ่มอีก box ได้)
        lines.forEach((ln,i)=>{
          drawTextCentered(ctx, (15*tScale).toFixed(1)+'px Sarabun',
            {startX:t.startX, startY:bottomY + (i*22), endX:t.endX, endY:bottomY + (i*22) + 22}, ln, 20, 0);
        });

        // imageBox (ถ้าเลือก)
        if (hasImg){
          const im = signatureCoordinates.imageBox;
          drawBox(ctx, im, 'green', '#28a745');
          const iw = im.endX-im.startX, ih = im.endY-im.startY;
          if (signatureImgLoaded){ ctx.drawImage(signatureImg, im.startX, im.startY, iw, ih); }
        }
      }

      function getActive(mx,my){
        const checks = $('input[name="modal-check[]"]:checked').map(function(){return $(this).val();}).get();
        const hasImg = checks.includes('4');
        if (hasImg && inBox(mx,my, signatureCoordinates.imageBox)) return signatureCoordinates.imageBox;
        if (inBox(mx,my, signatureCoordinates.textBox)) return signatureCoordinates.textBox;
        return null;
      }

      canvas.addEventListener('mousemove', function(e){
        const r = canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
        if (isOnHandle(mx,my, signatureCoordinates.textBox) ||
            isOnHandle(mx,my, signatureCoordinates.imageBox)) canvas.style.cursor='se-resize';
        else if (getActive(mx,my)) canvas.style.cursor='move';
        else canvas.style.cursor='default';
      });

      canvas.onmousedown = function(e){
        const r = canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;

        if (isOnHandle(mx,my, signatureCoordinates.textBox)){ active=signatureCoordinates.textBox; resizing=true; }
        else if (isOnHandle(mx,my, signatureCoordinates.imageBox)){ active=signatureCoordinates.imageBox; resizing=true; }
        else { active=getActive(mx,my); if(active){ dragging=true; dx=mx-active.startX; dy=my-active.startY; } }

        if (dragging || resizing){
          e.preventDefault();
          window.addEventListener('mousemove', onMove);
          window.addEventListener('mouseup', onUp);
        }
      };

      function onMove(e){
        const r = canvas.getBoundingClientRect(), mx=e.clientX-r.left, my=e.clientY-r.top;
        if (dragging && active){
          const w=active.endX-active.startX, h=active.endY-active.startY;
          const nsx = clamp(mx - dx, 0, canvas.width - w);
          const nsy = clamp(my - dy, 0, canvas.height- h);
          active.startX = nsx; active.startY = nsy; active.endX = nsx + w; active.endY = nsy + h;
          if (active.type==='text'){ $('#positionX').val(nsx); $('#positionY').val(nsy); }
          redraw();
        }else if (resizing && active){
          const minW=60,minH=30;
          active.endX = clamp(Math.max(active.startX + minW, mx), 0, canvas.width);
          active.endY = clamp(Math.max(active.startY + minH, my), 0, canvas.height);
          redraw();
        }
      }
      function onUp(){
        dragging=false; resizing=false; active=null;
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
      }
    };
    document.getElementById('mark-layer').addEventListener('click', markEventListener);
  });
});

// ===== ส่งบันทึกลายเซ็น (payload แบบเดิม) =====
$('#signature-save').on('click', function(e){
  e.preventDefault();
  var id = $('#id').val();
  var positionX = $('#positionX').val();
  var positionY = $('#positionY').val();
  var pages = $('#page-select').find(":selected").val();
  var text = $('#modal-text').val();
  var checkedValues = $('input[name="modal-check[]"]:checked').map(function(){ return $(this).val(); }).get();

  if (!id || positionX==='' || positionY===''){ Swal.fire("","กรุณาเลือกตำแหน่งเกษียณหนังสือ","info"); return; }

  Swal.fire({ title:"ยืนยันการลงเกษียณหนังสือ", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
  .then(function(r){
    if (!r.isConfirmed) return;
    $.ajax({
      type:"post", url:"/book/signature_stamp",
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
      data:{ id, positionX, positionY, pages, text, checkedValues },
      dataType:"json"
    }).done(function(res){
      if (res.status){ Swal.fire("","ลงบันทึกเกษียณหนังสือเรียบร้อย","success"); setTimeout(()=>location.reload(),1200); }
      else { Swal.fire("","บันทึกไม่สำเร็จ","error"); }
    });
  });
});

// ===== manager-save / manager-send / send-save / reject (ของเดิมตามเพจนี้) =====
$('#manager-save').on('click', function(e){
  e.preventDefault();
  var id=$('#id').val(), position_id=$('#position_id').val(), positionX=$('#positionX').val(), positionY=$('#positionY').val(),
      pages=$('#page-select').val(), text=$('#modal-text').val(),
      checkedValues=$('input[name="modal-check[]"]:checked').map(function(){return $(this).val();}).get();
  if (!id || positionX==='' || positionY===''){ Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info"); return; }

  Swal.fire({ title:"ยืนยันการลงลายเซ็น", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
  .then(function(r){
    if (!r.isConfirmed) return;
    $.ajax({
      type:"post", url:"/book/manager_stamp",
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
      data:{ id, positionX, positionY, pages, status:11, text, checkedValues, position_id }, dataType:"json"
    }).done(function(res){
      if (res.status){ Swal.fire("","บันทึกลายเซ็นเรียบร้อยแล้ว","success"); setTimeout(()=>location.reload(),1200); }
      else { Swal.fire("","บันทึกไม่สำเร็จ","error"); }
    });
  });
});

$('#manager-send').on('click', function(e){
  e.preventDefault();
  $.ajax({
    type:"post", url:"{{ route('book.checkbox_send') }}",
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
    success:function(response){
      Swal.fire({
        title:'แทงเรื่อง', html:response, allowOutsideClick:false, focusConfirm:true,
        confirmButtonText:'ตกลง', showCancelButton:true, cancelButtonText:'ยกเลิก',
        preConfirm: ()=>{
          var ids=[], texts=[];
          $('input[name="flexCheckChecked[]"]:checked').each(function(){
            ids.push($(this).val());
            texts.push($(this).next('label').text().trim());
          });
          if (ids.length===0){ Swal.showValidationMessage('กรุณาเลือกตัวเลือก'); }
          return { id:ids, text:texts };
        }
      }).then((r)=>{
        if (!r.isConfirmed) return;
        $('#users_id').val(r.value.id.join(','));
        $('#txt_label').text('- แทงเรื่อง (' + r.value.text.join(', ') + ') -');
        document.getElementById('send-save').disabled = false;
      });
    }
  });
});

$('#send-save').on('click', function(e){
  e.preventDefault();
  var id=$('#id').val(), users_id=$('#users_id').val(), position_id=$('#position_id').val();
  Swal.fire({ title:"ยืนยันการแทงเรื่อง", icon:'question', showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก" })
  .then((r)=>{
    if (!r.isConfirmed) return;
    $.ajax({
      type:"post", url:"/book/send_to_save",
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
      data:{ id, users_id, status:12, position_id }, dataType:"json"
    }).done((res)=>{
      if (res.status){ Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success"); setTimeout(()=>location.reload(),1200); }
      else { Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error"); }
    });
  });
});

$('#reject-book').on('click', function(e){
  e.preventDefault();
  Swal.fire({
    title:"", text:"ยืนยันการปฏิเสธหนังสือหรือไม่", icon:"warning",
    input:'textarea', inputPlaceholder:'กรอกเหตุผลการปฏิเสธ',
    showCancelButton:true, confirmButtonText:"ตกลง", cancelButtonText:"ยกเลิก",
    preConfirm:(note)=>{ if(!note){ Swal.showValidationMessage('กรุณากรอกเหตุผล'); } return note; }
  }).then((r)=>{
    if (!r.isConfirmed) return;
    $.ajax({
      type:"post", url:"/book/reject",
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},
      data:{ id:$('#id').val(), note:r.value }, dataType:"json"
    }).done(function(res){
      if (res.status){ Swal.fire("", "ปฏิเสธเรียบร้อย", "success"); setTimeout(()=>location.reload(),1200); }
      else { Swal.fire("", "ปฏิเสธไม่สำเร็จ", "error"); }
    });
  });
});
</script>

{{-- Modal เดิมของคุณคงไว้ตามที่แนบมา --}}
<div class="modal modal-lg fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form id="modalForm">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">เซ็นหนังสือ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 row">
          <label class="col-sm-2 col-form-label"><span class="req">*</span>ข้อความเซ็นหนังสือ :</label>
          <div class="col-sm-10">
            <textarea rows="4" class="form-control" name="modal-text" id="modal-text"></textarea>
          </div>
        </div>
        <div class="mb-3 row">
          <div class="col-2"></div>
          <div class="col-sm-10 d-flex justify-content-center text-center">
            ({{$users->fullname}})<br>
            {{$permission_data->permission_name}}<br>
            {{convertDateToThai(date("Y-m-d"))}}
          </div>
        </div>
        <div class="mb-3 row">
          <label class="col-sm-2 col-form-label"><span class="req">*</span>รหัสผ่านเกษียน :</label>
          <div class="col-sm-10">
            <input type="password" class="form-control" id="modal-Password" name="modal-Password">
          </div>
        </div>
        <div class="row">
          <label class="col-sm-2 col-form-label"><span class="req">*</span>แสดงผล :</label>
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
  </div></div>
</div>
@endsection
