@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
    <div class="card w-75 mt-5">
        <div class="card-header d-flex justify-content-end">
            <a href="/users/create_permission/{{$id}}" class="btn btn-sm btn-outline-success">เพิ่ม</a>
        </div>
        <div class="card-body">
            <table id="example" class="display">
                <thead>
                    <tr>
                        <th>ชื่อตำแหน่ง</th>
                        <th>หน่วยงาน</th>
                        <th>แก้ไข</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@extends('users.js.permission')