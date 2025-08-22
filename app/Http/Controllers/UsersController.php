<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use App\Models\Users_permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class UsersController extends Controller
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
    public function listUsers()
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = __FUNCTION__;
        return view('users.index', $data);
    }
    public function add()
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'addUser';
        return view('users.add', $data);
    }

    public function addForm()
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'addUser';
        $data['position'] = Position::get();
        return view('users.addForm', $data);
    }

    public function insertUser(Request $request)
    {
        $input = $request->input();
        $users = new User();
        if (isset($input['fullname'])) {
            $users->fullname = $input['fullname'];
        }
        if (isset($input['email'])) {
            $users->email = $input['email'];
        }
        $password = !empty($input['password']) ? $input['password'] : '4321';
        $users->password = password_hash($password, PASSWORD_DEFAULT);
        if (isset($input['position_id'])) {
            $users->position_id = $input['position_id'];
        }
        if (isset($input['permission_id'])) {
            $users->permission_id = $input['permission_id'];
        }
        $users->created_at = date('Y-m-d H:i:s');
        $users->updated_at = date('Y-m-d H:i:s');
        if ($users->save()) {
            return redirect()->route('users.add')->with('success', 'เพิ่มผู้ใช้สำเร็จ');
        }
    }

    public function listData()
{
    $users = User::select(
            'users.*',
            DB::raw('permissions.permission_name as role_name'),   
            DB::raw('positions.position_name   as department')    
        )
        ->whereNot('permission_id', '9')
        ->leftJoin('permissions', 'users.permission_id', '=', 'permissions.id')
        ->leftJoin('positions',   'users.position_id',   '=', 'positions.id')
        ->orderBy('users.id', 'asc')
        ->get()
        ->map(function ($u) {
            $primaryPerm = Permission::find($u->permission_id);
            $isReceiver = false;
            if ($primaryPerm && $primaryPerm->parent_id) {
                $isReceiver = Users_permission::where('users_id', $u->id)
                    ->where('permission_id', $primaryPerm->parent_id)
                    ->where('position_id', $u->position_id)
                    ->exists();
            }
            $u->is_receiver = $isReceiver;
            $u->action = '<a href="'.url('/users/permission/'.$u->id).'" class="btn btn-sm btn-outline-primary m-1"><i class="fa fa-users"></i></a>'
                       . '<a href="'.url('/users/edit/'.$u->id).'" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>';
            return $u;
        });

    return response()->json(['data' => $users]);
}




    public function edit($id)
    {
        if ($this->permission_id != 9) {
            if ($id != auth()->user()->id) {
                return redirect('/users/edit/' . auth()->user()->id);
            }
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'listUsers';
        $users = User::find($id);
        $data['data'] = $users;
        return view('users.edit', $data);
    }

    public function save(Request $request)
    {
        $input = $request->input();
        $users = User::find($input['id']);
        if ($users) {
            $users->fullname = $input['fullname'];
            if (isset($input['email'])) {
                $users->username = $input['email'];
            }
            if (!empty($input['password'])) {
                $users->password_email = $input['password'];
            }
            if (!empty($input['passwordLogin'])) {
                $users->password = password_hash($input['passwordLogin'], PASSWORD_DEFAULT);
            }
            $users->updated_at = date('Y-m-d H:i:s');
            if ($request->hasFile('formFile')) {
                $file = $request->file('formFile');
                $filePath = $file->store('users', 'public');
                $filePath = str_replace('users/', '', $filePath);
                $users->signature = $filePath;
            }
            if ($users->save()) {
                if ($input['id'] != auth()->user()->id) {
                    return redirect()->route('users.listUsers')->with('success', 'แก้ไขข้อมูลสำเร็จ');
                } else {
                    return redirect('/users/edit/' . $input['id'])->with('success', 'แก้ไขข้อมูลสำเร็จ');
                }
            }
        }
    }

    public function change_role($id)
    {
        $users = User::find(auth()->user()->id);
        $role = Users_permission::find($id);
        if ($role) {
            $users->permission_id = $role->permission_id;
            $users->position_id = $role->position_id;
            if ($users->save()) {
                return redirect()->back();
            }
        }
    }

    public function edit_permission($id)
    {
        if ($this->permission_id != 9) {
            if ($id != auth()->user()->id) {
                return redirect('/users/edit/' . auth()->user()->id);
            }
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'listUsers';
        $data['id'] = $id;

        return view('users.permission', $data);
    }

    public function listDataPermission(Request $request)
    {
        $id = $request->input('id');
        $role = Users_permission::select(
                'users_permissions.id',
                'users_permissions.permission_id',
                'permissions.permission_name',
                'permissions.can_status',
                'positions.position_name'
            )
            ->leftJoin('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->leftJoin('positions', 'users_permissions.position_id', '=', 'positions.id')
            ->where('users_permissions.users_id', $id)
            ->get();
        
        foreach ($role as $key => $value) {
            $escapedStatus = htmlspecialchars($value->can_status ?? '', ENT_QUOTES, 'UTF-8');
            $value->can_status_input = '<input type="text" name="can_status[' . $value->permission_id . ']" value="' . $escapedStatus . '" class="form-control form-control-sm" />';
            $value->action = '<a href="' . url('/users/form_permission/' . $value->id) . '" class="btn btn-sm btn-outline-primary m-1"><i class="fa fa-edit"></i></a>'
                .'<a href="' . url('/users/delete/' . $value->id) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-trash-o"></i></a>';
        }
        return response()->json(['data' => $role]);
    }

    public function updateCanStatus(Request $request)
    {
        if ($this->permission_id != 9) {
            return redirect('/users/edit/' . auth()->user()->id);
        }

        $statuses = $request->input('can_status', []);
        foreach ($statuses as $permissionId => $status) {
            $permission = Permission::find($permissionId);
            if ($permission) {
                $permission->can_status = $status;
                $permission->updated_by = auth()->user()->id;
                $permission->updated_at = date('Y-m-d H:i:s');
                $permission->save();
            }
        }

        return redirect()->route('users.permission', ['id' => $request->input('user_id')])->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function create_permission($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/users/edit/' . auth()->user()->id);
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'listUsers';
        $data['action'] = '/users/insertPermission';
        $data['permission'] = Permission::get();
        $data['position'] = Position::get();
        $data['id'] = $id;
        return view('users.create', $data);
    }

    public function form_permission($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/users/edit/' . auth()->user()->id);
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'listUsers';
        $info = Users_permission::find($id);
        $data['info'] = $info;
        $data['action'] = '/users/updatePermission';
        $data['permission'] = Permission::get();
        $data['position'] = Position::get();
        return view('users.form', $data);
    }

    public function insertPermission(Request $request)
    {
        $input = $request->input();
        $permission = new Users_permission();
        $permission->permission_id = $input['select_permission'];
        if (isset($input['select_position'])) {
            $permission->position_id = $input['select_position'];
        }
        $permission->users_id = $input['id'];
        $permission->created_by = auth()->user()->id;
        $permission->created_at = date('Y-m-d H:i:s');
        $permission->updated_by = auth()->user()->id;
        $permission->updated_at = date('Y-m-d H:i:s');
        if ($permission->save()) {
            return redirect()->route('users.listUsers')->with('success', 'แก้ไขข้อมูลสำเร็จ');
        } else {
            return redirect('/users/edit/' . $input['id'])->with('success', 'แก้ไขข้อมูลสำเร็จ');
        }
    }

    public function updatePermission(Request $request)
    {
        $input = $request->input();
        $update = Users_permission::find($input['id']);
        if ($update) {
            $update->permission_id = $input['select_permission'];
            $update->position_id = $input['select_position'];
            $update->updated_by = auth()->user()->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                return redirect()->route('users.permission', ['id' => $update->users_id])->with('success', 'แก้ไขข้อมูลสำเร็จ');
            }
            return redirect()->back()->with('error', 'แก้ไขข้อมูลไม่สำเร็จ');
        }
    }

    public function sync()
    {
        $users = User::get();
        foreach ($users as $rs) {
            $role = Users_permission::select('users_permissions.*', 'permissions.permission_name', 'positions.position_name')
                ->leftJoin('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
                ->leftJoin('positions', 'users_permissions.position_id', '=', 'positions.id')
                ->where('users_permissions.users_id', $rs->id)
                ->get();
            if (count($role) != 0) {
                $users = User::find($rs->id);
                $users->permission_id = $role[0]->permission_id;
                $users->position_id = $role[0]->position_id;
                $users->save();
            }
        }
    }

    public function getPermission(Request $request)
    {
        $input = $request->input();
        $info = Permission::where('position_id', $input['id'])->get();
        $html = '<option value="" disabled>กรุณาเลือก</option>';
    if (count($info) > 0) {
        foreach ($info as $rs) {
            $selected = '';
            if (isset($input['permission_id']) && $input['permission_id'] == $rs->id) {
                $selected = 'selected';
            }
            $html .= '<option value="' . $rs->id . '" ' . $selected . '>' . $rs->permission_name . '</option>';
        }
    }
    return response()->json($html);
}
public function deletePermission($id)
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $info = Users_permission::find($id);
        if ($info) {
            $userId = $info->users_id;
            $info->delete();
            return redirect()->route('users.permission', ['id' => $userId])->with('success', 'ลบข้อมูลสำเร็จ');
        }
        return redirect()->back()->with('error', 'ไม่พบข้อมูล');
    }
   public function toggleReceiver(Request $request) {
    $request->validate([
        'id' => 'required|integer'
    ]);

    $user = User::find($request->id);
    if (!$user) {
        return response()->json(['ok' => false, 'msg' => 'ไม่พบผู้ใช้'], 404);
    }

    $primaryPerm = Permission::find($user->permission_id);
    if (!$primaryPerm || !$primaryPerm->parent_id) {
        return response()->json([
            'ok' => false,
            'need_parent' => true,
            'permission_id' => $primaryPerm?->id,
            'position_id'   => $user->position_id,
            'msg' => 'สิทธิ์นี้ไม่มีผู้บังคับบัญชาที่กำหนด (parent_id)'
        ], 422);
    }

    $existing = Users_permission::where('users_id', $user->id)
        ->where('permission_id', $primaryPerm->parent_id)
        ->where('position_id', $user->position_id)
        ->first();

    if ($existing) {
        $existing->delete();
        return response()->json(['ok' => true, 'is_receiver' => false, 'msg' => 'ยกเลิกผู้รับแทงเรื่องแล้ว']);
    }

    Users_permission::create([
    'users_id'      => $user->id,
    'permission_id' => $primaryPerm->parent_id,
    'position_id'   => $user->position_id,
    'created_by'    => auth()->id(),
    'updated_by'    => auth()->id(),
    'created_at'    => now(),
    'updated_at'    => now(),
]);

    return response()->json(['ok' => true, 'is_receiver' => true, 'msg' => 'ตั้งเป็นผู้รับแทงเรื่องแล้ว']);
}
   public function parentOptions(Request $request) {
    $positionId = (int)$request->query('position_id');  
    $excludeId  = (int)$request->query('exclude');

    $q = Permission::query()
        ->leftJoin('positions', 'permissions.position_id', '=', 'positions.id')
        ->select(
            'permissions.id',
            'permissions.permission_name',
            'permissions.position_id',
            DB::raw('positions.position_name as department')
        )
        ->when($positionId, fn($qq) => $qq->where('permissions.position_id', $positionId))
        ->when($excludeId,  fn($qq) => $qq->where('permissions.id', '<>', $excludeId))
        ->orderBy('positions.position_name')
        ->orderBy('permissions.permission_name');

    return response()->json($q->get());
}

public function setParent(Request $request) {
    $v = Validator::make($request->all(), [
        'permission_id' => 'required|integer|exists:permissions,id',
        'parent_id'     => 'required|integer|exists:permissions,id'
    ], [], [
        'permission_id' => 'สิทธิ์',
        'parent_id'     => 'ผู้บังคับบัญชา',
    ]);

    if ($v->fails()) {
        return response()->json(['ok'=>false,'msg'=>$v->errors()->first()], 422);
    }

    $perm = Permission::find($request->permission_id);
    $perm->parent_id = (int)$request->parent_id;
    $perm->updated_by = auth()->id();
    $perm->updated_at = now();
    $perm->save();

    return response()->json(['ok'=>true, 'msg'=>'บันทึกผู้บังคับบัญชาเรียบร้อย']);
}


}
