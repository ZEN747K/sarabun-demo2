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
        <form id="modalForm" action="{{$action}}" id="formSubmit" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-header">
                เพิ่มตำแหน่งและหน่วยงาน
            </div>
            <div class="card-body">
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label">หน่วยงาน :</label>
                        <div class="col-sm-10">
                            <select class="form-select select2" id="select_position" name="select_position" require>
                                <option value="" disabled selected>กรุณาเลือก</option>
                                @foreach($position as $rs)
                                <option value="{{$rs->id}}">{{$rs->position_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label">ตำแหน่ง :</label>
                        <div class="col-sm-10">
                            <select class="form-select select2" id="select_permission" name="select_permission" require>
                                <option value="" disabled selected>กรุณาเลือก</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" id="id" value="{{$id}}">
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('users.js.create')