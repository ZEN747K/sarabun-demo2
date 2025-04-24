@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }

    .text-right {
        text-align: right;
    }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
    <div class="card w-50 mt-5">
        <form id="modalForm" action="{{route('permission.save')}}" id="formSubmit" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-header">
                เพิ่มตำแหน่งในหน่วยงาน
            </div>
            <div class="card-body">
                <div class="modal-body">
                    <div class="mb-4 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label">ชื่อสิทธิการใช้งาน :</label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="name" id="name" value="<?php if (isset($info)) {
                                                                                                        echo $info->permission_name;
                                                                                                    } ?>">
                        </div>
                    </div>
                    <div class="mb-4 row">
                        <label for="selectParent" class="col-sm-2 col-form-label">ภายใต้ :</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="selectParent" id="selectParent" require>
                                <option value="">กรุณาเลือก</option>
                                @foreach($item as $rs)
                                <option value="{{$rs->id}}" <?php if (isset($info)) {
                                                                if ($info->parent_id == $rs->id) {
                                                                    echo 'selected';
                                                                }
                                                            } ?>>{{$rs->permission_name}}</option>
                                @endforeach
                                <option value="6" <?php if (isset($info)) {
                                                        if ($info->parent_id == 6) {
                                                            echo 'selected';
                                                        }
                                                    } ?>>ปลัดองค์การบริหารส่วนตำบลแปลงยาว</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-4 row">
                        <h6>
                            รับหนังสือทั่วไป
                        </h6>
                    </div>
                    <?php
                    if (isset($info)) {
                        $can_status = explode(',', $info->can_status);
                    }
                    ?>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">รับหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="1" id="checkbox1" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('1', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">ลงรับหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="2" id="checkbox2" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('2', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="mt-4 row">
                        <h6>
                            ธุรการ
                        </h6>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">ลงรับ/เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="3" id="checkbox3" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('3', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">เซนต์เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="4" id="checkbox4" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('4', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="mt-4 row">
                        <h6>
                            ผู้อำนวยการ
                        </h6>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">เซนต์เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="5" id="checkbox5" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('6', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="mt-4 row">
                        <h6>
                            ปลัด
                        </h6>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">เซนต์เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="6" id="checkbox6" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('8', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="mt-4 row">
                        <h6>
                            รองนายก
                        </h6>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">เซนต์เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="7" id="checkbox7" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('10', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                    <div class="mt-4 row">
                        <h6>
                            นายก
                        </h6>
                    </div>
                    <div class="row d-flex align-items-center">
                        <label for="inputPassword" class="col-sm-3 col-form-label text-right">เซนต์เกษียณหนังสือ :</label>
                        <div class="col-sm-9">
                            <input class="form-check-input" type="checkbox" value="8" id="checkbox8" name="checkbox[]"
                                <?php if (isset($info)) {
                                    if (in_array('12', $can_status)) {
                                        echo 'checked';
                                    }
                                } ?>>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" id="id" value="<?php if (isset($info)) {
                                                                echo $info->id;
                                                            } ?>">
            <input type="hidden" name="position_id" id="position_id" value="<?php if (isset($info)) {
                                                                                echo $info->position_id;
                                                                            } else {
                                                                                echo $id;
                                                                            } ?>">
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('users.js.form')