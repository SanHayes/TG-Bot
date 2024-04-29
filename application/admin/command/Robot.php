<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;

class Robot extends Command
{
    protected $ApiUrl = 'https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/';

    protected function configure()
    {
        $this->setName('robot')
            ->setDescription('the robot command');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('ROBOT START');
        while (true) {
            $updates = json_decode(file_get_contents($this->ApiUrl . 'getUpdates'), true);
            if($updates['ok'] && !empty($updates['result'])){
                foreach ($updates['result'] as $update) {
                    $output->writeln("接收消息：" . date("Y-m-d H:i:s") . "\t " . json_encode($update['update_id']));
                    if (isset($update["message"])) {
                        $message = $update['message'];
                        $chat_id = $message['chat']['id'];
                        if(isset($message['text'])){
                            $text = $message['text'];
                            if(strpos($text, "/start") === 0){
                                $inlineKeyboardMarkup['inline_keyboard'] = $keyboardRow1 = $keyboardRow2 = $keyboardRow3 = $keyboardRow4 = [];
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
                                file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/sendMessage?'.http_build_query($payload), false, stream_context_create(array(
                                    "ssl"=>array(
                                        "verify_peer"=>false,
                                        "verify_peer_name"=>false,
                                        "allow_self_signed"=>true,
                                    ),
                                )));
                            }
                        }
                    }
                    if (isset($update['callback_query'])) {
                        if($update['callback_query']['data'] === "see"){ 
                            $sql = "select * from fa_videos order by rand() LIMIT 1";
                            $result = Db::name('videos')->query($sql)[0];
                            $payload = [
                                'chat_id' => $update['callback_query']['message']['chat']['id'],
                                'video' => $result['file_id'],
                                'reply_markup' => json_encode($this->getInline($update['callback_query']['message']['chat']['id']))
                            ];
                            file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/sendVideo?'.http_build_query($payload), false, stream_context_create(array(
                                "ssl"=>array(
                                    "verify_peer"=>false,
                                    "verify_peer_name"=>false,
                                    "allow_self_signed"=>true,
                                ),
                            )));
                        }
                        if($update['callback_query']['data'] === "long"){ 
                            $sql = "select * from fa_videos where width >= 720 order by rand() LIMIT 1";
                            $result = Db::name('videos')->query($sql)[0];
                            $payload = [
                                'chat_id' => $update['callback_query']['message']['chat']['id'],
                                'video' => $result['file_id'],
                                'reply_markup' => json_encode($this->getInline($update['callback_query']['message']['chat']['id']))
                            ];
                            file_get_contents('https://api.telegram.org/bot1658524088:AAFuOKWtOVF19F56-1B4iENG2dBqqnLAW7c/sendVideo?'.http_build_query($payload), false, stream_context_create(array(
                                "ssl"=>array(
                                    "verify_peer"=>false,
                                    "verify_peer_name"=>false,
                                    "allow_self_signed"=>true,
                                ),
                            )));
                        }
                    }
                    // if (isset($update["channel_post"])) {
                    //     InsertChannel($update);
                    // }
                }
                file_get_contents($this->ApiUrl . 'getUpdates?offset=' . (intval(++end($updates['result'])['update_id'])));
            }
        }
        $output->writeln('ROBOT END');
    }

    protected function getItem($chat_id = '-1001000000001'){
        $sql = "select * from `fa_advert` where `id` = '$chat_id'";
        return Db::name('advert')->query($sql);
    }

    protected function getInline($chat_id){
        if($chat_id < 0){
            $me_inline = $this->getItem();
            $it_inline = $this->getItem($chat_id);
            $me_advert = [];
            $step = 0;
            foreach ($me_inline as $key => $val) {
                $sm = json_decode($val['advert'], true);
                if(!empty($sm)){
                    $keys = array_keys($sm);
                    foreach ($keys as $k => $v) {
                        if($v == $val['step']){
                            if(isset($keys[$k + 1])){
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[$k + 1] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                            }else{
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                            }
                            $row = Db::name('videos')->query($sql);
                            break;
                        }else{
                            if($v = end($keys)){
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                                $row = Db::name('videos')->query($sql);
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
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[$k + 1] . " WHERE `id` = '" . $val['id'] . "' and `ident` = " . $val['ident'];
                            }else{
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[0] . " WHERE `id` = '" . $val['id'] . "' and `ident` = " . $val['ident'];
                            }
                            $row = Db::name('videos')->query($sql);
                            break;
                        }else{
                            if($v = end($keys)){
                                $sql = "UPDATE fa_advert SET `step` = " . $keys[0] . " WHERE `id` = '-1001000000001' and `ident` = " . $val['ident'];
                                $row = Db::name('videos')->query($sql);
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
        $me_inline = $this->getItem();
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
        $sql = "UPDATE fa_advert SET `step` = $step WHERE id = '-1001000000001'";
        $row = Db::name('videos')->query($sql);
        $inlineKeyboardMarkup['inline_keyboard'] = [];
        foreach ($me_advert as $key => $val) {
            $inlineKeyboardMarkup['inline_keyboard'][][] = ['text' => base64_decode($val['text']),'url' => $val['url']];
        }
        $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "还想看",'callback_data' => "see"],['text' => "长视频",'callback_data' => "long"]];
        return $inlineKeyboardMarkup;
    }
}