@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }

    #upload-area {
        border: 2px dashed #ccc;
        padding: 10px;
        text-align: center;
        height: calc(100vh - 65px);
        background-color: #fff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);

        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    #upload-area.dragover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .upload-icon {
        width: 80px;
        height: auto;
        margin-bottom: 20px;
    }

    h3 {
        font-weight: normal;
        font-size: 18px;
        margin-bottom: 10px;
    }

    .browse-button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        text-decoration: none;
    }

    #file-input {
        display: none;
    }

    #pdf-container {
        height: calc(100vh - 108px);
        width: 90%;
        background-color: #fff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        margin: 0px auto;
        overflow-y: auto;
    }

    #pdf-container canvas {
        margin-bottom: 20px;
        border: 1px solid #ccc;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    canvas {
        width: 100%;
        height: auto;
    }

    .hidden {
        display: none;
    }

    span.req {
        color: red;
    }
</style>
@endsection

@section('content')
<form class="mt-3" action="{{ route('bookSender.save') }}" id="formSubmit" method="post" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-sm-12 col-md-12 col-lg-5">
            <div class="card">
                <div class="card-body d-flex justify-content-center text-center">
                    <div class="col-12">
                        <div class="mb-2 row">
                            <label for="selectBookregist" class="col-sm-2 col-form-label d-flex justify-content-end">สมุดทะเบียน:</label>
                            <div class="col-sm-10">
                                <select class="form-control select2" name="selectBookregist" id="selectBookregist">
                                    @foreach($book_type as $rec)
                                    <option value="{{$rec->id}}">{{$rec->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2 row">
                            <label for="selectBookregist_parent" class="col-sm-2 col-form-label d-flex justify-content-end"><span class="req">*</span>สมุดทะเบียนส่ง:</label>
                            <div class="col-sm-10">
                                <select class="form-control select2" name="selectBookregist_parent" id="selectBookregist_parent">
                                    @foreach($book_type_parent as $rec)
                                    <option value="{{$rec->id}}">{{$rec->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2 row">
                            <label for="inputBooknumberOrgStruc" class="col-sm-2 col-form-label d-flex justify-content-end">เลขที่หนังสือ:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="inputBooknumberOrgStruc" name="inputBooknumberOrgStruc" autocomplete="off">
                            </div>
                            <label for="book_number_type" class="col-auto col-form-label" style="font-size:18px"> / </label>
                            <div class="col-sm-3">
                                <select class="form-control select2" name="selectBookcircular" id="selectBookcircular">
                                    <option value="" disabled selected>กรุณาเลือก</option>
                                    <option value="1">ว</option>
                                    <option value="2">ว 2566</option>
                                    <option value="3">2567</option>
                                    <option value="4">2568</option>
                                    <option value="5">ว 2568</option>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label for="inputBookregistNumber" class="col-auto col-form-label fw-bold" style="text-align:left;" id="inputBookregistLabel">{{ $inputBookregistNumber }}</label>
                                <input type="hidden" name="inputBookregistNumber" id="inputBookregistNumber" value="{{ $inputBookregistNumber }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="selectLevelSpeed" class="col-sm-2 col-form-label d-flex justify-content-end">ชั้นความเร็ว:</label>
                            <div class="col-sm-4">
                                <select class="form-control select2" name="selectLevelSpeed" id="selectLevelSpeed">
                                    <option value="" disabled selected>กรุณาเลือก</option>
                                    <option value="1">ด่วน</option>
                                    <option value="2">ด่วนมาก</option>
                                    <option value="3">ด่วนที่สุด</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="inputDated" class="col-sm-2 col-form-label d-flex justify-content-end">ลงวันที่:</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" id="inputDated" name="inputDated">
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2 row">
                            <label for="inputSubject" class="col-sm-2 col-form-label d-flex justify-content-end"><span class="req">*</span>เรื่อง:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="inputSubject" id="inputSubject"></textarea>
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="inputBookto" class="col-sm-2 col-form-label d-flex justify-content-end"><span class="req">*</span>เรียน:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="inputBookto" name="inputBookto" autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="inputBookref" class="col-sm-2 col-form-label d-flex justify-content-end">อ้างถึง:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="inputBookref" name="inputBookref" autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="inputAttachments" class="col-sm-2 col-form-label d-flex justify-content-end">สิ่งที่ส่งมาด้วย:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="inputAttachments" id="inputAttachments"></textarea>
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="inputContent" class="col-sm-2 col-form-label d-flex justify-content-end"><span class="req">*</span>เนื้อหา:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="inputContent" id="inputContent"></textarea>
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label for="selectUsersParent" class="col-sm-2 col-form-label d-flex justify-content-end">ผู้ลงนาม:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="selectUsersParent" id="selectUsersParent">
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2 row">
                            <label for="flexCheckChecked" class="col-sm-2 col-form-label d-flex justify-content-end">ไฟล์แนบอื่นๆ:</label>
                            <div class="col-sm-10">
                                <div class="input-group mb-3">
                                    <input type="file" class="form-control" id="file-attachments" name="file-attachments">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-12 col-lg-7" id="uploadDiv" style="height:800px">
            <div id="upload-area">
                <div class="upload-container">
                    <img src="template/icon/upload.png" alt="Cloud Upload Icon" class="upload-icon">
                    <input type="file" id="file-input" name="file-input" style="opacity: 0; position: absolute;" accept="application/pdf">
                    <p>DRAG & DROP FILE HERE OR</p>
                    <button type="button" id="browse-btn" class="btn btn-outline-info">Browse files</button>
                </div>
            </div>
            <div id="pdf-container" class="hidden" style="overflow-y: scroll; height: 800px;"></div>
        </div>
        <div class="col-sm-12 col-md-12 col-lg-7 hidden" id="dataTableDiv" style="height:800px">
            <div class="card">
                <div class="card-body">
                    <table id="tableEmail" class="display w-100">
                        <thead>
                            <tr>
                                <th>เลือก</th>
                                <th>หัวเรื่อง</th>
                                <th>ผู้ส่ง</th>
                                <th>เวลา</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@extends('booksender.js.index')