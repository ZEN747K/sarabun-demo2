@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }

    .card-header {
        padding: .5rem 1rem;
        margin-bottom: 0;
        background-color: rgba(0, 0, 0, .03);
        border-bottom: 1px solid rgba(0, 0, 0, .125);
    }

    .card-body {
        padding: 1%
    }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
    <div class="card w-75 mt-5">
        <div class="card-header">
            สมุดทะเบียนหนังสือส่ง
            <div class="row m-3">
                <div class="col-4">
                    คำค้น : <input class="form-control" type="text" name="keyword" id="keyword">
                </div>
                <div class="col-4">
                    สมุดทะเบียน : <select class="form-control" name="selectBookregist" id="selectBookregist">
                        <option value="">เลือก</option>
                        @foreach($book_type as $rec)
                        <option value="{{$rec->id}}">{{$rec->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    สมุดทะเบียนส่ง : <select class="form-control" name="selectBookregist_parent" id="selectBookregist_parent">
                        <option value="">เลือก</option>
                        @foreach($book_type_parent as $rec)
                        <option value="{{$rec->id}}">{{$rec->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row m-3">
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-success" id="search">ค้นหา</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="example" class="display">
                <thead>
                    <tr>
                        <th>เลขที่จอง</th>
                        <th>เลขที่หนังสือ</th>
                        <th>เรื่อง</th>
                        <th>หน่วยงาน</th>
                        <th>วันที่</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('bookList.js.index')