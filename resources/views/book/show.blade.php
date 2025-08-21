{{-- resources/views/show.blade.php --}}
@extends('include.main')

@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    .container-fluid {
        padding: 20px;
    }

    .card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 20px;
    }

    .card-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        font-weight: 600;
        padding: 15px;
        border-bottom: none;
    }

    .card-header h4 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header i {
        font-size: 24px;
    }

    .meta-info {
        background: #e9ecef;
        padding: 10px 15px;
        border-radius: 0 0 8px 8px;
        font-size: 14px;
        color: #6c757d;
    }

    .status-badge {
        background: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 10px;
    }

    .speed-badge {
        background: #ffc107;
        color: #212529;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 5px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .btn-action {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        border: 1px solid;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-action i {
        font-size: 16px;
    }

    .btn-primary-action {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }

    .btn-success-action {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }

    .btn-warning-action {
        background: #ffc107;
        border-color: #ffc107;
        color: #212529;
    }

    .btn-secondary-action {
        background: #6c757d;
        border-color: #6c757d;
        color: white;
    }

    .btn-danger-action {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .detail-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 12px;
    }

    .detail-label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 4px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .detail-value {
        font-size: 14px;
        color: #212529;
        font-weight: 500;
    }

    .file-viewer {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        height: 600px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .file-viewer iframe,
    .file-viewer embed {
        width: 100%;
        height: 100%;
        border: none;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #dee2e6;
    }

    .timeline {
        position: relative;
    }

    .timeline-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        position: relative;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 11px;
        top: 30px;
        width: 2px;
        height: calc(100% + 10px);
        background: #dee2e6;
    }

    .timeline-dot {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #007bff;
        border: 3px solid #fff;
        box-shadow: 0 0 0 3px #007bff25;
        flex-shrink: 0;
        margin-top: 4px;
    }

    .timeline-content {
        flex: 1;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
    }

    .timeline-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: #212529;
    }

    .timeline-meta {
        font-size: 12px;
        color: #6c757d;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .permission-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
    }

    .permission-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #dee2e6;
    }

    .permission-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .permission-label {
        font-weight: 600;
        color: #495057;
    }

    .permission-value {
        color: #212529;
    }

    .attachment-chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .attachment-chip {
        background: #e9ecef;
        color: #495057;
        padding: 6px 12px;
        border-radius: 15px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .attachment-chip:hover {
        background: #007bff;
        color: white;
        text-decoration: none;
    }

    .no-permissions {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        text-align: center;
    }

    .no-permissions .alert-title {
        font-weight: 600;
        color: #856404;
        margin-bottom: 8px;
    }

    .no-permissions .alert-text {
        color: #856404;
        font-size: 14px;
    }

    .code-snippet {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 2px 6px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .timeline-meta {
            flex-direction: column;
            gap: 5px;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Header Section --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fa fa-book"></i>
                        {{ $book->inputSubject ?? 'รายละเอียดหนังสือ' }}
                    </h4>
                </div>
                <div class="meta-info">
                    เลขทะเบียน: <strong>{{ $book->inputBookregistNumber ?? '-' }}</strong>
                    @if(!empty($book->inputBooknumberOrgStruc))
                        • เลขที่ส่วน: <strong>{{ $book->inputBooknumberOrgStruc }}</strong>
                    @endif
                    @if(!empty($book->inputDated))
                        • ลงวันที่: <strong>{{ \Carbon\Carbon::parse($book->inputDated)->format('d/m/Y') }}</strong>
                    @endif
                    
                    <span class="status-badge">{{ $book->status ?? '-' }}</span>
                    @if(!empty($book->selectLevelSpeed))
                        <span class="speed-badge">{{ $book->selectLevelSpeed }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left Column: Actions + Details + File Viewer --}}
        <div class="col-lg-8 col-md-12">
            {{-- Actions Card --}}
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-cogs"></i> การดำเนินการ</h5>
                </div>
                <div class="card-body">
                    @php
                        // ดึง can_status จาก $permission หรือจากผู้ใช้ปัจจุบัน
                        $user = auth()->user() ?? null;
                        $canStatusStr = $permission->can_status ?? ($user?->permission->can_status ?? '');
                        
                        // แปลงเป็นอาเรย์ และตัดช่องว่าง
                        $can = collect(preg_split('/\s*,\s*/', (string)$canStatusStr, -1, PREG_SPLIT_NO_EMPTY))
                            ->map(fn($v)=>(string)$v)->values()->all();
                        
                        // helper สำหรับเช็คสิทธิ์
                        $canDo = fn($code) => in_array((string)$code, $can, true);
                        
                        // route เป้าหมาย
                        $statusRoute = function($bookId){
                            return url('/books/'.$bookId.'/status');
                        };
                    @endphp

                    <div class="action-buttons">
                        {{-- แทงเรื่อง (3 / 3.5) --}}
                        @if($canDo('3') || $canDo('3.5'))
                            <button class="btn btn-action btn-primary-action" data-action="status" data-status="3">
                                <i class="fa fa-send"></i> แทงเรื่อง
                            </button>
                        @endif

                        {{-- ประทับตราลงรับ (4) --}}
                        @if($canDo('4'))
                            <button class="btn btn-action btn-success-action" data-action="status" data-status="4">
                                <i class="fa fa-stamp"></i> ประทับตราลงรับ
                            </button>
                        @endif

                        {{-- เกษียณ (5) --}}
                        @if($canDo('5'))
                            <button class="btn btn-action btn-warning-action" data-action="status" data-status="5">
                                <i class="fa fa-archive"></i> เกษียณ
                            </button>
                        @endif

                        {{-- เกษียณพับครึ่ง (14) --}}
                        @if($canDo('14'))
                            <button class="btn btn-action btn-secondary-action" data-action="status" data-status="14">
                                <i class="fa fa-sticky-note"></i> เกษียณพับครึ่ง
                            </button>
                        @endif

                        {{-- ตีกลับ (10) --}}
                        @if($canDo('10'))
                            <button class="btn btn-action btn-danger-action" data-action="status" data-status="10">
                                <i class="fa fa-undo"></i> ตีกลับ
                            </button>
                        @endif
                    </div>

                    @if(empty($can))
                        <div class="no-permissions">
                            <div class="alert-title">ยังไม่ได้กำหนดสิทธิ์ (can_status) ให้ผู้ใช้นี้</div>
                            <div class="alert-text">
                                กรุณาตรวจสอบตาราง <span class="code-snippet">permissions.can_status</span> หรือข้อมูลในผู้ใช้
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Details Card --}}
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-info-circle"></i> รายละเอียดหนังสือ</h5>
                </div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">ประเภทหนังสือ</div>
                            <div class="detail-value">{{ $book->type ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">ทะเบียนรับ</div>
                            <div class="detail-value">{{ $book->selectBookregist ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">เลขทะเบียน</div>
                            <div class="detail-value">{{ $book->inputBookregistNumber ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">เลขที่หน่วยงาน</div>
                            <div class="detail-value">{{ $book->inputBooknumberOrgStruc ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">เรื่อง</div>
                            <div class="detail-value">{{ $book->inputSubject ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">จาก</div>
                            <div class="detail-value">{{ $book->selectBookFrom ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">ถึง</div>
                            <div class="detail-value">{{ $book->inputBookto ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">อ้างถึง</div>
                            <div class="detail-value">{{ $book->inputBookref ?? '-' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">วันที่รับ</div>
                            <div class="detail-value">
                                {{ !empty($book->inputRecieveDate) ? \Carbon\Carbon::parse($book->inputRecieveDate)->format('d/m/Y H:i') : '-' }}
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">วันที่ลงรับ</div>
                            <div class="detail-value">
                                {{ !empty($book->adminDated) ? \Carbon\Carbon::parse($book->adminDated)->format('d/m/Y H:i') : '-' }}
                            </div>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="detail-label">บันทึก/หมายเหตุ</div>
                            <div class="detail-value">{{ $book->inputNote ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- File Viewer Card --}}
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-file-pdf-o"></i> ไฟล์เอกสาร</h5>
                </div>
                <div class="card-body">
                    @php
                        $filePath = $book->file ?? null;
                        $fullPath = $filePath ? (Str::startsWith($filePath, ['http://','https://']) ? $filePath : url($filePath)) : null;
                    @endphp

                    @if($fullPath)
                        <div class="file-viewer">
                            <embed src="{{ $fullPath }}" type="application/pdf" />
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fa fa-file-o"></i>
                            <div>ยังไม่มีไฟล์แนบหลักของหนังสือ</div>
                            <small class="text-muted">(field: file)</small>
                        </div>
                    @endif

                    @if(!empty($book->fileAttachments))
                        <div style="margin-top: 15px;">
                            <h6><i class="fa fa-paperclip"></i> ไฟล์แนบเพิ่มเติม</h6>
                            @php
                                $attaches = is_string($book->fileAttachments) ? explode(',', $book->fileAttachments) : (is_array($book->fileAttachments) ? $book->fileAttachments : []);
                            @endphp
                            <div class="attachment-chips">
                                @forelse($attaches as $f)
                                    @php
                                        $url = Str::startsWith($f, ['http://','https://']) ? $f : url(trim($f));
                                    @endphp
                                    <a class="attachment-chip" href="{{ $url }}" target="_blank" rel="noopener">
                                        <i class="fa fa-download"></i> เปิดไฟล์แนบ
                                    </a>
                                @empty
                                    <span class="text-muted">ไม่พบไฟล์แนบเพิ่มเติม</span>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: Timeline + Permissions --}}
        <div class="col-lg-4 col-md-12">
            {{-- Timeline Card --}}
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-clock-o"></i> ไทม์ไลน์การดำเนินการ</h5>
                </div>
                <div class="card-body">
                    @php
                        // คาดว่า controller จะส่ง $logs
                        $logs = $logs ?? [];
                    @endphp

                    @if(!empty($logs) && count($logs) > 0)
                        <div class="timeline">
                            @foreach($logs as $log)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            {{ $log->detail ?? $log->action ?? 'ดำเนินการ' }}
                                            @if(!empty($log->status))
                                                <span class="badge bg-primary">สถานะ: {{ $log->status }}</span>
                                            @endif
                                        </div>
                                        <div class="timeline-meta">
                                            <span><i class="fa fa-calendar"></i> {{ !empty($log->datetime) ? \Carbon\Carbon::parse($log->datetime)->format('d/m/Y H:i') : '-' }}</span>
                                            @if(!empty($log->position_name))
                                                <span><i class="fa fa-user"></i> {{ $log->position_name }}</span>
                                            @endif
                                            @if(!empty($log->fullname))
                                                <span><i class="fa fa-user-circle"></i> {{ $log->fullname }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fa fa-history"></i>
                            <div>ยังไม่มีบันทึกการดำเนินการ</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Permissions Card --}}
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-users"></i> ข้อมูลสิทธิ์</h5>
                </div>
                <div class="card-body">
                    <div class="permission-info">
                        <div class="permission-item">
                            <span class="permission-label">Permission Name</span>
                            <span class="permission-value">{{ $permission->permission_name ?? ($user?->permission?->permission_name ?? '-') }}</span>
                        </div>
                        <div class="permission-item">
                            <span class="permission-label">can_status</span>
                            <span class="permission-value">{{ $permission->can_status ?? ($user?->permission?->can_status ?? '-') }}</span>
                        </div>
                        <div class="permission-item">
                            <span class="permission-label">ตำแหน่ง (Position ID)</span>
                            <span class="permission-value">{{ $permission->position_id ?? ($user?->position_id ?? '-') }}</span>
                        </div>
                        <div class="permission-item">
                            <span class="permission-label">ผู้ใช้ปัจจุบัน</span>
                            <span class="permission-value">{{ $user->fullname ?? $user->name ?? '-' }}</span>
                        </div>
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
        if(!bookId){
            alert('ไม่พบรหัสหนังสือ');
            return;
        }

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
            
            // แจ้งเตือนและรีเฟรช
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
            if(status === '5' || status === '14'){
                if(!confirm(status === '5' ? 'ยืนยันการ "เกษียณ" เอกสารนี้?' : 'ยืนยันการ "เกษียณพับครึ่ง" เอกสารนี้?')) return;
            }
            
            postStatus(status);
        })
    });
})();
</script>
@endsection