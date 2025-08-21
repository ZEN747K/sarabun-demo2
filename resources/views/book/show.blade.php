@extends('include.main')

@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
        background-color: #f8f9fa;
    }

    .container-fluid {
        padding: 15px;
    }

    .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .document-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid;
    }

    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .document-card.type-internal {
        border-left-color: #17a2b8;
    }

    .document-card.type-external {
        border-left-color: #ffc107;
    }

    .document-card.status-completed {
        border-left-color: #28a745;
        background-color: #f8fff9;
    }

    .btn-action {
        margin: 2px;
        font-size: 13px;
        padding: 5px 10px;
    }

    .hidden {
        display: none !important;
    }

    #upload-area {
        border: 2px dashed #dee2e6;
        padding: 40px;
        text-align: center;
        background-color: #fff;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    #upload-area.dragover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .upload-icon {
        width: 60px;
        height: auto;
        margin-bottom: 20px;
        opacity: 0.6;
    }

    #pdf-viewer {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .pdf-canvas-container {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 500px;
        padding: 20px;
        background: #f8f9fa;
    }

    .pdf-canvas-wrapper {
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        overflow: hidden;
    }

    .pdf-canvas {
        display: block;
        border: 1px solid #dee2e6;
    }

    .overlay-canvas {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: auto;
        cursor: crosshair;
    }

    .toolbar {
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .status-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 500;
    }

    .search-section {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .document-list {
        max-height: 750px;
        overflow-y: auto;
    }

    .document-viewer {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    .pagination-controls {
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        align-items: center;
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 10px;
        }
        
        .action-buttons {
            justify-content: flex-start;
        }
    }
</style>
@endsection

@section('content')
@php
    // Permission Management System
    $permissionObj = $permission ?? ($currentPermission ?? null) ?? null;
    $rawCanStatus = '';
    
    if ($permissionObj && isset($permissionObj->can_status)) {
        $rawCanStatus = (string) $permissionObj->can_status;
    } elseif (isset($users) && isset($users->permission) && isset($users->permission->can_status)) {
        $rawCanStatus = (string) $users->permission->can_status;
    }

    $canStatus = array_filter(array_map(static function ($v) {
        return trim((string)$v);
    }, explode(',', $rawCanStatus)));

    $can = static function (string $code) use ($canStatus): bool {
        return in_array($code, $canStatus, true);
    };

    // Helper functions
    $fmtTime = static function ($time) {
        if (empty($time)) return '-';
        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Throwable $e) {
            return $time;
        }
    };

    $getStatusBadge = static function ($status) {
        $badges = [
            14 => ['class' => 'bg-success text-white', 'text' => 'เสร็จสิ้น'],
            5 => ['class' => 'bg-warning text-dark', 'text' => 'เกษียณ'],
            4 => ['class' => 'bg-info text-white', 'text' => 'ประทับตรา'],
            3 => ['class' => 'bg-primary text-white', 'text' => 'แทงเรื่อง'],
            1 => ['class' => 'bg-secondary text-white', 'text' => 'รอดำเนินการ'],
        ];
        return $badges[$status] ?? ['class' => 'bg-light text-dark', 'text' => 'ไม่ระบุ'];
    };
@endphp

<div class="container-fluid">
    {{-- Header Section --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="search-section">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="inputSearch" class="form-control" placeholder="ค้นหาหนังสือ...">
                            <button class="btn btn-outline-primary" type="button" id="search_btn">
                                <i class="fa fa-search"></i> ค้นหา
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <label class="text-danger fw-bold" id="txt_label"></label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-end">
                            สิทธิ์: {{ $rawCanStatus !== '' ? $rawCanStatus : 'ไม่ระบุ' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Document List Section --}}
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">รายการหนังสือ</h6>
                </div>
                <div class="card-body p-0">
                    <div class="document-list" id="box-card-item">
                        @foreach ($book as $rec)
                        @php
                            $cardClass = 'document-card mb-2';
                            $cardClass .= $rec->type == 1 ? ' type-internal' : ' type-external';
                            $cardClass .= $rec->status == 14 ? ' status-completed' : '';
                            
                            $statusBadge = $getStatusBadge($rec->status);
                            
                            if ($rec->file) {
                                $action = "openDocument('{$rec->url}', '{$rec->id}', '{$rec->status}', '{$rec->type}', '{$rec->is_number_stamp}', '{$rec->inputBookregistNumber}', '{$rec->position_id}')";
                            } else {
                                $action = "uploadDocument('{$rec->id}')";
                            }
                        @endphp
                        <div class="{{ $cardClass }}" onclick="{{ $action }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-1 text-truncate" style="max-width: 70%">
                                        {{ $rec->inputSubject }}
                                    </h6>
                                    <span class="status-badge {{ $statusBadge['class'] }}">
                                        {{ $statusBadge['text'] }}
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <small class="text-muted d-block">จาก: {{ $rec->selectBookFrom }}</small>
                                        @if($rec->inputBookregistNumber)
                                            <small class="text-primary">เลขรับ: {{ $rec->inputBookregistNumber }}</small>
                                        @endif
                                    </div>
                                    <div class="col-4 text-end">
                                        <small class="fw-bold text-dark">{{ $fmtTime($rec->showTime) }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="pagination-controls">
                        <button class="btn btn-sm btn-outline-secondary" id="prevPage">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <select id="page-select-card" class="form-select form-select-sm" style="width: auto;">
                            @for($page = 1; $page <= $totalPages; $page++)
                                <option value="{{ $page }}">หน้า {{ $page }}</option>
                            @endfor
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" id="nextPage">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Document Viewer Section --}}
        <div class="col-md-8">
            {{-- PDF Viewer --}}
            <div class="document-viewer" id="div-showPdf">
                <div class="toolbar">
                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                        {{-- Navigation Controls --}}
                        <button class="btn btn-sm btn-outline-secondary" id="prev">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <select id="page-select" class="form-select form-select-sm" style="width: auto;"></select>
                        <button class="btn btn-sm btn-outline-secondary" id="next">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                        
                        <div class="vr"></div>
                        
                        {{-- Document Type Tabs --}}
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="docType" id="doc-main" checked>
                            <label class="btn btn-outline-primary" for="doc-main">หนังสือหลัก</label>
                            
                            <input type="radio" class="btn-check" name="docType" id="doc-insert" disabled>
                            <label class="btn btn-outline-primary" for="doc-insert" id="insert_tab_label">เกษียณพับครึ่ง</label>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="action-buttons">
                        @if($can('3'))
                            <button class="btn btn-primary btn-action hidden" id="send-to" title="แทงเรื่อง">
                                <i class="fa fa-send"></i> แทงเรื่อง
                            </button>
                        @endif
                        
                        @if($can('4'))
                            <button class="btn btn-success btn-action hidden" id="add-stamp" title="ประทับตรา">
                                <i class="fa fa-stamp"></i> ประทับตรา
                            </button>
                            <button class="btn btn-info btn-action hidden" id="number-stamp" title="ประทับเลขรับ">
                                <i class="fa fa-hashtag"></i> เลขรับ
                            </button>
                        @endif
                        
                        @if($can('5'))
                            <button class="btn btn-warning btn-action hidden" id="send-signature" 
                                    data-bs-toggle="offcanvas" data-bs-target="#signatureOffcanvas" title="เกษียณหนังสือ">
                                <i class="fa fa-edit"></i> เกษียณ
                            </button>
                        @endif
                        
                        @if($can('14'))
                            <button class="btn btn-secondary btn-action hidden" id="insert-pages" title="เกษียณพับครึ่ง">
                                <i class="fa fa-file-o"></i> พับครึ่ง
                            </button>
                        @endif
                        
                        {{-- Save & Other Actions --}}
                        <div class="vr"></div>
                        <button class="btn btn-outline-primary btn-action hidden" id="save-pdf" title="บันทึก" disabled>
                            <i class="fa fa-save"></i>
                        </button>
                        <button class="btn btn-outline-success btn-action hidden" id="directory-save" title="จัดเก็บ" disabled>
                            <i class="fa fa-folder"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-action hidden" id="reject-book" title="ปฏิเสธ">
                            <i class="fa fa-times"></i> ปฏิเสธ
                        </button>
                    </div>
                </div>

                {{-- PDF Canvas Area --}}
                <div class="pdf-canvas-container" id="pdf-container-main">
                    <div class="pdf-canvas-wrapper">
                        <canvas id="pdf-render" class="pdf-canvas"></canvas>
                        <canvas id="mark-layer" class="overlay-canvas"></canvas>
                    </div>
                </div>

                {{-- Insert Pages Canvas (Hidden by default) --}}
                <div class="pdf-canvas-container hidden" id="pdf-container-insert">
                    <div class="pdf-canvas-wrapper">
                        <canvas id="pdf-render-insert" class="pdf-canvas"></canvas>
                        <canvas id="mark-layer-insert" class="overlay-canvas"></canvas>
                    </div>
                </div>
            </div>

            {{-- Upload Area --}}
            <div class="document-viewer hidden" id="div-uploadPdf">
                <div id="upload-area">
                    <div class="upload-container">
                        <img src="{{ url('/template/icon/upload.png') }}" alt="Upload Icon" class="upload-icon">
                        <input type="file" id="file-input" style="opacity: 0; position: absolute;" accept="application/pdf">
                        <h5 class="text-muted mb-3">อัปโหลดเอกสาร PDF</h5>
                        <p class="text-muted mb-3">ลากและปล่อยไฟล์ที่นี่ หรือ</p>
                        <button type="button" id="browse-btn" class="btn btn-primary">
                            <i class="fa fa-folder-open"></i> เลือกไฟล์
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Signature Offcanvas --}}
<div class="offcanvas offcanvas-end" style="width: 400px;" data-bs-scroll="true" data-bs-backdrop="false" 
     tabindex="-1" id="signatureOffcanvas">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title">เซ็นเกษียณหนังสือ</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="signatureForm">
            <div class="mb-3">
                <label class="form-label">
                    <span class="text-danger">*</span> ข้อความเซ็นหนังสือ
                </label>
                <textarea rows="4" class="form-control" name="signatureText" id="signatureText" 
                          placeholder="กรอกข้อความที่ต้องการแสดงในการเซ็น"></textarea>
            </div>

            <div class="mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <div class="fw-bold">{{ $users->fullname ?? 'ชื่อผู้ใช้' }}</div>
                        <div>{{ $permission_data->permission_name ?? 'ตำแหน่ง' }}</div>
                        <div>{{ convertDateToThai(date("Y-m-d")) ?? date('d/m/Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <span class="text-danger">*</span> รหัสผ่านเกษียน
                </label>
                <input type="password" class="form-control" id="signaturePassword" 
                       placeholder="กรอกรหัสผ่านเพื่อยืนยัน">
            </div>

            <div class="mb-3">
                <label class="form-label">การแสดงผล</label>
                <div class="card">
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="displayOptions[]" value="1" checked id="show-name">
                            <label class="form-check-label" for="show-name">ชื่อ-นามสกุล</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="displayOptions[]" value="2" checked id="show-position">
                            <label class="form-check-label" for="show-position">ตำแหน่ง</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="displayOptions[]" value="3" checked id="show-date">
                            <label class="form-check-label" for="show-date">วันที่</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="displayOptions[]" value="4" checked id="show-signature">
                            <label class="form-check-label" for="show-signature">ลายเซ็น</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary" id="submit-signature">
                    <i class="fa fa-check"></i> ยืนยันการเซ็น
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden Form Fields --}}
<input type="hidden" id="currentDocId">
<input type="hidden" id="currentPositionId">
<input type="hidden" id="currentUserId">
<input type="hidden" id="stampPositionX">
<input type="hidden" id="stampPositionY">
<input type="hidden" id="stampPositionPage">
<input type="hidden" id="stampWidth">
<input type="hidden" id="stampHeight">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the interface
    initializeInterface();
    
    // Document type switching
    document.getElementById('doc-main').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('pdf-container-main').classList.remove('hidden');
            document.getElementById('pdf-container-insert').classList.add('hidden');
        }
    });
    
    document.getElementById('doc-insert').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('pdf-container-main').classList.add('hidden');
            document.getElementById('pdf-container-insert').classList.remove('hidden');
        }
    });
});

function initializeInterface() {
    // Setup search functionality
    setupSearch();
    
    // Setup file upload
    setupFileUpload();
    
    // Setup action buttons
    setupActionButtons();
}

function openDocument(url, id, status, type, isNumberStamp, registNumber, positionId) {
    // Store current document info
    document.getElementById('currentDocId').value = id;
    document.getElementById('currentPositionId').value = positionId;
    
    // Show PDF viewer, hide upload area
    document.getElementById('div-showPdf').classList.remove('hidden');
    document.getElementById('div-uploadPdf').classList.add('hidden');
    
    // Show relevant action buttons based on status and permissions
    showRelevantActions(status, type);
    
    // Load PDF
    loadPdfDocument(url);
}

function uploadDocument(id) {
    // Store document ID
    document.getElementById('currentDocId').value = id;
    
    // Show upload area, hide PDF viewer
    document.getElementById('div-showPdf').classList.add('hidden');
    document.getElementById('div-uploadPdf').classList.remove('hidden');
}

function showRelevantActions(status, type) {
    // Hide all action buttons first
    document.querySelectorAll('.btn-action').forEach(btn => {
        btn.classList.add('hidden');
    });
    const s = parseFloat(status);
    // Show buttons based on status and permissions
    // This logic should match your permission system
    
    if (status < 3) {
        document.getElementById('send-to')?.classList.remove('hidden');
    }
    
    if (status >= 3 && status < 4) {
        document.getElementById('add-stamp')?.classList.remove('hidden');
        document.getElementById('number-stamp')?.classList.remove('hidden');
    }
    
    if (status >= 4 && status < 5) {
        document.getElementById('send-signature')?.classList.remove('hidden');
    }
    
    if (status >= 5 && status < 14) {
        document.getElementById('insert-pages')?.classList.remove('hidden');
    }
    
    // Always show save and file management buttons when document is loaded
    document.getElementById('save-pdf')?.classList.remove('hidden');
    document.getElementById('directory-save')?.classList.remove('hidden');
}

function setupSearch() {
    const searchBtn = document.getElementById('search_btn');
    const searchInput = document.getElementById('inputSearch');
    
    searchBtn?.addEventListener('click', performSearch);
    searchInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
}

function performSearch() {
    const query = document.getElementById('inputSearch').value;
    // Implement search functionality
    console.log('Searching for:', query);
}

function setupFileUpload() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    
    browseBtn?.addEventListener('click', () => fileInput?.click());
    
    // Drag and drop functionality
    uploadArea?.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadArea?.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadArea?.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });
    
    fileInput?.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
}

function handleFileUpload(file) {
    if (file.type !== 'application/pdf') {
        alert('กรุณาเลือกไฟล์ PDF เท่านั้น');
        return;
    }
    
    // Implement file upload logic
    console.log('Uploading file:', file.name);
}

function setupActionButtons() {
    // Setup all action button event listeners
    document.getElementById('send-to')?.addEventListener('click', function() {
        // Implement send functionality
        console.log('Sending document');
    });
    
    document.getElementById('add-stamp')?.addEventListener('click', function() {
        // Implement stamp functionality
        console.log('Adding stamp');
    });
    
    // Add more action button handlers as needed
}

function loadPdfDocument(url) {
    // Implement PDF loading using PDF.js or similar
    console.log('Loading PDF:', url);
}
</script>
@endsection