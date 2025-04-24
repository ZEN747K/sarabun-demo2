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
    <div class="card w-50 mt-5">
        <div class="card-header" style="padding:2%">
            <div class="row">
                <div class="col-6">สิทธิการใช้งาน</div>
                <div class="col-6 d-flex justify-content-end"><a href="/permission/create/{{$id}}" class="btn btn-sm btn-outline-success">เพิ่ม</a></div>
            </div>
        </div>
        <div class="card-body">
            <table id="example" class="display">
                <thead>
                    <tr>
                        <th width="80%">ตำแหน่ง</th>
                        <th>แก้ไข</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@extends('permission.js.detail')