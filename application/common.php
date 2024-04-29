<?php

// 公共助手函数

use think\exception\HttpResponseException;
use think\Response;

function apiRequest($method, $parameters) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    foreach ($parameters as $key => &$val) {
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }
    $url = 'https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/'.$method.'?'.http_build_query($parameters);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    if(!empty($result)){
        $jsontoarray = json_decode($result,true);
        if($jsontoarray['ok'] && is_array($jsontoarray['result'])){
            //echo "发送消息：" . date("Y-m-d H:i:s") . "\t " . $result . " \n\n";
            $output->writeln("发送消息：" . date("Y-m-d H:i:s") . "\t " . $jsontoarray['result']['message_id']);
        }
    }
    curl_close($ch);
    return json_decode($result, true);
}
function getconfig($conn){
    $sql = "select * from config";
    $configarr = $conn->query($sql);
    $config = [];
    if($configarr){
        foreach ($configarr as $value) {
            $config[$value['key']] = $value['value'];
        }
    }
    return $config;
}

function unicodeEncode($str){
    //split word
    preg_match_all('/./u',$str,$matches);
 
    $unicodeStr = "";
    foreach($matches[0] as $m){
        //拼接
        $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
    }
    return $unicodeStr;
}
// $para = chat or from
function getMentionName($para){
    $name = $para['first_name'];
    if ($para['last_name']) {
        $name .= " " . $para['last_name'];
    }
    if ($para['username']){
        $name = $para['username'];
    }
    return "[" . $name . "](tg://user?id=" . $para['id'] . ")";
}

function getChatNum($conn,$chat_id){
    $result = file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChatMembersCount?chat_id=" . $chat_id);
    $num = json_decode($result, true); //获取群组人数
    if($num['ok']){
        $num = isset($num['result']) ? $num['result'] : 0;

        $sql = "UPDATE `groupinfo` SET `num` = $num WHERE id = '$chat_id'";

        $row = $conn->query($sql);
    }
}

function getChatAdministrators($conn,$chat_id){
    $me = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getMe"), true); //获取机器人信息
    $list = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChatAdministrators?chat_id=" . $chat_id), true); //获取管理员列表
    if($me['ok'] && $list['ok']){
        foreach ($list['result'] as $key => $val) {
            if($me['result']['id'] == $val['user']['id']){
                return true;
            }
        }
    }
    return false;
}

function getChat($conn,$chat_id){
    $me = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getMe"), true); //获取机器人信息
    $list = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChatAdministrators?chat_id=" . $chat_id), true); //获取管理员列表
    $chat = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChat?chat_id=" . $chat_id), true); //获取群组名称和权限
    $num = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChatMembersCount?chat_id=" . $chat_id), true); //获取群组人数
    if($chat['ok'] && $num['ok']){
        $datetime = date("Y-m-d H:i:s");
        $title = isset($chat['result']['title']) ? base64_encode($chat['result']['title']) : "";
        $description = isset($chat['result']['description']) ? base64_encode($chat['result']['description']) : "";
        $invite_link = isset($chat['result']['invite_link']) ? $chat['result']['invite_link'] : "";
        $username = isset($chat['result']['username']) ? $chat['result']['username'] : "";
        $num = isset($num['result']) ? $num['result'] : 0;
        
        $sql = "select * from `groupinfo` where id = '$chat_id'";
        $res = $conn->query($sql)[0];
        if($res){
            $sqlstr = "`dt` = '$datetime' , `status` = 1 , `title` = '$title' , `description` = '$description' , `invite_link` = '$invite_link' , `username` = '$username' , `num` = $num";
            $sql = "UPDATE `groupinfo` SET $sqlstr WHERE id = '$chat_id'";
        } else {
            $sqlkey = "id,dt,title,description,invite_link,username,num";
            $sqlval = "'$chat_id','$datetime','$title','$description','$invite_link','$username',$num";
            $sql = "INSERT INTO `groupinfo` ( $sqlkey ) VALUES ( $sqlval )";
        }
        $row = $conn->query($sql);
    }
    if($me['ok'] && $list['ok']){
        foreach ($list['result'] as $key => $val) {
            if($me['result']['id'] == $val['user']['id']){
                return $val;
            }
        }
    }
    return false;
}

function getInline($conn,$chat_id){
    if($chat_id < 0){
        $me_inline = getItem($conn);
        $it_inline = getItem($conn,$chat_id);
        $me_advert = [];
        $step = 0;
        foreach ($me_inline as $key => $val) {
            $sm = json_decode($val['advert'], true);
            if(!empty($sm)){
                $keys = array_keys($sm);
                foreach ($keys as $k => $v) {
                    if($v == $val['step']){
                        if(isset($keys[$k + 1])){
                            $sql = "UPDATE `advert` SET `step` = " . $keys[$k + 1] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                        }else{
                            $sql = "UPDATE `advert` SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                        }
                        $row = $conn->query($sql);
                        break;
                    }else{
                        if($v = end($keys)){
                            $sql = "UPDATE `advert` SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                            $row = $conn->query($sql);
                        }
                    }
                }
            }
            $me_advert[] = $sm[$val['step']];
        }
        
        $it_advert = [];
        foreach ($it_inline as $key => $val) {
            $sm = json_decode($val['advert'], true);
            if(!empty($sm)){
                $keys = array_keys($sm);
                foreach ($keys as $k => $v) {
                    if($v == $val['step']){
                        if(isset($keys[$k + 1])){
                            $sql = "UPDATE `advert` SET `step` = " . $keys[$k + 1] . " WHERE `id` = '" . $val['id'] . "' and `ident` = " . $val['ident'];
                        }else{
                            $sql = "UPDATE `advert` SET `step` = " . $keys[0] . " WHERE `id` = '" . $val['id'] . "' and `ident` = " . $val['ident'];
                        }
                        $row = $conn->query($sql);
                        break;
                    }else{
                        if($v = end($keys)){
                            $sql = "UPDATE `advert` SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                            $row = $conn->query($sql);
                        }
                    }
                }
            }
            $it_advert[] = $sm[$val['step']];
        }
        
        $all_advert = [];
        for ($i = 0; $i < (5 - count($it_advert)); $i++) {
            if(!empty($me_advert[$i])){
                $all_advert[] = $me_advert[$i];
            }
            
        }
        foreach ($it_advert as $key => $val) {
            $all_advert[] = $val;
        }
        $inlineKeyboardMarkup['inline_keyboard'] = [];
        foreach ($all_advert as $key => $val) {
            $inlineKeyboardMarkup['inline_keyboard'][][] = ['text' => base64_decode($val['text']),'url' => $val['url']];
        }
        $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "还想看",'callback_data' => "see"],['text' => "长视频",'callback_data' => "long"]];
        return $inlineKeyboardMarkup;
    }
    $me_inline = getItem($conn);
    $me_advert = [];
    $step = 0;
    foreach ($me_inline as $key => $val) {
        $sm = json_decode($val['advert'], true);
        if(isset($sm[$val['step']])){
            $me_advert[] = $sm[$val['step']];
        }else{
            $me_advert[] = $sm[1];
        }
    }
    if($me_inline[0]['step'] == 3){
        $step = 1;
    }else{
        $step = $me_inline[0]['step'] + 1;
    }
    $sql = "UPDATE `advert` SET `step` = $step WHERE id = '-1001000000001'";
    $row = $conn->query($sql);
    $inlineKeyboardMarkup['inline_keyboard'] = [];
    foreach ($me_advert as $key => $val) {
        $inlineKeyboardMarkup['inline_keyboard'][][] = ['text' => base64_decode($val['text']),'url' => $val['url']];
    }
    $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "还想看",'callback_data' => "see"],['text' => "长视频",'callback_data' => "long"]];
    return $inlineKeyboardMarkup;
}

function getItem($conn,$chat_id = '-1001000000001'){
    $sql = "select * from `advert` where `id` = '$chat_id'";
    return $conn->query($sql);
}

function sendMessage($para) {
    $message = $para['message'];
    $chat_id = $message['chat']['id'];
    /*if(isset($message['new_chat_participant'])){
        apiRequest('deleteMessage', ['chat_id' => $chat_id,"message_id" => $message['message_id']]);
    } else if (isset($message['left_chat_participant'])){
        $sql = "DELETE FROM `groupinfo` WHERE id ='" . $chat_id . "'";
        $row = $conn->query($sql);
        if($row){
            apiRequest('deleteMessage', ['chat_id' => $chat_id,"message_id" => $message['message_id']]);
        }
    }*/
    if(isset($message['text'])){
        $text = $message['text'];
        if(strpos($text, "/start") === 0){
            $keyboardRow1[] = ['text' => "开车视频",'callback_data' => "see"];
            $keyboardRow2[] = ['text' => "广告投放请联系",'url' => "https://t.me/JiabaoAd"];
            $keyboardRow3[] = ['text' => "拉人¥只拉活粉¥3毛1人",'url' => "https://t.me/JiabaoAd"];
            $keyboardRow4[] = ['text' => "邀请机器人进群",'url' => "https://t.me/KaiCheDSbot?startgroup=start"];
            $inlineKeyboardMarkup['inline_keyboard'][] = $keyboardRow1;
            $inlineKeyboardMarkup['inline_keyboard'][] = $keyboardRow2;
            $inlineKeyboardMarkup['inline_keyboard'][] = $keyboardRow3;
            $inlineKeyboardMarkup['inline_keyboard'][] = $keyboardRow4;
            $payload = [
                'chat_id' => $chat_id,
                "text" => "使用方法：\n1.邀请机器人进群\n2.群里发送：  /kc\n机器人每6分钟定时开车\n----------------\n\n[点击这里邀请机器人进群](https://t.me/KaiCheDSbot?startgroup=start)", 
                "parse_mode" => "Markdown",
                "reply_markup" => json_encode($inlineKeyboardMarkup),
                'disable_web_page_preview' => true
            ];
            apiRequest('sendMessage', $payload);
        } else if (strpos($text, "/kc") === 0) {
            if($chat_id < 0 && strpos($chat_id, "-100") === 0){
                if(getChat($conn,$chat_id)){
                    $sql = "UPDATE `groupinfo` SET `status` = 1 WHERE id =" . $chat_id;
                    $row = $conn->query($sql);
                    $sql = "select * from videos order by rand() LIMIT 1";
                    $res = Db::name('videos')->query($sql)[0];
                    apiRequest("sendMessage", [
                        'chat_id' => $chat_id, 
                        'text' => "车辆已启动，每6分钟一班车\n注意：如果群禁止发视频，请把机器人设置为管理员", 
                        'reply_to_message_id' => $message['message_id'],
                        'disable_web_page_preview' => true
                    ]);
                    $payload = [
                        'chat_id' => $chat_id,
                        'video' => $res['file_id'],
                        'reply_markup' => json_encode(getInline($conn, $chat_id))
                    ];
                    apiRequest("sendVideo", $payload);
                }else{
                    apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => '请将本BOT添加管理员','disable_web_page_preview' => true]);
                }
            } else {
                apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => '暂不支持私信,频道,私群开车，只能在公开群组开车','disable_web_page_preview' => true]);
            }
        } else if (strpos($text, "/ad 8PmDSbLi") === 0) {
            if($chat_id > 0){
                $body = $message['text'];
                $arr = explode(',',explode(' ',$body)[2]);
                $sql = "select * from `advert` where `id` = '-1001000000001' and `ident` = $arr[0]";
                $res = $conn->query($sql)[0];
                $datetime = date("Y-m-d H:i:s");
                $text = base64_encode($arr[2]);
                $url = $arr[3];
                if($res){
                    $advert = json_decode($res['advert'], true);
                    $advert[$arr[1]] = ['text'=>$text,'url'=>$url];
                    $advert = json_encode($advert);
                    echo $sql = "UPDATE `advert` SET `advert` = '$advert', `datetime` = '$datetime' WHERE `id` ='-1001000000001' and `ident` = $arr[0]";
                }else{
                    $advert[$arr[1]] = ['text'=>$text,'url'=>$url];
                    $advert = json_encode($advert);
                    echo $sql = "INSERT INTO `advert`(`id`, `ident`, `advert`, `datetime`) VALUES ('-1001000000001',$arr[0],'$advert','$datetime')";
                }
                $row = $conn->query($sql);
                if($row){
                    apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message['message_id']]);
                    $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => 'No.' . $arr[0] . ': Success','disable_web_page_preview' => true]);
                }else{
                    $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => 'No.' . $arr[0] . ': Fail','disable_web_page_preview' => true]);
                }
                sleep(3);
                apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $result['result']['message_id']]);
            }
        } else if (strpos($text, "/ad") === 0){
            if($chat_id < 0){
                $body = $message['text'];
                // /ad 1,出粉¥全部活粉¥3毛1人👆,https://t.me/JiabaoAd
                $arr = explode(',',explode(' ',$body)[1]);
                //获取管理员列表
                $list = json_decode(file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/' . "getChatAdministrators?chat_id=" . $chat_id), true); 
                $administrators = false;
                $from_id = $message['from']['id'];
                foreach ($list['result'] as $val) {
                    if($from_id == $val['user']['id']){
                        $administrators = true;
                    }
                }
                if($administrators){
                    if($arr[0] == 1 || $arr[0] == 2 || $arr[0] == 3){
                        if($arr[1] == 1 || $arr[1] == 2 || $arr[1] == 3 || $arr[1] == 4 || $arr[1] == 5){
                            $sql = "select * from `advert` where `id` = '$chat_id' and `ident` = $arr[0]";
                            $res = $conn->query($sql)[0];
                            $datetime = date("Y-m-d H:i:s");
                            $text = base64_encode($arr[2]);
                            $url = $arr[3];
                            if($res){
                                $advert = json_decode($res['advert'], true);
                                if($arr[2] != "" && $arr[3] != ""){
                                    $advert[$arr[1]] = ['text'=>$text,'url'=>$url];
                                }else{
                                    unset($advert[$arr[1]]);
                                }
                                $advert = json_encode($advert);
                                $sql = "UPDATE `advert` SET `advert` = '$advert', `datetime` = '$datetime' WHERE `id` ='$chat_id' and `ident` = $arr[0]";
                                $row = $conn->query($sql);
                            }else{
                                if($arr[2] != "" && $arr[3] != ""){
                                    $advert[$arr[1]] = ['text'=>$text,'url'=>$url];
                                    $advert = json_encode($advert);
                                    $sql = "INSERT INTO `advert`(`id`, `ident`, `advert`, `datetime`) VALUES ('$chat_id',$arr[0],'$advert','$datetime')";
                                    $row = $conn->query($sql);
                                }else{
                                    $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => 'Fail（请填写内容）','disable_web_page_preview' => true]);
                                }
                            }
                            if($row){
                                apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message['message_id']]);
                                $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => 'No.' . $arr[0] . ': Success','disable_web_page_preview' => true]);
                            }else{
                                $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => 'No.' . $arr[0] . ': Fail','disable_web_page_preview' => true]);
                            }
                            sleep(3);
                            apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $result['result']['message_id']]);
                        }else{
                            apiRequest('sendMessage', ['chat_id' => $chat_id,'text' =>'Fail（只能轮播5组内容）','disable_web_page_preview' => true]);
                        }
                    }else{
                        apiRequest('sendMessage', ['chat_id' => $chat_id,'text' =>'Fail（只能操作1-3号内容）','disable_web_page_preview' => true]);
                    }
                }else{
                    apiRequest('sendMessage', ['chat_id' => $chat_id,'text' =>'Fail（需要管理员权限）','disable_web_page_preview' => true]);
                }
            }
        }else if(strpos($text, "/gg") === 0){
            $result = apiRequest('sendMessage', ['chat_id' => $chat_id,'text' => "·新增5个广告位，增加群主收益\n·群主或管理发送如下指令添加广告内容\n------------------\n发送：/ad 1,1,广告词,跳转链接\n发送：/ad 2,1,广告词,跳转链接\n发送：/ad 3,1,广告词,跳转链接\n发送：/ad 3,2,广告词,跳转链接\n注意：中间英文逗号( , )隔开\n例如：/ad 1,1,指定群拉人,https://t.me/JiabaoAD\n------------------\n说明：1-3号广告位群主可以自由设置，每个广告位可5组轮播\n------------------\n发送如下指令删除某位置广告\n发送：/ad 1,1,,\n发送：/ad 1,2,,\n发送：/ad 2,1,,\n以此类推....",'disable_web_page_preview' => true]);
        }else if(strpos($text, "/img") === 0){
            $payload = [
                'chat_id' => $chat_id,
                'photo' => 'https://www.google.com.hk/imgres?imgurl=https%3A%2F%2Fimg.iplaysoft.com%2Fwp-content%2Fuploads%2F2019%2Ffree-images%2Ffree_stock_photo.jpg&imgrefurl=https%3A%2F%2Fwww.iplaysoft.com%2Ffree-images.html&tbnid=vQjlM9KtkGsb_M&vet=12ahUKEwiCo_rpsO3vAhXOEIgKHTjfDqsQMygBegUIARCyAQ..i&docid=JeaDEV9l4RQZhM&w=680&h=453&q=%E5%9B%BE%E7%89%87&hl=zh-CN&safe=strict&ved=2ahUKEwiCo_rpsO3vAhXOEIgKHTjfDqsQMygBegUIARCyAQ',
                "parse_mode" => "HTML",
                'caption' => "<a href='https://www.baidu.com'>baidu</a>\n----------------\n<a href='https://www.google.com'>google</a>\n@AbramBeggs"
            ];
            $result = apiRequest('sendPhoto', $payload);
            sleep(5);
            $media = [
                'type' => 'photo',
                'media' => 'https://www.google.com.hk/imgres?imgurl=http%3A%2F%2Fstatic.runoob.com%2Fimages%2Fdemo%2Fdemo2.jpg&imgrefurl=https%3A%2F%2Fwww.runoob.com%2Fcss%2Fcss-image-gallery.html&tbnid=-jU8YIo2rZ_g_M&vet=12ahUKEwiCo_rpsO3vAhXOEIgKHTjfDqsQMygEegUIARC4AQ..i&docid=FjHfmgJfNrv8TM&w=1920&h=1080&q=%E5%9B%BE%E7%89%87&hl=zh-CN&safe=strict&ved=2ahUKEwiCo_rpsO3vAhXOEIgKHTjfDqsQMygEegUIARC4AQ',
                "parse_mode" => "HTML",
                'caption' => "<a href='https://www.baidu.com'>百度</a>\n----------------\n<a href='https://www.google.com'>谷歌</a>\n@AbramBeggs"
            ];
            $payload = [
                'chat_id' => $chat_id,
                'message_id' => $result['result']['message_id'],
                "media" => json_encode($media)
            ];
            apiRequest('editMessageMedia', $payload);
        }
    } else if(isset($message['new_chat_member'])){
        //$rand = mt_rand(1,10);
        //if($rand = 5){
            getChatNum($conn,$chat_id);
        //}
        apiRequest('deleteMessage', ['chat_id' => $chat_id,"message_id" => $message['message_id']]);
    } else if(isset($message['left_chat_member'])){
        //$rand = mt_rand(1,10);
        //if($rand = 5){
            //getChatNum($conn,$chat_id);
        //}
        apiRequest('deleteMessage', ['chat_id' => $chat_id,"message_id" => $message['message_id']]);
    } else if (isset($message['pinned_message'])) {
        apiRequest('deleteMessage', ['chat_id' => $chat_id,"message_id" => $message['message_id']]);
    }/* else if (isset($message['video'])) {
        $video = $message['video'];
        $sql = "select * from videos where file_unique_id = '".$video['file_unique_id']."'";
        $res = $conn->query($sql)[0];
        if(!$res){
            $sqlkey = "";
            $sqlval = "";
            foreach ($video as $key => $val) {
                $sqlkey .= $key.",";
                $val = is_array($val) ? json_encode($val) : $val;
                $sqlval .= "'".$val."',";
            }
            $sql = "INSERT INTO videos ( ".rtrim($sqlkey, ",")." ) VALUES ( ".rtrim($sqlval, ",")." )";
            $row = $conn->query($sql);
            if($row){
                $payload = [
                    'chat_id' => $chat_id,
                    "parse_mode" => "Markdown",
                    'text' => $conn->lastInsertId()
                ];
                apiRequest("sendMessage", $payload);
            }
        }
    }*/
}

function sendVideo($conn,$update){
    if($update['callback_query']['data'] === "see"){ 
        $sql = "select * from videos order by rand() LIMIT 1";
        $result = Db::name('videos')->query($sql)[0];
        $payload = [
            'chat_id' => $update['callback_query']['message']['chat']['id'],
            'video' => $result['file_id'],
            'reply_markup' => json_encode(getInline($conn, $para['callback_query']['message']['chat']['id']))
        ];
        apiRequest("sendVideo", $payload);
    }
    if($update['callback_query']['data'] === "long"){ 
        $sql = "select * from videos where width >= 720 order by rand() LIMIT 1";
        $result = Db::name('videos')->query($sql)[0];
        $payload = [
            'chat_id' => $update['callback_query']['message']['chat']['id'],
            'video' => $result['file_id'],
            'reply_markup' => json_encode(getInline($conn, $para['callback_query']['message']['chat']['id']))
        ];
        apiRequest("sendVideo", $payload);
    }
}

function InsertChannel($conn,$update){
    $from_chat_id = $update['channel_post']['chat']['id'];
    $message_id = $update['channel_post']['message_id'];
    $date = $update['channel_post']['date'];
    $username = $update['channel_post']['sender_chat']['username'];
    if($from_chat_id == '-1001438104305'){
        $sql = "SELECT * FROM `pinmessage` WHERE `from_chat_id` = '$from_chat_id' AND `message_id` = '$message_id'";
        $result = $conn->query($sql)[0];
        if($result){
            $sql = "UPDATE `pinmessage` SET `from_chat_id` = '$from_chat_id', `message_id` = '$message_id', `username` = '$username' WHERE id = " . $result['id'];
        }else{
            $sql = "INSERT INTO `pinmessage` (`from_chat_id`, `date`, `message_id`, `username`) VALUES ('$from_chat_id','$date','$message_id','$username')";
        }
        $row = $conn->query($sql);
    }
}

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        if (is_bool($domain) || stripos($cdnurl, '/') === 0) {
            $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        }
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = $v['field'] ?? $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = $v['display'] ?? str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = $v['primary'] ?? '';
            $v['column'] = $v['column'] ?? 'name';
            $v['model'] = $v['model'] ?? '';
            $v['table'] = $v['table'] ?? '';
            $v['name'] = $v['name'] ?? str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column($v['column'], $primary) : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $linedata = array_intersect_key($result[$n], $curr);
                    $v[$fieldsArr[$n]['display']] = $fieldsArr[$n]['column'] == '*' ? $linedata : implode(',', $linedata);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
        $replaced = [];
        $count = 0;

        //判断是否是对象
        if (is_resource($data) || is_object($data)) {
            return var_export($data, $return);
        }

        //判断是否有特殊的键名
        $specialKey = false;
        array_walk_recursive($data, function (&$value, &$key) use (&$specialKey) {
            if (is_string($key) && (stripos($key, "\n") !== false || stripos($key, "array (") !== false)) {
                $specialKey = true;
            }
        });
        if ($specialKey) {
            return var_export($data, $return);
        }
        array_walk_recursive($data, function (&$value, &$key) use (&$replaced, &$count, &$stringcheck) {
            if (is_object($value) || is_resource($value)) {
                $replaced[$count] = var_export($value, true);
                $value = "##<{$count}>##";
            } else {
                if (is_string($value) && (stripos($value, "\n") !== false || stripos($value, "array (") !== false)) {
                    $index = array_search($value, $replaced);
                    if ($index === false) {
                        $replaced[$count] = var_export($value, true);
                        $value = "##<{$count}>##";
                    } else {
                        $value = "##<{$index}>##";
                    }
                }
            }
            $count++;
        });

        $dump = var_export($data, true);

        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump); // Starts
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump); // Ends
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump); // Empties
        $dump = preg_replace('#\)$#', "]", $dump); //End

        if ($replaced) {
            $dump = preg_replace_callback("/'##<(\d+)>##'/", function ($matches) use ($replaced) {
                return $replaced[$matches[1]] ?? "''";
            }, $dump);
        }

        if ($return === true) {
            return $dump;
        } else {
            echo $dump;
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] && config('fastadmin.cors_request_domain')) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('url_clean')) {
    /**
     * 清理URL
     */
    function url_clean($url)
    {
        if (!check_url_allowed($url)) {
            return '';
        }
        return xss_clean($url);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}

if (!function_exists('check_url_allowed')) {
    /**
     * 检测URL是否允许
     * @param string $url URL
     * @return bool
     */
    function check_url_allowed($url = '')
    {
        //允许的主机列表
        $allowedHostArr = [
            strtolower(request()->host())
        ];

        if (empty($url)) {
            return true;
        }

        //如果是站内相对链接则允许
        if (preg_match("/^[\/a-z][a-z0-9][a-z0-9\.\/]+((\?|#).*)?\$/i", $url) && substr($url, 0, 2) !== '//') {
            return true;
        }

        //如果是站外链接则需要判断HOST是否允许
        if (preg_match("/((http[s]?:\/\/)+(?>[a-z\-0-9]{2,}\.){1,}[a-z]{2,8})(?:\s|\/)/i", $url)) {
            $chkHost = parse_url(strtolower($url), PHP_URL_HOST);
            if ($chkHost && in_array($chkHost, $allowedHostArr)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('build_suffix_image')) {
    /**
     * 生成文件后缀图片
     * @param string $suffix 后缀
     * @param null   $background
     * @return string
     */
    function build_suffix_image($suffix, $background = null)
    {
        $suffix = mb_substr(strtoupper($suffix), 0, 4);
        $total = unpack('L', hash('adler32', $suffix, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $background = $background ? $background : "rgb({$r},{$g},{$b})";

        $icon = <<<EOT
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
            <path style="fill:#E2E5E7;" d="M128,0c-17.6,0-32,14.4-32,32v448c0,17.6,14.4,32,32,32h320c17.6,0,32-14.4,32-32V128L352,0H128z"/>
            <path style="fill:#B0B7BD;" d="M384,128h96L352,0v96C352,113.6,366.4,128,384,128z"/>
            <polygon style="fill:#CAD1D8;" points="480,224 384,128 480,128 "/>
            <path style="fill:{$background};" d="M416,416c0,8.8-7.2,16-16,16H48c-8.8,0-16-7.2-16-16V256c0-8.8,7.2-16,16-16h352c8.8,0,16,7.2,16,16 V416z"/>
            <path style="fill:#CAD1D8;" d="M400,432H96v16h304c8.8,0,16-7.2,16-16v-16C416,424.8,408.8,432,400,432z"/>
            <g><text><tspan x="220" y="380" font-size="124" font-family="Verdana, Helvetica, Arial, sans-serif" fill="white" text-anchor="middle">{$suffix}</tspan></text></g>
        </svg>
EOT;
        return $icon;
    }
}
