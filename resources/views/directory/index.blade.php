@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
        height: 850px;
    }

    .container {
        width: 300px;
    }

    .card-file {
        background: #f8f8f8;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        position: relative;
        height: 830px;
        padding-left: 25px;
        padding-top: 25px;
    }

    .folder-container {
        display: flex;
        align-items: center;
        cursor: pointer;
        margin-bottom: 5px;
        width: 98%;
    }

    .arrow {
        margin-right: 8px;
        transition: transform 0.2s ease;
        font-size: 16px;
        cursor: pointer;
    }

    .folder,
    .sub-folder {
        font-weight: bold;
        /* background: #d9d9d9; */
        padding: 8px;
        /* border-radius: 5px; */
        border: 1px solid #ccc;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .sub-folder {
        background: #e6e6e6;
        margin-left: 20px;
    }

    .file {
        padding: 8px;
        margin-left: 60px;
        border-radius: 5px;
        background: #ffffff;
        border: 1px solid #ccc;
        margin-bottom: 5px;
        width: 470px;
    }

    .hidden {
        display: none;
    }

    .pl-3 {
        padding-left: 15px
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-4">
        <div class="card card-file">
            @foreach($item as $key => $rs)
            <div class="folder-container" onclick="toggleFolder('folder{{$key}}', this, '{{$key}}')">
                <div class="folder">📂 {{$rs}}</div>
            </div>
            @endforeach
            <!-- <div class="folder-container" onclick="toggleFolder('folder1', this)">
                <span class="arrow">▶</span>
                <div class="folder">📂 กองการศึกษา ศาสนาและวัฒนธรรม</div>
            </div>
            <div id="folder1" class="hidden">
                <div class="folder-container pl-3" onclick="toggleFolder('sub1', this)">
                    <span class="arrow">▶</span>
                    <div class="sub-folder">📁 แฟ้มจัดเก็บ(สำนักปลัด)</div>
                </div>
                <div id="sub1" class="hidden">
                    <div class="file">📄 ไฟล์ A</div>
                    <div class="file">📄 ไฟล์ B</div>
                </div>

                <div class="folder-container pl-3" onclick="toggleFolder('sub2', this)">
                    <span class="arrow">▶</span>
                    <div class="sub-folder">📁 คำร้อง</div>
                </div>
                <div id="sub2" class="hidden">
                    <div class="file">📄 ไฟล์ C</div>
                </div>
            </div>

            <div class="folder-container" onclick="toggleFolder('folder2', this)">
                <span class="arrow">▶</span>
                <div class="folder">📂 กองคลัง</div>
            </div>
            <div id="folder2" class="hidden">
                <div class="file">📄 เอกสารงบประมาณ</div>
            </div> -->
        </div>
    </div>
    <input type="hidden" name="id" id="id">
    <div class="col-8">
        <div class="card card-file">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        คำค้น : <input class="form-control" type="text" name="keyword" id="keyword">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-outline-success" id="search">ค้นหา</button>
                    </div>
                </div>
                <hr>
                <table id="example" class="display mt-2">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>เลขที่</th>
                            <th>เลขที่หนังสือ</th>
                            <th>ชื่อหนังสือ</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('directory.js.index')