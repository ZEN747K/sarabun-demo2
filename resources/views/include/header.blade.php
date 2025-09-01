<nav class="pc-sidebar pc-trigger pc-sidebar-hide"></nav>
<style>
  .dropdown-user-profile .pc-h-dropdown{ max-width: 480px; }
  .dropdown-user-profile .list-group{ max-height: 360px; overflow-y: auto; }
  .dropdown-user-profile .list-group-item{ white-space: normal; word-break: break-word; overflow-wrap: anywhere; line-height: 1.35; }
  .dropdown-user-profile .user-role{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.35; }
  .dropdown-user-profile .role-text{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.35; }
  .dropdown-user-profile .dropdown-header .flex-grow-1{ min-width:0; padding-right:48px; }
  .dropdown-user-profile .dropdown-header .logout-wrap{ flex-shrink:0; margin-left:8px; align-self:flex-start; }
</style>
<header class="pc-header">
    <div class="header-wrapper d-flex justify-content-between align-items-center">
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled d-flex align-items-center mb-0">
                @if(auth()->user()->permission_id == 1 || auth()->user()->permission_id == 9)
                <a href="{{url('book')}}" type="button" class="btn btn-outline-primary {{($function_key == 'index') ? 'active' : ''}}" style="margin-right:10px">นำเข้าหนังสือ</a>
                @endif
                <a href="{{url('book/show')}}" type="button" class="btn btn-outline-primary {{($function_key == 'show') ? 'active' : ''}}" style="margin-right:10px">รายการหนังสือ</a>
                @if(auth()->user()->permission_id == 9)
                <a href="{{url('users/listUsers')}}" type="button" class="btn btn-outline-primary {{($function_key == 'listUsers') ? 'active' : ''}}" style="margin-right:10px">ข้อมูลสมาชิก</a>
                <a href="{{url('permission')}}" type="button" class="btn btn-outline-primary {{($function_key == 'permission') ? 'active' : ''}}" style="margin-right:10px">สิทธิการใช้งาน</a>
                @endif
                <a href="{{url('tracking')}}" type="button" class="btn btn-outline-primary {{($function_key == 'tracking') ? 'active' : ''}}" style="margin-right:10px">ติดตามสถานะ</a>
                <a href="{{url('bookSender')}}" type="button" class="btn btn-outline-primary {{($function_key == 'bookSender') ? 'active' : ''}}" style="margin-right:10px">ส่งหนังสือ</a>
                <a href="{{url('listSender')}}" type="button" class="btn btn-outline-primary {{($function_key == 'listSender') ? 'active' : ''}}" style="margin-right:10px">สมุดทะเบียนส่ง</a>
                <a href="{{url('pdfs')}}" type="button" class="btn btn-outline-primary {{($function_key == 'deepdetail') ? 'active' : ''}}" style="margin-right:10px">deep detail</a>
                <a href="{{url('directory')}}" type="button" class="btn btn-outline-primary {{($function_key == 'directory') ? 'active' : ''}}" style="margin-right:10px">แฟ้มเอกสาร</a>
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="list-unstyled">
                <li class="dropdown pc-h-item header-user-profile">
                    <h6 class="mb-1">
                        <?= auth()->user()->fullname ?>
                    </h6>
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" data-bs-auto-close="outside" aria-expanded="false">
                        <img src="{{asset('dist/assets/images/user/avatar-2.jpg')}}" alt="user-image" class="user-avtar" style="width:35px">
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown" style="max-width:450px;">
                        <div class="dropdown-header">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <a href="{{url('/users/edit/'.auth()->user()->id)}}">
                                        <img src="{{asset('dist/assets/images/user/avatar-2.jpg')}}" alt="user-image" class="user-avtar wid-35">
                                    </a>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?= auth()->user()->fullname ?></h6>
                                    <span class="user-role"><?= session()->get('permission_name') ?> <?= $permission_data->permission_name ?></span>
                                </div>
                                <div class="logout-wrap">
                                    <a href="{{url('/login/logout')}}" class="pc-head-link bg-transparent"><i class="fa fa-sign-out"></i></a>
                                </div>
                            </div>
                            <div class="card">
                                <ul class="list-group list-group-flush">
                                    <?php $role = role_user();
                                    foreach ($role as &$rs) {
                                        $active = '';
                                        if ($rs->permission_id == auth()->user()->permission_id && $rs->position_id == auth()->user()->position_id) {
                                            $active = 'active';
                                        }
                                    ?>
                                        <a href="/users/change_role/<?= $rs->id ?>">
                                            <li class="list-group-item <?= $active ?>" style="padding:10px;">
                                                <span class="role-text"><?= $rs->permission_name . ' ' . $rs->position_name ?></span>
                                            </li>
                                        </a>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
