<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Directory_log;
use App\Models\Permission;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DirectoryController extends Controller
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
        $data['function_key'] = 'directory';
        $item = Storage::directories('directory');
        $position = Position::orderBy('id', 'asc')->get();
        $data['item'] = array();
        foreach ($item as $key => $rs) {
            $name = str_replace('directory/', '', $rs);
            if (auth()->user()->position_id != null) {
                foreach ($position as $rec) {
                    if (auth()->user()->position_id == $rec->id) {
                        if ($name == $rec->id) {
                            $name = $rec->position_name;
                            $data['item'][$rec->id] = $name;
                        }
                    }
                    if (auth()->user()->position_id == $rec->parent_id) {
                        if ($name == $rec->id) {
                            $name = $rec->position_name;
                            $data['item'][$rec->id] = $name;
                        }
                    }
                }
            } else {
                foreach ($position as $rec) {
                    if ($name == $rec->id) {
                        $name = $rec->position_name;
                        $data['item'][$rec->id] = $name;
                    }
                }
            }
        }
        ksort($data['item']);

        return view('directory.index', $data);
    }

    public function create_directory()
    {
        $position = Position::get();
        foreach ($position as $rs) {
            $folderPath = 'directory/' . $rs->id;

            if (!Storage::exists($folderPath)) {
                Storage::makeDirectory($folderPath);
            }
        }
    }

    public function listData(Request $request)
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => []
        ];
        $input = $request->input();
        if (!empty($input['id'])) {
            $query = Directory_log::select('books.*', 'book_number_types.name as type_name', 'directory_logs.file')
                ->leftJoin('books', 'books.id', '=', 'directory_logs.book_id')
                ->leftJoin('book_number_types', 'book_number_types.id', '=', 'books.selectBookcircular')
                ->leftJoin('log_status_books', 'log_status_books.id', '=', 'directory_logs.logs_id');
            if (!empty($input['keyword'])) {
                $query = $query->whereRaw('(inputSubject like "%' . $input['keyword'] . '%"')
                    ->orWhereRaw('inputBookregistNumber like "%' . $input['keyword'] . '%"')
                    ->orWhereRaw('inputBooknumberOrgStruc like "%' . $input['keyword'] . '%"')
                    ->orWhereRaw('inputBooknumberEnd like "%' . $input['keyword'] . '%")');
            }
            $item = $query->where('directory_logs.position_id', $input['id'])->get();
            $info = array();
            foreach ($item as $rec) {
                $info[] = [
                    'number_regis' => $rec->inputBookregistNumber,
                    'type_regis' => $rec->type_name,
                    'number_book' => $rec->inputBooknumberOrgStruc . '/' . $rec->type_name . $rec->inputBooknumberEnd,
                    'title' => '<a href="' . url('storage/directory/' . $input['id'] . '/' . $rec->file) . '" target="_blank">' . $rec->inputSubject . '</a>',
                    'date' => DateThai($rec->inputRecieveDate),
                ];
            }
            $data = [
                'status' => true,
                'message' => 'โหลดข้อมูลสำเร็จ',
                'data' => $info
            ];
        }

        return response()->json($data);
    }
}
