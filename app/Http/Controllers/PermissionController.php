<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
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
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'permission';
        return view('permission.index', $data);
    }

    public function detail($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'permission';
        $data['id'] = $id;
        return view('permission.detail', $data);
    }


    public function listData()
    {
        $info = Position::orderBy('id', 'asc')->get();
        foreach ($info as $key => $value) {
            $value->action = '<a href="' . url('/permission/detail/' . $value->id) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>';
        }
        $data['data'] = $info;
        return response()->json($data);
    }

    public function listDataPermission(Request $request)
    {
        $id = $request->input('id');
        $info = Permission::where('position_id', $id)->orderBy('id', 'asc')->get();
        foreach ($info as $key => $value) {
            $value->action = '<a href="' . url('/permission/edit/' . $value->id) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>';
        }
        $data['data'] = $info;
        return response()->json($data);
    }

    public function create($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'permission';
        $data['id'] = $id;
        $data['item'] = Permission::where('position_id', $id)->get();
        return view('permission.form', $data);
    }

    public function edit($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'permission';
        $data['info'] = Permission::find($id);
        $data['item'] = Permission::where('position_id', $data['info']->position_id)->get();
        return view('permission.form', $data);
    }

    public function save(Request $request)
    {
        $input = $request->input();
        $value = $this->_build_data($input);
        if ($input['id']) {
            $query = Permission::find($input['id']);
            $query->permission_name = $input['name'];
            $query->can_status = $value;
            $query->position_id = $input['position_id'];
            $query->updated_by = auth()->user()->id;
            $query->updated_at = date('Y-m-d H:i:s');
            if ($input['selectParent']) {
                $query->parent_id = $input['selectParent'];
            }
            if ($query->save()) {
                return redirect('/permission/detail/' . $input['position_id'])->with('success', 'บันทึกข้อมูลสำเร็จ');
            } else {
                return redirect('/permission')->with('error', 'แก้ไขข้อมูลไม่สำเร็จ');
            }
        } else {
            $query = new Permission();
            $query->permission_name = $input['name'];
            $query->can_status = $value;
            $query->created_by = auth()->user()->id;
            $query->created_at = date('Y-m-d H:i:s');
            $query->updated_by = auth()->user()->id;
            $query->updated_at = date('Y-m-d H:i:s');
            $query->position_id = $input['position_id'];
            if ($input['selectParent']) {
                $query->parent_id = $input['selectParent'];
            }
            if ($query->save()) {
                return redirect('/permission/detail/' . $input['position_id'])->with('success', 'บันทึกข้อมูลสำเร็จ');
            } else {
                return redirect('/permission')->with('error', 'แก้ไขข้อมูลไม่สำเร็จ');
            }
        }
    }

    public function _build_data($input)
    {
        $value = '';
        $i = 0;
        foreach ($input['checkbox'] as $rs) {
            if ($i >= 1) {
                $value .= ',';
            }
            switch ($rs) {
                case '1':
                    $value .= '1,2,3,4,5,6,7,8,9,10,11,12';
                    break;
                case '2':
                    $value .= '1,2';
                    break;
                case '3':
                    $value .= '3,3.5';
                    break;
                case '4':
                    $value .= '4,5';
                    break;
                case '5':
                    $value .= '6,7';
                    break;
                case '6':
                    $value .= '8,9';
                    break;
                case '7':
                    $value .= '10,11';
                    break;
                case '8':
                    $value .= '12,13';
                    break;

                default:
                    # code...
                    break;
            }
            $i++;
        }
        return $value;
    }
}
