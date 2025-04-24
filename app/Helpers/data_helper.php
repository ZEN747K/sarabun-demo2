<?php

use App\Models\Book;
use App\Models\BookModel;
use App\Models\Log_active_book;
use App\Models\Log_status_book;
use App\Models\User;
use App\Models\Users_permission;

function status_helper($data)
{
    $status = [
        1 => 'รอลงเวลานำเข้า',
        2 => 'รอส่งต่อไปยังแผนก',
        3 => 'รอธุรการลงรับ',
        4 => 'ธุรการลงรับเรียบร้อย',
        5 => 'รอผู้จัดการลงลายเซ็น',
        6 => 'ผู้จัดการลงลายเซ็นเรียบร้อย',
    ];
    return $status[$data];
}

function DateThai($strDate)
{
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $strMonthThai = $strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}

function DateTimeThai($strDate)
{
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $time = date("H:i", strtotime($strDate));
    $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
    $strMonthThai = $strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear" . " " . $time;
}

function numberToThaiDigits($num)
{
    $thaiDigits = array(
        '0' => '๐',
        '1' => '๑',
        '2' => '๒',
        '3' => '๓',
        '4' => '๔',
        '5' => '๕',
        '6' => '๖',
        '7' => '๗',
        '8' => '๘',
        '9' => '๙'
    );
    $num = strval($num);
    $thaiNum = '';
    for ($i = 0; $i < strlen($num); $i++) {
        $thaiNum .= $thaiDigits[$num[$i]];
    }
    return $thaiNum;
}

function convertDayToThai($date)
{
    $day = date('d', strtotime($date));
    if (!$day) {
        return "วันที่ไม่ถูกต้อง";
    }

    $day = $day;

    $dayThai = numberToThaiDigits($day);
    return $dayThai;
}

function convertMonthsToThai($date)
{
    $string = date('m', strtotime($date));
    if (!$string) {
        return "วันที่ไม่ถูกต้อง";
    }

    $months = array(
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    );
    $monthThai = $months[$string];
    return $monthThai;
}
function convertYearsToThai($date)
{
    $years = date('Y', strtotime($date));
    if (!$years) {
        return "วันที่ไม่ถูกต้อง";
    }
    $year = $years + 543;

    $yearThai = numberToThaiDigits($year);
    return $yearThai;
}

function convertDateToThai($date)
{
    $months = array(
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    );

    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime) {
        return "วันที่ไม่ถูกต้อง";
    }

    $day = $dateTime->format('d');
    $month = (int) $dateTime->format('m');
    $year = (int) $dateTime->format('Y') + 543;

    $dayThai = numberToThaiDigits($day);
    $monthThai = $months[$month];
    $yearThai = numberToThaiDigits($year);
    return $dayThai . " " . $monthThai . " " . $yearThai;
}


function convertTimeToThai($time)
{
    list($hours, $minutes, $seconds) = explode(":", $time);

    $hoursThai = numberToThaiDigits($hours);
    $minutesThai = numberToThaiDigits($minutes);

    // คืนค่าผลลัพธ์ในรูปแบบที่แปลงแล้ว
    return $hoursThai . ":" . $minutesThai;
}

function adminNumber()
{
    $books = Log_status_book::where('position_id', auth()->user()->position_id)->orderBy('adminBooknumber', 'desc')->first();
    if ($books) {
        $adminBooknumber = $books->adminBookNumber + 1;
    } else {
        $adminBooknumber = 1;
    }

    return $adminBooknumber;
}

function log_active($data)
{
    $logs = new Log_active_book();
    $logs->users_id = $data['users_id'];
    $logs->status = $data['status'];
    $logs->datetime = $data['datetime'];
    $logs->detail = $data['detail'];
    $logs->book_id = $data['book_id'];
    if (isset($data['position_id'])) {
        $logs->position_id = $data['position_id'];
    }
    $logs->created_at = date('Y-m-d H:i:s');
    $logs->updated_at = date('Y-m-d H:i:s');
    if ($logs->save()) {
        $message = 'บันทึกสำเร็จ';
    } else {
        $message = 'บันทึกไม่สำเร็จ';
    }
    return $message;
}


function role_user()
{
    $role = Users_permission::select('users_permissions.*', 'permissions.permission_name', 'positions.position_name')
        ->leftJoin('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
        ->leftJoin('positions', 'users_permissions.position_id', '=', 'positions.id')
        ->where('users_permissions.users_id', auth()->user()->id)
        ->get();
    if (count($role) <= 0) {
        $insert  = new Users_permission();
        $insert->permission_id = auth()->user()->permission_id;
        $insert->position_id = auth()->user()->position_id;
        $insert->users_id = auth()->user()->id;
        $insert->created_by = auth()->user()->id;
        $insert->created_at = date('Y-m-d H:i:s');
        $insert->updated_by = auth()->user()->id;
        $insert->updated_at = date('Y-m-d H:i:s');
        if ($insert->save()) {
            $role = Users_permission::select('users_permissions.*', 'permissions.permission_name', 'positions.position_name')
                ->leftJoin('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
                ->leftJoin('positions', 'users_permissions.position_id', '=', 'positions.id')
                ->where('users_permissions.users_id', auth()->user()->id)
                ->get();
        }
    }
    return $role;
}
