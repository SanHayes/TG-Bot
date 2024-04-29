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

class Timing extends Command
{
    protected function configure()
    {
        $this->setName('timing')
            ->setDescription('the robot command');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $output->writeln('Timing Start!!!');
            $Bot_Api_Key = Env::get('bot.apikey');
            $Bot_UserName = Env::get('bot.name');
            $chat_id = Env::get('bot.chat_id');
            $Telegram = new Telegram($Bot_Api_Key, $Bot_UserName); //实例化Telegram
            $Telegram->useGetUpdatesWithoutDatabase(); //不实用数据库
            $data = $Telegram->handleGetUpdates(); //获取Bot消息
            $num = $data->raw_data['ok'];
            $result = $data->raw_data['result'];
            if($num > 0){
                foreach ($result as $item) {
                    if (isset($item->raw_data['callback_query']['data'])) {

                        $callback = $item->raw_data['callback_query']['data'];
                        // $chat_id = $item->raw_data['callback_query']['message']['chat']['id'];

                        switch ($callback) {
                            case 'analysis':
                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text'    => '🔍账户分析'
                                ]);
                                $output->info('');
                                break;

                            case 'switch':
                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text'    => '👁监控开关'
                                ]);
                                break;

                            case 'harvest':
                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text'    => '🔪一键收割'
                                ]);
                                break;

                            case 'auto':
                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text'    => '⏰自动收割'
                                ]);
                                break;

                            default:
                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text'    => '⏰自动收割'
                                ]);
                                break;
                        }
                    }
                }
            }
        } catch (TelegramException $e) {
            $output->writeln($e->getMessage());
        }
    }
}