<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
header('Content-Type: text/html; charset=utf-8');

require_once "settings.php";
require_once "simple_html_dom.php";
require_once "amocrm.php";

$imap = imap_open( $imap_server, $mail, $mail_password ) or die('Cannot connect to Yandex Mail: ' . imap_last_error());
//$emails = imap_search($inbox,'ALL');
$message_count = imap_num_msg($imap);
//print $message_count;
$cnt_new_lead = 0;
for ($i = 1; $i <= $message_count; ++$i) {
    $header = imap_header($imap, $i);

    if($header->udate < strtotime($start_handle_from)) {
        continue;
    }
    
    $charset_pos = strpos($header->subject, '=?koi8-r?B?');

    if ($charset_pos === false) {
        $subj = $header->subject;
        $body = imap_body($imap, $i);
    } else {
        $subj = str_replace('=?koi8-r?B?','',$header->subject);
        $subj = str_replace('?=','',$subj);
        $subj = iconv('koi8-r','utf-8',base64_decode($subj));
        $body = imap_body($imap, $i);
        $body = iconv('koi8-r','utf-8',$body);
    }

    //var_dump($body);
    
    
    if (preg_match("/Имя :(.+?)Телефон/", $body, $matches)) {
        $name = trim($matches[1]); 
    }
    else {
        if (preg_match("/Имя :(.+?)\\n/", $body, $matches)) {
            $name = trim($matches[1]);
        }
        else {
            $name = "";
        }
    }
    $name = str_replace("<br />", "", $name);

    if (preg_match("/Телефон :(.+?)\<br \/\>/", $body, $matches)) {
        $phone = trim($matches[1]);
        $phone = str_replace("<span>", "", $phone);
        $phone = str_replace("</span>", "", $phone);
    }
    else {
        if (preg_match("/Телефон :(.+?)\\n/", $body, $matches)) {
            $phone = trim($matches[1]);
        }
        else {
            $phone = "";
        }
    }

    
    if (preg_match("/Сообщение :(.+?)\<br \/\>/", $body, $matches)) {
        $msg = trim($matches[1]); 
    }
    else {
        if (preg_match("/Сообщение :(.+?)\\n/", $body, $matches)) {
            $msg = trim($matches[1]);
        }
        else {
            $msg = "";
        }
    }

    if (preg_match('/<a href="(.+)">/', $body, $matches)) {
        $email = trim($matches[1]); 
        $email = str_replace("mailto:", "", $email);
    }
    else {
        if (preg_match("/E-mail :(.+?)\\n/", $body, $matches)) {
            $email = trim($matches[1]);
        }
        else {
            $email = "";
        }
    }
    
    
    if (preg_match("/Удобрение :(.+?)\<br \/\>/", $body, $matches)) {
        $fertilizer = trim($matches[1]); 
    }
    else {
        if (preg_match("/Удобрение :(.+?)\\n/", $body, $matches)) {
            $fertilizer = trim($matches[1]);
        }
        else {
            $fertilizer = "";
        }
    }
    
    if (preg_match("/Объём удоборений :(.+?)\<br \/\>/", $body, $matches)) {
        $volume_fertilizer = trim($matches[1]); 
    }
    else {
        if (preg_match("/Объём удоборений :(.+?)\\n/", $body, $matches)) {
            $volume_fertilizer = trim($matches[1]);
        }
        else {
            $volume_fertilizer = "";
        }
    }
    
    if (preg_match("/Адрес доставки :(.+?)\<br \/\>/", $body, $matches)) {
        $address = trim($matches[1]); 
    }
    else {
        if (preg_match("/Адрес доставки :(.+?)\\n/", $body, $matches)) {
            $address = trim($matches[1]);
        }
        else {
            $address = "";
        }
    }
    
    if (preg_match("/source :(.+?)\<br \/\>/", $body, $matches)) {
        $utm_source = trim($matches[1]); 
    }
    else {
        if (preg_match("/source :(.+?)\\n/", $body, $matches)) {
            $utm_source = trim($matches[1]);
        }
        else {
            $utm_source = "";
        }
    }
    
    if (preg_match("/medium :(.+?)\<br \/\>/", $body, $matches)) {
        $utm_medium = trim($matches[1]); 
    }
    else {
        if (preg_match("/medium :(.+?)\\n/", $body, $matches)) {
            $utm_medium = trim($matches[1]);
        }
        else {
            $utm_medium = "";
        }
    }
    
    if (preg_match("/campaign :(.+?)\<br \/\>/", $body, $matches)) {
        $utm_campaign = trim($matches[1]); 
    }
    else {
        if (preg_match("/campaign :(.+?)\\n/", $body, $matches)) {
            $utm_campaign = trim($matches[1]);
        }
        else {
            $utm_medium = "";
        }
    }
    
    if (preg_match("/utm_content :(.+?)\<br \/\>/", $body, $matches)) {
        $utm_content = trim($matches[1]); 
    }
    else {
        if (preg_match("/utm_content :(.+?)\\n/", $body, $matches)) {
            $utm_content = trim($matches[1]);
        }
        else {
            $utm_content = "";
        }
    }
    
    if (preg_match("/utm_term :(.+?)\<\/p>/", $body, $matches)) {
        $utm_term = urldecode(trim($matches[1])); 
    }
    else {
        if (preg_match("/utm_term :(.+?)\\n/", $body, $matches)) {
            $utm_term = trim($matches[1]);
        }
        else {
            $utm_term = "";
        }
    }

    /*
    var_dump($utm_source);
    var_dump($utm_medium);
    var_dump($utm_campaign);
    var_dump($utm_content);
    var_dump($utm_term);
    */
    $ga_utm = "";
 
    
    $datetime = date("d.m.y h:i:s", $header->udate);
    $prettydate = date("jS F Y", $header->udate);
    $timestamp = strval($header->udate);
    
    $date_file = "messages.txt";
    $date_contains = file_get_contents($date_file, true);

    if( strpos($date_contains, $timestamp) === false) {
		$cnt_new_lead += 1;
        file_put_contents($date_file, $timestamp."\n", FILE_APPEND);
        send_to_amocrm($name, $phone, $email, $msg, $fertilizer, $volume_fertilizer, $address, $datetime, $utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term, $ga_utm);
    }

    //var_dump($prettydate);

    if (isset($header->from[0]->personal)) {
        $personal = $header->from[0]->personal;
    } else {
        $personal = $header->from[0]->mailbox;
    }

    $email = "$personal <{$header->from[0]->mailbox}@{$header->from[0]->host}>";
    //var_dump($email);
}

imap_close($imap);

echo "Добавлено новых сделок: ".$cnt_new_lead;
