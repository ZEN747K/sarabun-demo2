<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\ClientManager;

class EmailController extends Controller
{
    public function index()
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
        $messages = $folder->query()->all()->get();
        $totalMessages = $folder->query()->all()->count();

        foreach ($messages as $message) {
            $uid = $message->getUid();
            $data['title'] = $message->getSubject() . '';
            $data['sender'] = $message->getFrom() . '';
            $data['date'] = $message->getDate() . '';
            if ($message->hasAttachments()) {
                $attachments = $message->getAttachments();

                foreach ($attachments as $attachment) {
                    $url = 'https://webmail.plaengyao.go.th/roundcube/?_task=mail&_frame=1&_mbox=INBOX&_uid=' . $uid . '&_part=2&_action=get&_extwin=1';
                    $data['url'] = '<a href="' . $url . '" target="_blank">ไฟล์</a>';
                    // $attachment->save(storage_path('app/attachments/'));
                }
            }
            dd($data);
        }
    }
}
