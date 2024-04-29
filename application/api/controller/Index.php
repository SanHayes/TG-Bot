<?php

namespace app\api\controller;

use think\Env;
use think\Console;
use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot\Request;
use \Longman\TelegramBot\Exception\TelegramException;
use app\common\controller\Api;
// use app\common\model\User;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $Bot_Api_Key;
    protected $Bot_UserName;
    protected $Chat_ID;
    protected $Telegram;

    public function _initialize()
    {
        $this->Bot_Api_Key = Env::get('bot.apikey');
        $this->Bot_UserName = Env::get('bot.name');
        $this->Chat_ID = Env::get('bot.chat_id');
        $this->Telegram = new Telegram($this->Bot_Api_Key, $this->Bot_UserName);
        $this->Telegram->useGetUpdatesWithoutDatabase();
    }

    public function index()
    {   
    	try {
            $data = $this->Telegram->handleGetUpdates();
	        $num = $data->raw_data['ok'];
	        $result = $data->raw_data['result'];
            // echo '<pre>';
            // var_dump($result);
	        if($num > 0){
	            foreach ($result as $item) {
	                if (isset($item->raw_data['callback_query']['data'])) {
	                    $callback = $item->raw_data['callback_query']['data'];
	                    switch ($callback) {
	                        case 'analysis':
	                            Request::sendMessage([
	                                'chat_id'      => $this->Chat_ID,
	                                'text'        => '🔍账户分析'
	                            ]);
	                            break;

	                        case 'switch':
	                            Request::sendMessage([
	                                'chat_id'      => $this->Chat_ID,
	                                'text'        => '👁监控开关'
	                            ]);
	                            break;

	                        case 'harvest':
	                            Request::sendMessage([
	                                'chat_id'      => $this->Chat_ID,
	                                'text'        => '🔪一键收割'
	                            ]);
	                            break;

	                        case 'auto':
	                            Request::sendMessage([
	                                'chat_id'      => $this->Chat_ID,
	                                'text'        => '⏰自动收割'
	                            ]);
	                            break;

	                        default:
	                            break;
	                    }
	                }
	            }
	        }
        } catch (TelegramException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 会员注册信息
     */
    public function register()
    {
        Console::call('register');
    }

    /**
     * 会员授权信息
     */
    public function authorization()
    {
        Console::call('authorization');
    }

    /**
     * 会员动账信息
     */
    public function bill()
    {
        Console::call('bill');
    }
}
