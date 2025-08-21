{{-- resources/views/show.blade.php --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>รายละเอียดหนังสือ | {{ $book->inputSubject ?? 'หนังสือ' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Font & Icon --}}
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        :root{
            --bg:#0f172a;
            --card:#111827;
            --muted:#94a3b8;
            --text:#e5e7eb;
            --primary:#22d3ee;
            --success:#22c55e;
            --warning:#f59e0b;
            --danger:#ef4444;
            --secondary:#64748b;
            --chip:#1f2937;
            --border:#1f2937;
            --accent:#38bdf8;
        }
        *{box-sizing:border-box}
        html,body{
            margin:0;
            padding:0;
            background: radial-gradient(1200px 800px at 10% -10%, rgba(34,211,238,.12), transparent 50%),
                        radial-gradient(1000px 700px at 120% 20%, rgba(56,189,248,.08), transparent 60%),
                        var(--bg);
            color:var(--text);
            font-family:"Noto Sans Thai", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
            line-height:1.5;
        }
        .container{
            max-width:1120px;
            margin:40px auto;
            padding:0 16px;
        }
        .page-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            margin-bottom:20px;
        }
        .title{
            display:flex;align-items:center;gap:14px
        }
        .title .logo{
            width:46px;height:46px;border-radius:14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display:grid;place-items:center;color:#0b1220;font-weight:800;
            box-shadow:0 8px 20px rgba(56,189,248,.35), inset 0 0 18px rgba(255,255,255,.25);
        }
        .title h1{
            font-size:22px; font-weight:700; margin:0;
            letter-spacing:.2px;
        }
        .title .sub{
            color:var(--muted); font-size:13px; margin-top:2px
        }
        .card{
            background: linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,0)) , var(--card);
            border:1px solid var(--border);
            border-radius:16px;
            padding:18px;
            box-shadow:0 10px 28px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.02) inset;
        }
        .grid{
            display:grid; gap:14px;
        }
        @media (min-width: 900px){
            .grid-cols-2{ grid-template-columns: 1.3fr .7fr; }
        }
        .section-title{
            font-size:14px; color:var(--muted); letter-spacing:.6px; text-transform:uppercase;
            margin:4px 0 12px; font-weight:700;
        }
        .meta{
            display:grid; grid-template-columns: repeat(2,minmax(0,1fr));
            gap:10px;
        }
        .meta .item{
            background:var(--chip); border:1px solid var(--border);
            padding:10px 12px; border-radius:12px;
        }
        .meta .label{font-size:12px; color:var(--muted); margin-bottom:4px}
        .meta .value{font-size:14px; font-weight:600}

        .actions{
            display:flex; flex-wrap:wrap; gap:10px;
        }
        .btn{
            appearance:none; border:1px solid var(--border); background:var(--chip);
            color:var(--text); padding:10px 14px; border-radius:12px; cursor:pointer;
            font-weight:600; font-size:14px; display:inline-flex; align-items:center; gap:8px;
            transition:.18s ease;
        }
        .btn:hover{ transform: translateY(-1px); box-shadow:0 10px 20px rgba(0,0,0,.2)}
        .btn:active{ transform: translateY(0) scale(.98)}
        .btn-primary{ border-color: rgba(34,211,238,.4); box-shadow: 0 0 0 1px rgba(34,211,238,.25) inset}
        .btn-success{ border-color: rgba(34,197,94,.4); box-shadow: 0 0 0 1px rgba(34,197,94,.25) inset}
        .btn-warning{ border-color: rgba(245,158,11,.5); box-shadow: 0 0 0 1px rgba(245,158,11,.25) inset}
        .btn-danger{ border-color: rgba(239,68,68,.45); box-shadow: 0 0 0 1px rgba(239,68,68,.25) inset}
        .btn-secondary{ border-color: rgba(100,116,139,.45); box-shadow: 0 0 0 1px rgba(100,116,139,.25) inset}
        .btn i{font-size:18px}

        .file-viewer{
            background:#0b1220; border:1px solid var(--border); border-radius:14px; overflow:hidden;
            height:520px; display:grid; place-items:center;
        }
        .file-viewer iframe, .file-viewer embed{
            width:100%; height:520px; border:0; background:#0b1220;
        }

        .chips{ display:flex; gap:8px; flex-wrap:wrap}
        .chip{
            padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:.3px;
            border:1px solid var(--border); background:var(--chip); color:var(--muted);
        }

        .timeline{
            display:grid; gap:10px;
        }
        .tl-item{
            display:flex; gap:12px; align-items:flex-start;
            background:var(--chip); border:1px solid var(--border); padding:12px; border-radius:12px;
        }
        .tl-dot{
            width:10px; height:10px; border-radius:50%;
            background:var(--accent); margin-top:6px; box-shadow:0 0 0 3px rgba(56,189,248,.25);
        }
        .tl-main{flex:1}
        .tl-title{font-weight:700; margin-bottom:4px}
        .tl-meta{font-size:12px; color:var(--muted)}

        .muted{ color:var(--muted) }
        .divider{ height:1px; background:var(--border); margin:16px 0 }
        .kbd{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;background:#0b1220;border:1px solid var(--border);padding:2px 8px;border-radius:6px;color:#9ca3af}

        .empty{
            background:repeating-linear-gradient(135deg, rgba(255,255,255,.03) 0px, rgba(255,255,255,.03) 10px, rgba(255,255,255,0) 10px, rgba(255,255,255,0) 20px),
                       #0b1220;
            color:#8b9db3; border:1px dashed var(--border); border-radius:14px; padding:30px; text-align:center;
        }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="page-header">
        <div class="title">
            <div class="logo"><i class='bx bx-book-open'></i></div>
            <div>
                <h1>{{ $book->inputSubject ?? 'รายละเอียดหนังสือ' }}</h1>
                <div class="sub">
                    เลขทะเบียน: {{ $book->inputBookregistNumber ?? '-' }}
                    @if(!empty($book->inputBooknumberOrgStruc)) • เลขที่ส่วน: {{ $book->inputBooknumberOrgStruc }} @endif
                    @if(!empty($book->inputDated)) • ลงวันที่: {{ \Carbon\Carbon::parse($book->inputDated)->format('d/m/Y') }} @endif
                </div>
            </div>
        </div>
        <div class="chips">
            <span class="chip">สถานะปัจจุบัน: <strong style="margin-left:6px">{{ $book->status ?? '-' }}</strong></span>
            @if(!empty($book->selectLevelSpeed))
                <span class="chip">ชั้นความเร็ว: {{ $book->selectLevelSpeed }}</span>
            @endif
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-2">

        {{-- Left: Details + Actions + Viewer --}}
        <div class="grid" style="gap:16px">

            {{-- Actions --}}
            <div class="card">
                <div class="section-title">การดำเนินการ</div>

                @php
                    // ดึง can_status จาก $permission หรือจากผู้ใช้ปัจจุบัน (หาก controller ไม่ส่ง $permission มา)
                    $user = auth()->user() ?? null;
                    $canStatusStr = $permission->can_status
                        ?? ($user?->permission->can_status ?? '');

                    // แปลงเป็นอาเรย์ และตัดช่องว่าง
                    $can = collect(preg_split('/\s*,\s*/', (string)$canStatusStr, -1, PREG_SPLIT_NO_EMPTY))
                            ->map(fn($v)=>(string)$v)->values()->all();

                    // helper สำหรับเช็คสิทธิ์
                    $canDo = fn($code) => in_array((string)$code, $can, true);

                    // route เป้าหมาย (ปรับตามโปรเจ็กต์ได้)
                    $statusRoute = function($bookId){
                        // ถ้าคุณมี route name: route('books.setStatus', $bookId)
                        // return "{{ route('books.setStatus', ':id') }}".replace(':id', $bookId);
                        // Fallback เป็น URL
                        return url('/books/'.$bookId.'/status');
                    };
                @endphp>

                <div class="actions">
                    {{-- แทงเรื่อง (3 / 3.5) --}}
                    @if($canDo('3') || $canDo('3.5'))
                        <button class="btn btn-primary" data-action="status" data-status="3">
                            <i class='bx bx-right-top-arrow-circle'></i> แทงเรื่อง
                        </button>
                    @endif

                    {{-- ประทับตราลงรับ (4) --}}
                    @if($canDo('4'))
                        <button class="btn btn-success" data-action="status" data-status="4">
                            <i class='bx bx-check-shield'></i> ประทับตราลงรับ
                        </button>
                    @endif

                    {{-- เกษียณ (5) --}}
                    @if($canDo('5'))
                        <button class="btn btn-warning" data-action="status" data-status="5">
                            <i class='bx bx-archive'></i> เกษียณ
                        </button>
                    @endif

                    {{-- เกษียณพับครึ่ง (14) --}}
                    @if($canDo('14'))
                        <button class="btn btn-secondary" data-action="status" data-status="14">
                            <i class='bx bx-notepad'></i> เกษียณพับครึ่ง
                        </button>
                    @endif

                    {{-- ตัวอย่างปุ่มยกเลิก/ตีกลับ (ออปชัน) --}}
                    @if($canDo('10'))
                        <button class="btn btn-danger" data-action="status" data-status="10">
                            <i class='bx bx-revision'></i> ตีกลับ
                        </button>
                    @endif
                </div>

                @if(empty($can))
                    <div class="empty" style="margin-top:14px">
                        <div style="font-weight:700;margin-bottom:6px">ยังไม่ได้กำหนดสิทธิ์ (can_status) ให้ผู้ใช้นี้</div>
                        <div class="muted">กรุณาตรวจสอบตาราง <span class="kbd">permissions.can_status</span> หรือข้อมูลในผู้ใช้</div>
                    </div>
                @endif

                <div class="divider"></div>

                <div class="muted" style="font-size:12px">
                    เคล็ดลับ: เปลี่ยนปลายทางส่งสถานะได้ที่บรรทัด <span class="kbd">$statusRoute</span> ในไฟล์นี้
                </div>
            </div>

            {{-- Details --}}
            <div class="card">
                <div class="section-title">รายละเอียดหนังสือ</div>

                <div class="meta">
                    <div class="item">
                        <div class="label">ประเภทหนังสือ</div>
                        <div class="value">{{ $book->type ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">ทะเบียนรับ</div>
                        <div class="value">{{ $book->selectBookregist ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">เลขทะเบียน</div>
                        <div class="value">{{ $book->inputBookregistNumber ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">เลขที่หน่วยงาน</div>
                        <div class="value">{{ $book->inputBooknumberOrgStruc ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">เรื่อง</div>
                        <div class="value">{{ $book->inputSubject ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">จาก</div>
                        <div class="value">{{ $book->selectBookFrom ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">ถึง</div>
                        <div class="value">{{ $book->inputBookto ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">อ้างถึง</div>
                        <div class="value">{{ $book->inputBookref ?? '-' }}</div>
                    </div>
                    <div class="item">
                        <div class="label">วันที่รับ</div>
                        <div class="value">
                            {{ !empty($book->inputRecieveDate) ? \Carbon\Carbon::parse($book->inputRecieveDate)->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                    <div class="item">
                        <div class="label">วันที่ลงรับ</div>
                        <div class="value">
                            {{ !empty($book->adminDated) ? \Carbon\Carbon::parse($book->adminDated)->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                    <div class="item" style="grid-column: 1 / -1">
                        <div class="label">บันทึก/หมายเหตุ</div>
                        <div class="value">{{ $book->inputNote ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Viewer --}}
            <div class="card">
                <div class="section-title">ไฟล์เอกสาร</div>
                @php
                    $filePath = $book->file ?? null; // บางระบบเก็บไว้ใน $book->file, บางระบบเก็บใน $book->path + $book->file
                    $fullPath = $filePath ? (Str::startsWith($filePath, ['http://','https://']) ? $filePath : url($filePath)) : null;
                @endphp

                @if($fullPath)
                    <div class="file-viewer">
                        <embed src="{{ $fullPath }}" type="application/pdf" />
                    </div>
                @else
                    <div class="empty">
                        ยังไม่มีไฟล์แนบหลักของหนังสือ (field: <span class="kbd">file</span>)
                    </div>
                @endif

                @if(!empty($book->fileAttachments))
                    <div style="margin-top:12px">
                        <div class="section-title" style="margin-top:0">ไฟล์แนบเพิ่มเติม</div>
                        @php
                            $attaches = is_string($book->fileAttachments)
                                ? explode(',', $book->fileAttachments)
                                : (is_array($book->fileAttachments) ? $book->fileAttachments : []);
                        @endphp
                        <div class="chips">
                            @forelse($attaches as $f)
                                @php $url = Str::startsWith($f, ['http://','https://']) ? $f : url(trim($f)); @endphp
                                <a class="chip" href="{{ $url }}" target="_blank" rel="noopener">เปิดไฟล์แนบ</a>
                            @empty
                                <span class="muted">ไม่พบไฟล์แนบเพิ่มเติม</span>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- Right: Timeline / Logs --}}
        <div class="grid" style="gap:16px">
            <div class="card">
                <div class="section-title">ไทม์ไลน์การดำเนินการ</div>

                @php
                    // คาดว่า controller จะส่ง $logs (จาก log_active_books / log_status_books join)
                    // รองรับกรณีไม่มีการส่งมาด้วย
                    $logs = $logs ?? [];
                @endphp

                @if(!empty($logs) && count($logs) > 0)
                    <div class="timeline">
                        @foreach($logs as $log)
                            <div class="tl-item">
                                <div class="tl-dot"></div>
                                <div class="tl-main">
                                    <div class="tl-title">
                                        {{ $log->detail ?? $log->action ?? 'ดำเนินการ' }}
                                        @if(!empty($log->status))
                                            <span class="chip" style="margin-left:8px">สถานะ: {{ $log->status }}</span>
                                        @endif
                                    </div>
                                    <div class="tl-meta">
                                        เมื่อ: {{ !empty($log->datetime) ? \Carbon\Carbon::parse($log->datetime)->format('d/m/Y H:i') : '-' }}
                                        @if(!empty($log->position_name)) • ตำแหน่ง: {{ $log->position_name }} @endif
                                        @if(!empty($log->fullname)) • โดย: {{ $log->fullname }} @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty">ยังไม่มีบันทึกการดำเนินการ</div>
                @endif
            </div>

            <div class="card">
                <div class="section-title">ผู้มีสิทธิ์</div>
                <div class="meta">
                    <div class="item">
                        <div class="label">Permission Name</div>
                        <div class="value">{{ $permission->permission_name ?? ($user?->permission?->permission_name ?? '-') }}</div>
                    </div>
                    <div class="item">
                        <div class="label">can_status</div>
                        <div class="value">{{ $permission->can_status ?? ($user?->permission?->can_status ?? '-') }}</div>
                    </div>
                    <div class="item">
                        <div class="label">ตำแหน่ง (Position ID)</div>
                        <div class="value">{{ $permission->position_id ?? ($user?->position_id ?? '-') }}</div>
                    </div>
                    <div class="item">
                        <div class="label">ผู้ใช้ปัจจุบัน</div>
                        <div class="value">{{ $user->fullname ?? $user->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
(function(){
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const buttons = document.querySelectorAll('[data-action="status"]');
    const bookId = @json($book->id ?? null);
    const endpoint = @json($statusRoute($book->id ?? 0));

    async function postStatus(newStatus){
        if(!bookId){ alert('ไม่พบรหัสหนังสือ'); return; }
        try{
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            if(!res.ok){
                const t = await res.text();
                throw new Error(t || 'HTTP '+res.status);
            }
            const data = await res.json().catch(()=> ({}));
            // แจ้งเตือน และรีเฟรช (คุณจะเปลี่ยนเป็นอัปเดตแบบไม่รีเฟรชก็ได้)
            alert(data?.message || 'อัปเดตสถานะสำเร็จ');
            location.reload();
        }catch(e){
            console.error(e);
            alert('ไม่สามารถอัปเดตสถานะได้: ' + (e?.message || 'unknown error'));
        }
    }

    buttons.forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const status = btn.getAttribute('data-status');
            // ยืนยันเป็นพิเศษสำหรับสถานะสำคัญ
            if(status === '5' || status === '14'){ // เกษียณ / เกษียณพับครึ่ง
                if(!confirm(status === '5' ? 'ยืนยันการ “เกษียณ” เอกสารนี้?' : 'ยืนยันการ “เกษียณพับครึ่ง” เอกสารนี้?')) return;
            }
            postStatus(status);
        })
    });
})();
</script>
</body>
</html>
