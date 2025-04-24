<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Log_active_book;
use App\Models\Log_status_book;
use App\Models\Permission;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class TrackController extends Controller
{
    public $users;
    public $permission;
    public $permission_data;
    public $permission_id;
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
            $this->position_id = $this->users->position_id;
            $this->position_name = ($sql != null) ? $sql->position_name : '';
            $this->signature = url("/storage/users/" . auth()->user()->signature);
            return $next($request);
        });
    }

    public function index()
    {
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'tracking';
        return view('tracking.index', $data);
    }

    public function dataReportMain()
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => []
        ];
        $logs = new Log_active_book();
        $logs = $logs->select('book_id', Log_active_book::raw('count(id) as total'))->groupBy('book_id')->get();
        if (count($logs) > 0) {
            $info = [];
            foreach ($logs as $rec) {
                $book = new Book();
                $book = $book
                    ->select('books.*', 'book_types.name as type_name', 'book_number_types.name as number_type')
                    ->leftJoin('book_types', 'book_types.id', '=', 'books.inputBookregistNumber')
                    ->leftJoin('book_number_types', 'book_number_types.id', '=', 'books.selectBookcircular')
                    ->find($rec->book_id);
				if(!empty($book) > 0){
					if ($rec->total <= 2) {
						$action = '<a href="' . url('/storage/' . $book->file) . '" target="_blank"><button class="btn btn-sm btn-outline-dark" title="เปิดไฟล์"><i class="fa fa-file-pdf-o"></i></button></a>
						<button title="ดูรายละเอียด" class="btn btn-sm btn-outline-dark modalDetail" data-id="' . Crypt::encrypt($book->id) . '"><i class="fa fa-search"></i></button>';
					} else {
						$action = '<a href="' . url('/tracking/detail/' . Crypt::encrypt($book->id)) . '"><button class="btn btn-sm btn-outline-dark" title="ดูรายละเอียด"><i class="fa fa-search"></i></button></a>';
					}
					$info[] = [
						'number_regis' => $book->inputBookregistNumber,
						'type_regis' => $book->type_name,
						'number_book' => $book->inputBooknumberOrgStruc . '/' . $book->number_type . $book->inputBooknumberEnd,
						'title' => $book->inputSubject,
						'date' => DateThai($book->inputRecieveDate),
						'action' => $action
					];
				}
            }
            $data = [
                'data' => $info,
                'status' => true,
                'message' => 'success'
            ];
        }
        return response()->json($data);
    }


    public function detail($id)
    {
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'tracking';
        $data['id'] = $id;
        return view('tracking.detail', $data);
    }


    public function dataReportDetail(Request $request)
    {
        $id = Crypt::decrypt($request->input('id'));
        $data = [
            'status' => false,
            'message' => '',
            'data' => []
        ];
        $logs = new Log_active_book();
        $logs = $logs->select('position_id', Log_active_book::raw('count(id) as total'))
            ->whereNot('position_id')
            ->where('book_id', $id)
            ->groupBy('position_id')
            ->get();
        if (count($logs) > 0) {
            $info = [];
            foreach ($logs as $rec) {
                $book = new Book();
                $book = $book
                    ->select('books.*', 'book_types.name as type_name', 'book_number_types.name as number_type')
                    ->leftJoin('book_types', 'book_types.id', '=', 'books.inputBookregistNumber')
                    ->leftJoin('book_number_types', 'book_number_types.id', '=', 'books.selectBookcircular')
                    ->find($id);
                $logs_detail = new Log_active_book();
                $logs_detail = $logs_detail->select('log_active_books.*', 'positions.position_name')
                    ->where('position_id', $rec->position_id)
                    ->where('book_id', $id)
                    ->where('status', 3)
                    ->leftJoin('positions', 'log_active_books.position_id', '=', 'positions.id')
                    ->first();
                $logs_main = new Log_status_book();
                $logs_main = $logs_main->where('position_id', $rec->position_id)
                    ->where('book_id', $id)
                    ->first();
				if(!empty($logs_detail)){
					$info[] = [
						'number_regis' => $logs_main->adminBookNumber,
						'title' => $book->inputSubject,
						'date' => DateTimeThai($logs_detail->datetime),
						'orgPath' => $logs_detail->position_name,
						'detail' => $logs_detail->detail,
						'action' => '<a href="' . url('/storage/' . $logs_main->file) . '" target="_blank"><button class="btn btn-sm btn-outline-dark" title="เปิดไฟล์"><i class="fa fa-file-pdf-o"></i></button></a>
						<button title="ดูรายละเอียด" class="btn btn-sm btn-outline-dark modalDetail" data-id="' . $rec->position_id . '"><i class="fa fa-search"></i></button>'
					];
				}
            }
            $data = [
                'data' => $info,
                'status' => true,
                'message' => 'success'
            ];
        }
        return response()->json($data);
    }

    public function getDetailAll(Request $request)
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => ''
        ];
        $id = Crypt::decrypt($request->input('id'));
        $position_id = $request->input('position_id');
        $logs = new Log_active_book();
        $logs = $logs
            ->select('log_active_books.*', 'users.fullname')
            ->leftJoin('users', 'users.id', '=', 'log_active_books.users_id')
            ->where('book_id', $id)
            ->where(function ($query) use ($position_id) {
                if ($position_id) {
                    $query->where('log_active_books.position_id')
                        ->orWhere('log_active_books.position_id', $position_id);
                }
            })
            ->orderBy('log_active_books.status', 'asc')->get();
        if (count($logs)) {
            $txt = '';
            foreach ($logs as $key => $value) {
                $txt .= '<div class="card">
                            <div class="card-header">
                                ' . $value->detail . '
                            </div>
                            <div class="card-body">
                                <p class="card-text">' . $value->fullname . ' ได้ทำการ' . $value->detail . ' วันที่ ' . DateTimeThai($value->datetime) . ' น.</p>
                            </div>
                        </div>';
            }
            $data = [
                'status' => true,
                'message' => 'success',
                'data' => $txt
            ];
        }
        return response()->json($data);
    }
}
