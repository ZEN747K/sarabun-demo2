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
            <a href="{{ url('/users/add/form') }}" class="btn btn-outline-primary">เพิ่ม User</a>
        </div>
        <div class="card-body">
            <table id="example" class="display">
                <thead>
                    <tr>
                        <th>email</th>
                        <th>ชื่อ</th>
                        <th>ตำแหน่ง</th>
                        <th>หน่วยงาน</th>
                        <th>แก้ไข</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('users.js.index')