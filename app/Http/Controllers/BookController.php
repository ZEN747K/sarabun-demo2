<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Directory_log;
use App\Models\Log_active_book;
use App\Models\Log_status_book;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use App\Models\Users_permission;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Auth;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\ClientManager;

class BookController extends Controller
{

    public $users;
    public $permission;
    public $permission_data;
    public $permission_id;
    public $position_data;
    public $position_id;
    public $position_name;
    public $signature;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->users = Auth::user();
            $sql = Permission::where('id', $this->users->permission_id)->first();
            $this->permission = explode(',', $sql->can_status);
            $this->permission_id = $this->users->permission_id;
            $this->permission_data = $sql;
            $sql = Position::where('id', $this->users->position_id)->first();
            $this->position_data = $sql;
            $this->position_id = $this->users->position_id;
            $this->position_name = ($sql != null) ? $sql->position_name : '';
            $this->signature = url("/storage/users/" . auth()->user()->signature);
            return $next($request);
        });
    }

    public function index()
    {
        if ($this->permission_id != 1 && $this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = __FUNCTION__;
        $books = Book::orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $data['inputBookregistNumber'] = $books->inputBookregistNumber + 1;
        } else {
            $data['inputBookregistNumber'] = 1;
        }
        return view('book.index', $data);
    }

    public function bookType(Request $request)
    {
        $id = $request->input('id');
        $books = Book::where('selectBookregist', $id)->orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $inputBookregistNumber = $books->inputBookregistNumber + 1;
        } else {
            $inputBookregistNumber = 1;
        }
        return response()->json($inputBookregistNumber);
    }

    public function save(Request $request)
    {
        if ($request->hasFile('file-input') || isset($request['select-email'])) {
            $book = new Book;
            $book->type = 1;
            $book->selectBookregist = $request['selectBookregist'];
            $book->inputBookregistNumber = $request['inputBookregistNumber'];
            $book->inputBooknumberOrgStruc = $request['inputBooknumberOrgStruc'];
            $book->selectBookcircular = $request['selectBookcircular'];
            $book->inputBooknumberEnd = $request['inputBooknumberEnd'];
            $book->selectLevelSpeed = $request['selectLevelSpeed'];
            $book->inputRecieveDate = $request['inputRecieveDate'];
            $book->inputPickUpDate = $request['inputPickUpDate'];
            $book->inputDated = $request['inputDated'];
            $book->inputSubject = $request['inputSubject'];
            $book->inputBookto = $request['inputBookto'];
            $book->inputBookref = $request['inputBookref'];
            $book->inputContent = $request['inputContent'];
            $book->inputNote = $request['inputNote'];
            $book->selectBookFrom = $request['selectBookFrom'];
            $book->flexCheckChecked = ($request['flexCheckChecked'] == 'on') ? 1 : 0;
            $book->created_by = $this->users->id;
            $book->updated_by = $this->users->id;
            $book->status = 1;
            if (isset($request['select-email'])) {
                $cm = new ClientManager();
                $client = $cm->make([
                    'host'          => 'plaengyao.go.th',
                    'port'          => '993',
                    'encryption'    => 'TLS',
                    'validate_cert' => false,
                    'username'      => 'saraban@plaengyao.go.th',
                    'password'      => 'Saraban@867',
                    'protocol'      => 'imap'
                ]);

                $client->connect();

                $folder = $client->getFolder('INBOX');
                $message = $folder->query()->uid($request['select-email'])->get();
                if ($message) {
                    $message = $message->first();
                    if ($message->hasAttachments()) {
                        $attachments = $message->getAttachments();
                        foreach ($attachments as $attachment) {
                            $filePath =  'uploads/' . time() . '.' . $attachment->getExtension();
                            // $attachment->save(storage_path('app/attachments/' . $newFileName));
                            $attachment->save(storage_path('app/public/uploads/'));
                            rename(storage_path('app/public/uploads/' . $attachment->name), storage_path('app/public/' . $filePath));
                        }
                        $book->file = ($filePath) ? $filePath : '';
                    }
                }
            } else {
                if ($request->hasFile('file-input')) {
                    $file = $request->file('file-input');
                    $filePath = $file->store('uploads');
                    $book->file = ($filePath) ? $filePath : '';
                }
            }
            if ($request->hasFile('file-attachments')) {
                $attachments = $request->file('file-attachments');
                $filePathAttachments = $attachments->store('attachments');
                $book->fileAttachments = ($filePathAttachments) ? $filePathAttachments : '';
            }
            if ($book->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 1,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'นำเข้าเอกสาร',
                    'book_id' => $book->id
                ]);
                return redirect()->route('book.index')->with('success', 'Book added successfully!');
            }
        }
        return redirect()->route('book.index')->with('error', 'ท่านไม่ได้เลือกไฟล์ที่ต้องการนำเข้าระบบ');
    }

    public function show()
    {
        if (in_array('3', $this->permission)) {
            $data['extends'] = 'book.js.admin'; //แก้แล้ว
        } else if (in_array('4', $this->permission)) {
            $data['extends'] = 'book.js.admin'; //แก้แล้ว
        } else if (in_array('6', $this->permission)) {
            $data['extends'] = 'book.js.manager'; //แก้แล้ว
        } else if (in_array('8', $this->permission)) {
            $data['extends'] = 'book.js.bailiff';
        } else if (in_array('10', $this->permission)) {
            $data['extends'] = 'book.js.mayor_1';
        } else if (in_array('12', $this->permission)) {
            $data['extends'] = 'book.js.mayor_2';
        } else {
            $data['extends'] = 'book.js.show';
        }
        // dd($data['extends']);
        $data['function_key'] = __FUNCTION__;
        $data['permission_id'] = $this->permission_id;
        $data['permission'] = implode(',', $this->permission);
        $data['position_id'] = $this->position_id;
        $data['position_name'] = $this->position_name;
        $data['permission_data'] = $this->permission_data;
        $data['users'] = $this->users;
        $data['signature'] = $this->signature;
        Session::forget('keyword');
        $book = new Book;
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book = $book->select('books.*')->whereIn('status', $this->permission)->orderBy('created_at', 'desc')->limit(5)->get();
        } else {
            if ($this->position_id != null) {
                $book = $book->where('log_status_books.position_id', $this->position_id);
            }
            $book = $book->select('books.*', 'log_status_books.status', 'log_status_books.file', 'log_status_books.position_id')
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->whereIn('log_status_books.status', $this->permission)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }
        foreach ($book as &$rec) {
            $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
            $rec->url = url("storage/" . $rec->file);
            $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
        }
        $book_count = new Book;
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book_count = $book_count->select('books.*')->whereIn('status', $this->permission)->orderBy('created_at', 'desc')->count();
        } else {
            if ($this->position_id != null) {
                $book_count = $book_count->where('log_status_books.position_id', $this->position_id);
            }
            $book_count = $book_count->select('books.*', 'log_status_books.status', 'log_status_books.file', 'log_status_books.position_id')
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->whereIn('log_status_books.status', $this->permission)
                ->orderBy('created_at', 'desc')
                ->count();
        }
        $book_count = $book_count;
        $data['totalPages'] = (int)ceil($book_count / 5);
        $data['book'] = $book;
        $item = Position::where('parent_id')->get();
        $data['itemParent'] = [];
        if (auth()->user()->position_id) {
            $item_parent_id = Position::where('parent_id', auth()->user()->position_id)->get();
            foreach ($item_parent_id as $rs) {
                $data['itemParent'][$rs->id] = $rs->position_name;
            }
        }
        foreach ($item as $rs) {
            $data['item'][$rs->id] = $rs->position_name;
        }
        return view('book.show', $data);
    }

    public function dataList(Request $request)
    {
        $data = [
            'book' => array(),
            'status' => false
        ];
        $pages = $request->input('pages');
        if ($pages != 1) {
            $pages = 5 * ($pages - 1);
        } else {
            $pages = 0;
        }
        $search = session('keyword');
        if (!empty($search)) {
            $query = Book::whereRaw('(inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%")');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
                $book = $query->select('books.*')->whereIn('status', $this->permission)->orderBy('created_at', 'desc')->limit(5)->offset($pages)->get();
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
                $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                    ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                    ->whereIn('log_status_books.status', $this->permission)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
                $book = $query->select('books.*')
                    ->whereIn('status', $this->permission)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
                $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                    ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                    ->whereIn('log_status_books.status', $this->permission)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            }
        }
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
                $rec->url = url("storage/" . $rec->file);
                $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
            }
            $data['book'] = $book;
            $data['status'] = true;
        }

        return response()->json($data);
    }

    public function dataListSearch(Request $request)
    {
        Session::forget('keyword');
        $data = [
            'book' => array(),
            'status' => false
        ];
        $pages = $request->input('pages');
        $search = $request->input('search');
        if ($pages != 1) {
            $pages = 5 * ($pages - 1);
        } else {
            $pages = 0;
        }
        if (!empty($search)) {
            session(['keyword' => $search]);
            $query = Book::whereRaw('(inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%")');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        }
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book = $query->select('books.*')
                ->whereIn('status', $this->permission)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->offset($pages)
                ->get();
        } else {
            $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->whereIn('log_status_books.status', $this->permission)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->offset($pages)
                ->get();
        }

        if (!empty($search)) {
            $query = Book::whereRaw('(inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%")');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        }
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book_count = $query->whereIn('status', $this->permission)->count();
        } else {
            $book_count = $query->whereIn('log_status_books.status', $this->permission)
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->count();
        }
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
                $rec->url = url("storage/" . $rec->file);
                $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
            }
            $data['totalPages'] = (int)ceil($book_count / 5);
            $data['book'] = $book;
            $data['status'] = true;
        }

        return response()->json($data);
    }

    public function save_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $positionPages = $request->input('positionPages');
        $width = $request->input('width');
        $height = $request->input('height');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = 2;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($positionPages == 2) {
                $update->new_pages = $book->new_pages + 1;
            }
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 2,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ลงบันทึกรับหนังสือ',
                    'book_id' => $id
                ]);
                $this->editPdf($positionX, $positionY, $pages, $book, $positionPages, $width, $height);
                $filePath =  'uploads/' . rand(1, 10000) . time() . '.pdf';
                rename(storage_path('app/public/' . $book->file), storage_path('app/public/' . $filePath));
                $update->file = $filePath;
                $update->save();
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editPdf($x, $y, $pages, $data, $positionPages, $widthPx, $heightPx)
    {
        $filePath = public_path('/storage/' . $data->file);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();
        $pdf->setAutoPageBreak(false, 0);
        $pdf->SetMargins(210, 0, 0);

        $pageCount = $pdf->setSourceFile($filePath);
        $stop_ = 0;
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($positionPages == 2) {
                if ($stop_ == 0) {
                    $pdf->AddPage();

                    $fontPath = resource_path('fonts/sarabunextralight.php');
                    $pdf->AddFont('sarabunextralight', '', $fontPath);
                    $pdf->setTextColor(0, 0, 255);
                    $pdf->setDrawColor(0, 0, 255);
                    $scale = min($widthPx / 213, $heightPx / 115);
                    $x = ($x / 1.5) * 0.3528;
                    $y = ($y / 1.5) * 0.3528;
                    $width = ($widthPx / 1.5) * 0.3528;
                    $height = ($heightPx / 1.5) * 0.3528;
                    $pdf->Rect($x, $y, $width, $height);
                    $pdf->SetFont('sarabunextralight', '', 10 * $scale);
                    $pdf->Text($x + (1 * $scale), $y + (2 * $scale), 'องค์การบริหารส่วนตำบลแปลงยาว');
                    $pdf->Text($x + (21 * $scale), $y + (8.5 * $scale), numberToThaiDigits($data->inputBookregistNumber));
                    $pdf->SetFont('sarabunextralight', '', 8 * $scale);
                    $pdf->Text($x + (1 * $scale), $y + (10 * $scale), 'รับที่.............................................................');
                    $pdf->Text($x + (8 * $scale), $y + (15 * $scale), convertDayToThai($data->inputRecieveDate));
                    $pdf->Text($x + (19.5 * $scale), $y + (15 * $scale), convertMonthsToThai($data->inputRecieveDate));
                    $pdf->Text($x + (39 * $scale), $y + (15 * $scale), convertYearsToThai($data->inputRecieveDate));
                    $pdf->Text($x + (1 * $scale), $y + (16 * $scale), 'วันที่...........เดือน...................พ.ศ..............');
                    $pdf->Text($x + (19 * $scale), $y + (20 * $scale), convertTimeToThai(date("H:i:s", strtotime($data->inputRecieveDate))));
                    $pdf->Text($x + (1 * $scale), $y + (21 * $scale), 'เวลา..........................................................น.');
                    $stop_++;
                }
            } else {
                if ($pageNo == $pages) {
                    $fontPath = resource_path('fonts/sarabunextralight.php');
                    $pdf->AddFont('sarabunextralight', '', $fontPath);
                    $pdf->setTextColor(0, 0, 255);
                    $pdf->setDrawColor(0, 0, 255);
                    $scale = min($widthPx / 213, $heightPx / 115);
                    $x = ($x / 1.5) * 0.3528;
                    $y = ($y / 1.5) * 0.3528;
                    $width = ($widthPx / 1.5) * 0.3528;
                    $height = ($heightPx / 1.5) * 0.3528;
                    $pdf->Rect($x, $y, $width, $height);
                    $pdf->SetFont('sarabunextralight', '', 10 * $scale);
                    $pdf->Text($x + (1 * $scale), $y + (2 * $scale), 'องค์การบริหารส่วนตำบลแปลงยาว');
                    $pdf->Text($x + (21 * $scale), $y + (8.5 * $scale), numberToThaiDigits($data->inputBookregistNumber));
                    $pdf->SetFont('sarabunextralight', '', 8 * $scale);
                    $pdf->Text($x + (1 * $scale), $y + (10 * $scale), 'รับที่.............................................................');
                    $pdf->Text($x + (8 * $scale), $y + (15 * $scale), convertDayToThai($data->inputRecieveDate));
                    $pdf->Text($x + (19.5 * $scale), $y + (15 * $scale), convertMonthsToThai($data->inputRecieveDate));
                    $pdf->Text($x + (39 * $scale), $y + (15 * $scale), convertYearsToThai($data->inputRecieveDate));
                    $pdf->Text($x + (1 * $scale), $y + (16 * $scale), 'วันที่...........เดือน...................พ.ศ..............');
                    $pdf->Text($x + (19 * $scale), $y + (20 * $scale), convertTimeToThai(date("H:i:s", strtotime($data->inputRecieveDate))));
                    $pdf->Text($x + (1 * $scale), $y + (21 * $scale), 'เวลา..........................................................น.');
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }


    public function send_to_admin(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $position_id = $request->input('position_id');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = 3;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            foreach ($position_id as $value) {
                $filePath = storage_path('app/public/' . $book->file);
                $insert = new Log_status_book();
                $destinationDirectory = storage_path('app/public/' . $value . '/' . $book->file);
                if (!File::exists(storage_path('app/public/' . $value . '/uploads'))) {
                    File::makeDirectory(storage_path('app/public/' . $value . '/uploads'), 0777, true); // สร้างโฟลเดอร์ใหม่
                }
                if (File::exists($filePath)) {
                    File::copy($filePath, $destinationDirectory);
                }
                $insert->book_id = $id;
                $insert->status = 3;
                $insert->datetime = date('Y-m-d H:i:s');
                $insert->file = $value . '/' . $book->file;
                $insert->position_id = $value;
                $insert->new_pages = $book->new_pages;
                if ($insert->save()) {
                    $sql = Position::where('id', $value)->first();
                    log_active([
                        'users_id' => auth()->user()->id,
                        'status' => 3,
                        'datetime' => date('Y-m-d H:i:s'),
                        'detail' => 'แทงเรื่องไป ' . $sql->position_name,
                        'book_id' => $id,
                        'position_id' => $value
                    ]);
                }
            }
            if ($update->save()) {
                $data['status'] = true;
                $data['message'] = 'แทงเรื่องเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function send_to_adminParent(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $position_id = $request->input('position_id');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = 4;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            foreach ($position_id as $value) {
                $logs_file = Log_status_book::where('status', 3.5)->where('position_id', auth()->user()->position_id)->where('book_id', $book->id)->first();
                $filePath = storage_path('app/public/' . $logs_file->file);
                $insert = new Log_status_book();
                $file = str_replace(auth()->user()->position_id . '/' . 'uploads/', '', $logs_file->file);
                $destinationDirectory = storage_path('app/public/' . $value . '/uploads/' . $file);
                if (!File::exists(storage_path('app/public/' . $value . '/uploads'))) {
                    File::makeDirectory(storage_path('app/public/' . $value . '/uploads'), 0777, true); // สร้างโฟลเดอร์ใหม่
                }
                if (File::exists($filePath)) {
                    File::copy($filePath, $destinationDirectory);
                }
                $insert->book_id = $id;
                $insert->status = 3;
                $insert->datetime = date('Y-m-d H:i:s');
                $insert->file = $value . '/uploads/' . $file;
                $insert->position_id = $value;
                $insert->new_pages = $logs_file->new_pages;
                if ($insert->save()) {
                    $sql = Position::where('id', $value)->first();
                    log_active([
                        'users_id' => auth()->user()->id,
                        'status' => 4,
                        'datetime' => date('Y-m-d H:i:s'),
                        'detail' => 'แทงเรื่องไป ' . $sql->position_name,
                        'book_id' => $id,
                        'position_id' => $value
                    ]);
                }
            }
            if (auth()->user()->position_id == 1) {
                $log = Log_status_book::where('status', 3.5)->where('position_id', auth()->user()->position_id)->where('book_id', $book->id)->first();
                $log->status = 4;
                $log->save();
            }
            if ($update->save()) {
                $data['status'] = true;
                $data['message'] = 'แทงเรื่องเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function admin_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $positionPages = $request->input('positionPages');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $this->position_id)->where('book_id', $id)->first();
            $update->status = 3.5;
            $update->updated_at = date('Y-m-d H:i:s');
            $update->adminBookNumber = adminNumber();
            $update->adminDated = date('Y-m-d H:i:s');
            if ($update->save()) {
                $this->editPdf_admin($positionX, $positionY, $pages, $update, $positionPages);
                if ($positionPages == 2) {
                    if ($update->new_pages != 0) {
                        $update->new_pages = $update->new_pages + 1;
                    } else {
                        $update->new_pages = $book->new_pages + 1;
                    }
                    $update->save();
                }
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 3.5,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ประทับตราลงรับ',
                    'book_id' => $id,
                    'position_id' => $update->position_id
                ]);
                $filePath =  $this->position_id . '/uploads/' . rand(1, 10000) . time() . '.pdf';
                rename(storage_path('app/public/' . $update->file), storage_path('app/public/' . $filePath));
                $update->file = $filePath;
                $update->save();
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editPdf_admin($x, $y, $pages, $data, $positionPages)
    {

        $permission_name = $this->position_name;
        $text = mb_strlen($permission_name);
        if ($text >= 30) {
            $dynamicX = 0.5;
        } else if ($text >= 20) {
            $dynamicX = 2;
        } else if ($text >= 15) {
            $dynamicX = 12;
        } else if ($text >= 13) {
            $dynamicX = 16.5;
        } else if ($text >= 10) {
            $dynamicX = 14;
        } else {
            $dynamicX = 17.5;
        }
        $filePath = public_path('/storage/' . $data['file']);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();
        $pdf->setAutoPageBreak(false, 0);
        $pdf->SetMargins(210, 0, 0);

        $pageCount = $pdf->setSourceFile($filePath);
        $stop_ = 0;
        $skip = 0;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($positionPages == 2) {
                if ($stop_ == 0) {
                    if ($skip == $data->new_pages) {
                        $pdf->AddPage();
                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->Rect(0, 0, 210, 10, 'F');
                        $stop_++;

                        $fontPath = resource_path('fonts/sarabunextralight.php');
                        $pdf->AddFont('sarabunextralight', '', $fontPath);
                        $pdf->setTextColor(0, 0, 255);
                        $pdf->setDrawColor(0, 0, 255);
                        $x = ($x / 1.5) * 0.3528;
                        $y = ($y / 1.5) * 0.3528;
                        $width = 50;
                        $height = 27;
                        $pdf->Rect($x, $y, $width, $height);
                        $pdf->SetFont('sarabunextralight', '', 10);
                        $pdf->Text($x + $dynamicX, $y + 2, $permission_name);
                        $pdf->Text($x + 21, $y + 8.5, numberToThaiDigits($data['adminBookNumber']));
                        $pdf->SetFont('sarabunextralight', '', 8);
                        $pdf->Text($x + 1, $y + 10, 'รับที่.................................................................');
                        $pdf->Text($x + 8, $y + 15, convertDayToThai($data['adminDated']));
                        $pdf->Text($x + 19.5, $y + 15, convertMonthsToThai($data['adminDated']));
                        $pdf->Text($x + 39, $y + 15, convertYearsToThai($data['adminDated']));
                        $pdf->Text($x + 1, $y + 16, 'วันที่...........เดือน........................พ.ศ..............');
                        $pdf->Text($x + 19, $y + 20, convertTimeToThai(date('H:i:s', strtotime($data['adminDated']))));
                        $pdf->Text($x + 1, $y + 21, 'เวลา.............................................................น.');
                    }
                    $skip++;
                }
            } else {
                if ($pageNo == $pages) {

                    $fontPath = resource_path('fonts/sarabunextralight.php');
                    $pdf->AddFont('sarabunextralight', '', $fontPath);
                    $pdf->setTextColor(0, 0, 255);
                    $pdf->setDrawColor(0, 0, 255);
                    $x = ($x / 1.5) * 0.3528;
                    $y = ($y / 1.5) * 0.3528;
                    $width = 50;
                    $height = 27;
                    $pdf->Rect($x, $y, $width, $height);
                    $pdf->SetFont('sarabunextralight', '', 10);
                    $pdf->Text($x + $dynamicX, $y + 2, $permission_name);
                    $pdf->Text($x + 21, $y + 8.5, numberToThaiDigits($data['adminBookNumber']));
                    $pdf->SetFont('sarabunextralight', '', 8);
                    $pdf->Text($x + 1, $y + 10, 'รับที่.................................................................');
                    $pdf->Text($x + 8, $y + 15, convertDayToThai($data['adminDated']));
                    $pdf->Text($x + 19.5, $y + 15, convertMonthsToThai($data['adminDated']));
                    $pdf->Text($x + 39, $y + 15, convertYearsToThai($data['adminDated']));
                    $pdf->Text($x + 1, $y + 16, 'วันที่...........เดือน........................พ.ศ..............');
                    $pdf->Text($x + 19, $y + 20, convertTimeToThai(date('H:i:s', strtotime($data['adminDated']))));
                    $pdf->Text($x + 1, $y + 21, 'เวลา.............................................................น.');
                }
            }
        }

        $outputPath = public_path('/storage/' . $data['file']);
        $pdf->Output($outputPath, 'F');
    }

    public function checkbox_send()
    {
        $txt = '<div class="row d-flex align-items-start">';
        $get_users = Users_permission::select('users.*', 'permissions.permission_name')
            ->join('users', 'users_permissions.users_id', '=', 'users.id')
            ->join('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->where('users_permissions.position_id', $this->position_id)
            ->where('users_permissions.permission_id', $this->permission_data->parent_id)
            ->get();
        $count = Users_permission::where('permission_id', $this->permission_data->parent_id)->where('position_id', $this->position_id)->count();
        if (!empty($get_users)) {
            for ($i = 0; $i < $count; $i++) {
                $txt .= '<div class="col-1 mb-3"></div><div class="col-11 mb-2">';
                $txt .= '<input type="checkbox" name="flexCheckChecked[]" id="flexCheckChecked' . $get_users[$i]->id . '" value="' . $get_users[$i]->id . '" class="form-check-input"><label style="margin-left:5px;" for="flexCheckChecked' . $get_users[$i]->id . '">' . $get_users[$i]->fullname . ' (' . $get_users[$i]->permission_name . ')' . '</label>';
                $txt .= '</div>';
            }
        }
        $txt .= '</div>';
        return response()->json($txt);
    }

    public function _checkbox_send()
    {
        $txt = '<div class="row d-flex align-items-start">';
        $get_users = Users_permission::select('users.*', 'permissions.permission_name')
            ->join('users', 'users_permissions.users_id', '=', 'users.id')
            ->join('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->where('users_permissions.permission_id', $this->permission_data->parent_id)
            ->get();
        $count = Users_permission::where('permission_id', $this->permission_data->parent_id)->count();
        if (!empty($get_users)) {
            for ($i = 0; $i < $count; $i++) {
                $txt .= '<div class="col-1 mb-3"></div><div class="col-11 mb-2">';
                $txt .= '<input type="checkbox" name="flexCheckChecked[]" id="flexCheckChecked' . $get_users[$i]->id . '" value="' . $get_users[$i]->id . '" class="form-check-input"><label style="margin-left:5px;" for="flexCheckChecked' . $get_users[$i]->id . '">' . $get_users[$i]->fullname . ' (' . $get_users[$i]->permission_name . ')' . '</label>';
                $txt .= '</div>';
            }
        }
        $txt .= '</div>';
        return response()->json($txt);
    }


    public function send_to_save(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $users_id = $request->input('users_id');
        $position_id = $request->input('position_id');
        $status = $request->input('status');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            if ($status == 14) {
                $update = Log_status_book::where('position_id', $position_id)
                    ->where('book_id', $id)
                    ->first();
                $getForward = Permission::where('can_status', 'like', "3,3.5%")
                    ->leftJoin('users_permissions', 'users_permissions.permission_id', '=', 'permissions.id')
                    ->where('permissions.position_id', $position_id)
                    ->first();
                $update->parentUsers = $getForward->users_id;
            } else {
                if (auth()->user()->position_id) {
                    $update = Log_status_book::where('position_id', auth()->user()->position_id)->where('book_id', $id)->first();
                } else {
                    $update = Log_status_book::where('position_id', $position_id)->where('book_id', $id)->first();
                }
                $getForward = User::find($users_id);
                $sql = Permission::where('id', $getForward->permission_id)->first();
                $update->parentUsers = $users_id;
            }
            $update->status = $status;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                if ($status == 14) {
                    $detail = 'ส่งเวียนหนังสือ';
                } else {
                    $detail = 'แทงเรื่อง (' . $getForward->fullname . '(' . $sql->permission_name . '))';
                }
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $status,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => $detail,
                    'book_id' => $id,
                    'position_id' => $update->position_id
                ]);
                $data['status'] = true;
                $data['message'] = 'แทงเรื่องเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function confirm_signature(Request $request)
    {
        $data = [
            'status' => false,
            'message' => 'กรุณากรอกรหัสผ่านเกษียณ'
        ];
        $password = $request->input('modal-Password');
        if (!empty($password)) {
            $users = new User;
            $users = $users->where('id', $this->users->id)->first();
            if ($users) {
                $verify_password = password_verify($password, $users->password);
                if ($verify_password) {
                    $data = [
                        'status' => true,
                        'message' => 'ยืนยันรหัสผ่านเกษียณเรียบร้อยแล้ว'
                    ];
                } else {
                    $data['message'] = 'รหัสผ่านเกษียณไม่ถูกต้อง';
                }
            }
        }
        return response()->json($data);
    }

    public function signature_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = Book::where('id', $input['id'])->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $this->position_id)->where('book_id', $input['id'])->first();
            if ($update->status == 3.5) {
                $status = 4;
            } else {
                $status = 5;
            }
            $update->status = $status;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $status,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'เกษียณหนังสือ',
                    'book_id' => $input['id'],
                    'position_id' => $update->position_id
                ]);
                $this->editPdf_signature($input['positionX'], $input['positionY'], $input['pages'], $update, $input['text'], $input['checkedValues'], $input['positionPages']);
                if ($input['positionPages'] == 2) {
                    $update->new_pages = $update->new_pages + 1;
                    $update->save();
                }
                $filePath = $this->position_id . '/uploads/' . rand(1, 10000) . time() . '.pdf';
                rename(storage_path('app/public/' . $update->file), storage_path('app/public/' . $filePath));
                $update->file = $filePath;
                $update->save();
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเกษียณหนังสือเรียบร้อย';
            }
        }
        return response()->json($data);
    }

    public function editPdf_signature($x, $y, $pages, $data, $text, $checkedValues, $positionPages)
    {
        error_reporting(E_ALL & ~E_WARNING);
        preg_match_all('/\n/', $text, $matches);
        $lineCount = count($matches[0]);
        $text = explode("\n", $text);
        $filePath = public_path('/storage/' . $data['file']);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();
        $pdf->setAutoPageBreak(false, 0);
        $pdf->SetMargins(210, 0, 0);

        $pageCount = $pdf->setSourceFile($filePath);
        $stop_ = 0;
        $skip = 0;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($positionPages == 2) {
                if ($stop_ == 0) {
                    if ($skip == $data->new_pages) {
                        $pdf->AddPage();
                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->Rect(0, 0, 210, 10, 'F');
                        $stop_++;

                        $fontPath = resource_path('fonts/sarabunextralight.php');
                        $pdf->AddFont('sarabunextralight', '', $fontPath);
                        $pdf->setTextColor(0, 0, 255);
                        $pdf->setDrawColor(0, 0, 255);

                        $x = ($x / 1.5) * 0.3528;
                        $y = ($y / 1.5) * 0.3528;

                        $pdf->SetFont('sarabunextralight', '', 10);

                        for ($i = 0; $i <= $lineCount; $i++) {
                            $centeredX = $this->getCenteredPosition($pdf, $text[$i], 10, $x, $y + (5 * $i));
                            $pdf->Text($centeredX, $y + (5 * $i), $text[$i]);
                        }

                        $checkbox_text = '';
                        $checkbox_x = 0;
                        foreach ($checkedValues as $key => $value) {
                            if ($value == 4) {
                                $plus_y = 35;
                                $signatureX = $x - 25;
                                $signatureY = $y + 3 + (5 * $lineCount);
                                $pdf->Image(public_path('storage/users/' . auth()->user()->signature), $signatureX, $signatureY, 65, 30);
                            } else {
                                $plus_y = 5;
                            }
                        }

                        $i = 0;
                        foreach ($checkedValues as $key => $value) {
                            switch ($value) {
                                case '1':
                                    $checkbox_text = '(' . $this->users->fullname . ')';
                                    break;
                                case '2':
                                    $checkbox_text = str_replace('\n', '<br>', $this->permission_data->permission_name);
                                    break;
                                case '3':
                                    $checkbox_text = convertDateToThai(date("Y-m-d"));
                                    break;
                            }
                            $text = explode("\n", $checkbox_text);
                            if ($value != 4) {
                                $stop = 0;
                                foreach ($text as $text_) {
                                    if (count($text) > 1) {
                                        if ($stop != 0) {
                                            $i++;
                                        }
                                        $stop++;
                                    }
                                    $centeredX = $this->getCenteredPosition($pdf, $text_, 10, $x, $y + $plus_y + (5 * $lineCount) + (5 * ($key + $i)));
                                    $pdf->Text($centeredX, $y + $plus_y + (5 * $lineCount) + (5 * ($key + $i)), $text_);
                                }
                            }
                        }
                    }
                    $skip++;
                }
            } else {
                if ($pageNo == $pages) {

                    $fontPath = resource_path('fonts/sarabunextralight.php');
                    $pdf->AddFont('sarabunextralight', '', $fontPath);
                    $pdf->setTextColor(0, 0, 255);
                    $pdf->setDrawColor(0, 0, 255);

                    $x = ($x / 1.5) * 0.3528;
                    $y = ($y / 1.5) * 0.3528;

                    $pdf->SetFont('sarabunextralight', '', 10);

                    for ($i = 0; $i <= $lineCount; $i++) {
                        $centeredX = $this->getCenteredPosition($pdf, $text[$i], 10, $x, $y + (5 * $i));
                        $pdf->Text($centeredX, $y + (5 * $i), $text[$i]);
                    }

                    $checkbox_text = '';
                    $checkbox_x = 0;
                    foreach ($checkedValues as $key => $value) {
                        if ($value == 4) {
                            $plus_y = 35;
                            $signatureX = $x - 25;
                            $signatureY = $y + 3 + (5 * $lineCount);
                            $pdf->Image(public_path('storage/users/' . auth()->user()->signature), $signatureX, $signatureY, 65, 30);
                        } else {
                            $plus_y = 5;
                        }
                    }

                    $i = 0;
                    foreach ($checkedValues as $key => $value) {
                        switch ($value) {
                            case '1':
                                $checkbox_text = '(' . $this->users->fullname . ')';
                                break;
                            case '2':
                                $checkbox_text = str_replace('\n', '<br>', $this->permission_data->permission_name);
                                break;
                            case '3':
                                $checkbox_text = convertDateToThai(date("Y-m-d"));
                                break;
                        }
                        $text = explode("\n", $checkbox_text);
                        if ($value != 4) {
                            $stop = 0;
                            foreach ($text as $text_) {
                                if (count($text) > 1) {
                                    if ($stop != 0) {
                                        $i++;
                                    }
                                    $stop++;
                                }
                                $centeredX = $this->getCenteredPosition($pdf, $text_, 10, $x, $y + $plus_y + (5 * $lineCount) + (5 * ($key + $i)));
                                $pdf->Text($centeredX, $y + $plus_y + (5 * $lineCount) + (5 * ($key + $i)), $text_);
                            }
                        }
                    }
                }
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }

    function getCenteredPosition($pdf, $text, $fontSize, $startX, $startY)
    {
        // กำหนดฟอนต์และขนาด
        $pdf->SetFont('sarabunextralight', '', $fontSize);

        // คำนวณความกว้างของข้อความ
        $textWidth = $pdf->GetStringWidth($text);

        // คำนวณตำแหน่งกลาง
        $centeredX = $startX - ($textWidth / 2);

        return $centeredX;
    }

    public function manager_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = Book::where('id', $input['id'])->first();
        if (isset($input['position_id'])) {
            $position_id = $input['position_id'];
        } else {
            $position_id = auth()->user()->position_id;
        }
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $position_id)->where('book_id', $input['id'])->first();
            $update->status = $input['status'];
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $input['status'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'เซ็นเกษียณหนังสือ',
                    'book_id' => $input['id'],
                    'position_id' => $update->position_id
                ]);
                $this->editPdf_signature($input['positionX'], $input['positionY'], $input['pages'], $update, $input['text'], $input['checkedValues'], $input['positionPages']);
                if ($input['positionPages'] == 2) {
                    $update->new_pages = $update->new_pages + 1;
                    $update->save();
                }
                $filePath = $update->position_id . '/uploads/' . rand(1, 10000) . time() . '.pdf';
                rename(storage_path('app/public/' . $update->file), storage_path('app/public/' . $filePath));
                $update->file = $filePath;
                $update->save();
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกลายเซ็นเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function getEmail()
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => 'plaengyao.go.th',
            'port'          => '993',
            'encryption'    => 'TLS',
            'validate_cert' => false,
            'username'      => 'saraban@plaengyao.go.th',
            'password'      => 'Saraban@867',
            'protocol'      => 'imap'
        ]);

        $client->connect();

        $folder = $client->getFolder('INBOX');
        $totalMessages = $folder->query()->all()->count();
        $messages = $folder->query()->all()->get();

        $data = [
            'data' => [],
            'status' => false,
            'total' => $totalMessages
        ];
        if ($totalMessages > 0) {
            foreach ($messages as $message) {
                $uid = $message->getUid();
                $item['uid'] = $uid . '';
                $item['title'] = $message->getSubject() . '';
                $item['sender'] = $message->getFrom() . '';
                $item['date'] = DateThai($message->getDate() . '');
                if ($message->hasAttachments()) {
                    $attachments = $message->getAttachments();

                    foreach ($attachments as $attachment) {
                        $url = 'https://webmail.plaengyao.go.th/roundcube/?_task=mail&_frame=1&_mbox=INBOX&_uid=' . $uid . '&_part=2&_action=get&_extwin=1';
                        $item['url'] = '<a href="' . $url . '" target="_blank"><i class="fa fa-envelope-open-o"></i></a>';
                    }
                }
                $data['data'][] = $item;
            }
            $data['status'] = true;
        }
        return response()->json($data);
    }

    function uploadPdf(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = book::find($input['id']);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('uploads');
            $book->file = ($filePath) ? $filePath : '';
            if ($book->save()) {
                $data['status'] = true;
                $data['message'] = 'บันทึกข้อมูลสำเร็จ';
            }
        }
        return response()->json($data);
    }

    public function number_save(Request $request)
    {
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->is_number_stamp = 1;
            $update->status = 2;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 1,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ลงประทับเลขที่จอง',
                    'book_id' => $id
                ]);
                $this->editNumberPDF($positionX, $positionY, $pages, $book);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editNumberPDF($x, $y, $pages, $data)
    {
        $filePath = public_path('/storage/' . $data->file);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();
        $pdf->setAutoPageBreak(false, 0);
        $pdf->SetMargins(210, 0, 0);

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pages) {

                $fontPath = resource_path('fonts/sarabunextralight.php');
                $pdf->AddFont('sarabunextralight', '', $fontPath);
                $pdf->setTextColor(0, 0, 255);

                $x = ($x / 1.5) * 0.3528;
                $y = ($y / 1.5) * 0.3528;
                $pdf->SetFont('sarabunextralight', '', 14);
                $pdf->Text($x, $y - 5, numberToThaiDigits($data->inputBookregistNumber));
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }

    public function directory_save(Request $request)
    {
        $input = $request->input();
        $book = Book::where('id', $input['id'])->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $this->position_id)->where('book_id', $input['id'])->first();
            $update->status = 15;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 15,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'จัดเก็บหนังสือเข้าแฟ้ม',
                    'book_id' => $input['id'],
                    'position_id' => $update->position_id
                ]);
                $oldPath = $update->file;  // ไฟล์ต้นทาง
                $file = str_replace($update->position_id . '/uploads/', '', $update->file);
                $newPath = 'directory/' . $update->position_id . '/' . $file;  // ไฟล์ปลายทาง
                if (Storage::exists($oldPath)) {
                    Storage::copy($oldPath, $newPath);
                }
                $directorylogs = new Directory_log;
                $directorylogs->book_id = $input['id'];
                $directorylogs->position_id = $update->position_id;
                $directorylogs->logs_id = $update->id;
                $directorylogs->file = $file;
                $directorylogs->created_at = date('Y-m-d H:i:s');
                $directorylogs->created_by = auth()->user()->id;
                $directorylogs->updated_at = date('Y-m-d H:i:s');
                $directorylogs->updated_by = auth()->user()->id;
                $directorylogs->save();
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเกษียณหนังสือเรียบร้อย';
            }
        }
        return response()->json($data);
    }
}
