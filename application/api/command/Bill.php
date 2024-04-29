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

class Bill extends Command
{
    protected function configure()
    {
        $this->setName('bill')
            ->setDescription('the bill command');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('Bill Start!!!');
        $Bot_Api_Key = Env::get('bot.apikey');
        $Bot_UserName = Env::get('bot.name');
        $chat_id = Env::get('bot.chat_id');
        $Telegram = new Telegram($Bot_Api_Key, $Bot_UserName); //实例化Telegram
        try {
            $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "🔖交易详情", 'url' => "https://www.baidu.com/details"], ['text' => "🏠账户查询", 'url' => "https://www.baidu.com/query"]];
            $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "🔍账户分析", 'callback_data' => "analysis"], ['text' => "👁监控开关", 'callback_data' => "switch"]];
            $inlineKeyboardMarkup['inline_keyboard'][] = [['text' => "🔪一键收割", 'callback_data' => "harvest"], ['text' => "⏰自动收割", 'callback_data' => "auto"]];
            Request::sendMessage([
                'chat_id'      => $chat_id,
                'text'         => "【🔔ETH链动账信息】\n【地址】`erXwufsXjsTpaFSDiqZBRgBbaLRdtUeM`\n【转入/转出】1290.21 USDT\n【账户余额】1591.95 USDT",
                'reply_markup' => json_encode($inlineKeyboardMarkup),
                'parse_mode' => "Markdown"
            ]);
        } catch (TelegramException $e) {
            $output->writeln($e->getMessage());
        }
    }
}