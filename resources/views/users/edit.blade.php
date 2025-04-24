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
        <form id="modalForm" action="{{ route('users.save') }}" id="formSubmit" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-header">
                แก้ไขข้อมูล
            </div>
            <div class="card-body">
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label">ชื่อ-นามสกุล :</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="fullname" id="fullname" autocomplete="off"
                                <?php if ($data->id != auth()->user()->id) { ?>
                                readonly
                                <?php } ?>
                                value="{{$data->fullname}}">
                        </div>
                    </div>
                    <?php if (auth()->user()->permission_id == 9) { ?>
                        <div class="mb-3 row">
                            <label for="email" class="col-sm-2 col-form-label">อีเมล :</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="email" name="email" id="email" autocomplete="off" value="{{$data->username}}">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="password" class="col-sm-2 col-form-label">รหัสผ่านอีเมล :</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="password" name="password" id="password" autocomplete="off">
                            </div>
                        </div>
                    <?php } ?>
                    <div class="mb-3 row">
                        <label for="passwordLogin" class="col-sm-2 col-form-label">รหัสผ่าน :</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="password" name="passwordLogin" id="passwordLogin" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label">อัปโหลดลายเซ็น :</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="file" id="formFile" name="formFile">
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('users.js.index')