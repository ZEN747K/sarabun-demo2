@extends('layouts.app')

@section('title', 'รายละเอียดหนังสือ')

@section('content')
@php
    $permissionObj = $permission ?? ($currentPermission ?? null) ?? null;

    $rawCanStatus = '';
    if ($permissionObj && isset($permissionObj->can_status)) {
        $rawCanStatus = (string) $permissionObj->can_status;
    } elseif (isset($user) && isset($user->permission) && isset($user->permission->can_status)) {
        $rawCanStatus = (string) $user->permission->can_status;
    } elseif (isset($authUser) && isset($authUser->permission) && isset($authUser->permission->can_status)) {
        $rawCanStatus = (string) $authUser->permission->can_status;
    }

    $canStatus = array_filter(array_map(static function ($v) {
        return trim((string)$v);
    }, explode(',', $rawCanStatus)));

    $can = static function (string $code) use ($canStatus): bool {
        return in_array($code, $canStatus, true);
    };

    $b = $book instanceof \Illuminate\Support\Collection ? $book->first() : $book;
    
    $get = static function($key, $default = '') use ($b) {
        if (is_object($b)) {
            return $b->{$key} ?? $default;
        }
        if (is_array($b)) {
            return $b[$key] ?? $default;
        }
        return $default;
    };

    $bookId            = $get('id');
    $subject           = $get('inputSubject', '-');
    $bookTo            = $get('inputBookto', '-');
    $bookFrom          = $get('selectBookFrom', '-');
    $bookRef           = $get('inputBookref', '-');
    $bookType          = $get('type', null);
    $bookRegistNum     = $get('inputBookregistNumber', null);
    $bookOrgStruc      = $get('inputBooknumberOrgStruc', null);
    $bookNumberEnd     = $get('inputBooknumberEnd', null);
    $levelSpeed        = $get('selectLevelSpeed', null);
    $recieveDate       = $get('inputRecieveDate', null);
    $pickUpDate        = $get('inputPickUpDate', null);
    $dated             = $get('inputDated', null);
    $statusCurrent     = $get('status', null);
    $mainFilePath      = $get('path', null);
    $mainFileName      = $get('file', null);
    $attachments       = $get('fileAttachments', null);

    $fmtDate = static function ($dt) {
        if (empty($dt)) return '-';
        try {
            return \Carbon\Carbon::parse($dt)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return $dt;
        }
    };

    $statusActionUrl = url('/books/' . $bookId . '/status');
@endphp

<div class="container py-3">
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">รายละเอียดหนังสือ</h4>
                <div class="text-muted small">เลขทะเบียน: {{ $bookRegistNum ?? '-' }} / โครงสร้าง: {{ $bookOrgStruc ?? '-' }} / ปลายเลข: {{ $bookNumberEnd ?? '-' }}</div>
                <div class="text-muted small">ระดับความเร็ว: {{ $levelSpeed ?? '-' }} / สถานะปัจจุบัน: {{ $statusCurrent ?? '-' }}</div>
            </div>
            <div>
                @if(!empty($mainFilePath))
                    <a href="{{ asset($mainFilePath) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        เปิดเอกสารหลัก
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="mb-2"><strong>เรื่อง:</strong> {{ $subject }}</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div><strong>จาก:</strong> {{ $bookFrom }}</div>
                    <div><strong>ถึง:</strong> {{ $bookTo }}</div>
                </div>
                <div class="col-md-4">
                    <div><strong>อ้างอิง:</strong> {{ $bookRef ?: '-' }}</div>
                    <div><strong>ลงวันที่หนังสือ:</strong> {{ $fmtDate($dated) }}</div>
                </div>
                <div class="col-md-4">
                    <div><strong>วันที่รับ:</strong> {{ $fmtDate($recieveDate) }}</div>
                    <div><strong>วันที่รับเอกสารเข้าระบบ:</strong> {{ $fmtDate($pickUpDate) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($attachments))
        @php
            $attList = [];
            if (is_string($attachments)) {
                $maybeJson = json_decode($attachments, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($maybeJson)) {
                    $attList = $maybeJson;
                } else {
                    $attList = [$attachments];
                }
            } elseif (is_array($attachments)) {
                $attList = $attachments;
            }
        @endphp
        @if(count($attList))
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="mb-3">เอกสารแนบ</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($attList as $idx => $file)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate" style="max-width: 70%">{{ $file }}</span>
                                <a href="{{ asset($file) }}" target="_blank" class="btn btn-outline-secondary btn-sm">เปิด</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h6 class="mb-3">การดำเนินการ</h6>

            <div class="d-flex flex-wrap gap-2">
                @if($can('3'))
                    <form action="{{ $statusActionUrl }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="status" value="3">
                        <button type="submit" class="btn btn-primary">
                            แทงเรื่อง
                        </button>
                    </form>
                @endif

                @if($can('3.5'))
                    <form action="{{ $statusActionUrl }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="status" value="3.5">
                        <button type="submit" class="btn btn-primary">
                            แทงเรื่อง (3.5)
                        </button>
                    </form>
                @endif

                @if($can('4'))
                    <form action="{{ $statusActionUrl }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="status" value="4">
                        <button type="submit" class="btn btn-success">
                            ประทับตราลงรับ
                        </button>
                    </form>
                @endif

                @if($can('5'))
                    <form action="{{ $statusActionUrl }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="status" value="5">
                        <button type="submit" class="btn btn-warning">
                            เกษียณ
                        </button>
                    </form>
                @endif

                @if($can('14'))
                    <form action="{{ $statusActionUrl }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="status" value="14">
                        <button type="submit" class="btn btn-secondary">
                            เกษียณพับครึ่ง
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-3 small text-muted">
                สิทธิ์ของคุณ: {{ $rawCanStatus !== '' ? $rawCanStatus : '-' }}
            </div>
        </div>
    </div>

    @if(!empty($logs) && is_iterable($logs))
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">ประวัติการดำเนินการ</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="text-nowrap">วันเวลา</th>
                                <th class="text-nowrap">สถานะ</th>
                                <th class="text-nowrap">ตำแหน่ง</th>
                                <th class="text-nowrap">ผู้นำเข้า</th>
                                <th class="text-nowrap">ไฟล์</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                @php
                                    $dt = $log->datetime ?? $log->created_at ?? null;
                                    $st = $log->status ?? '-';
                                    $pos= $log->position_name ?? ($log->position_id ?? '-');
                                    $usr= $log->user_name ?? ($log->created_by ?? '-');
                                    $f  = $log->file ?? null;
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $fmtDate($dt) }}</td>
                                    <td class="text-nowrap">{{ $st }}</td>
                                    <td class="text-nowrap">{{ $pos }}</td>
                                    <td class="text-nowrap">{{ $usr }}</td>
                                    <td class="text-nowrap">
                                        @if(!empty($f))
                                            <a href="{{ asset($f) }}" target="_blank" class="btn btn-outline-secondary btn-sm">เปิด</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection