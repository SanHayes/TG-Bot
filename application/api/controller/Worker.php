<?php
namespace app\api\controller; 
use think\worker\Server; 
use Workerman\Lib\Timer;
use think\Console;
use think\Db;
 
class Worker extends Server{    
    protected $socket = 'http://0.0.0.0:2000'; //linux服务器端口
    protected $processes = 1;

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker){
    //查看是否有新的充值或提现订单，有就推送给所有用户
        Timer::add(1, function()use($worker){
             Console::call('timing');
        });
    }
}