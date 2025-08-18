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
        <form id="modalForm" action="{{ route('users.insertUser') }}" method="post">
            @csrf
            <div class="card-header">
                เพิ่ม User
            </div>
            <div class="card-body">
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">ชื่อ-นามสกุล :</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="fullname" id="fullname" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">ชื่อผู้ใช้ :</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="email" id="email" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">รหัสผ่าน :</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="password" name="password" id="password" placeholder="4321" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">หน่วยงาน :</label>
                        <div class="col-sm-9">
                            <select class="form-select select2" id="select_position" name="position_id">
                                <option value="" disabled selected>กรุณาเลือก</option>
                                @foreach($position as $rs)
                                <option value="{{$rs->id}}">{{$rs->position_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">ตำแหน่ง :</label>
                        <div class="col-sm-9">
                            <select class="form-select select2" id="select_permission" name="permission_id">
                                <option value="" disabled selected>กรุณาเลือก</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('users.js.create')