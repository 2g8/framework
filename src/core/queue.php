<?php

interface QueueInterface {
    const PRIORITY_HIGH = 0;
    const PRIORITY_NORM = 1;
    const PRIORITY_LOW = 2;

    const PRIORITY_LOW_SUFFIX = '_low';
    const PRIORITY_HIGH_SUFFIX = '_high';

    public function push($data, $priority = self::PRIORITY_NORM): bool;
    public function pop($priority = null, $block = false);
    public function count($priority = self::PRIORITY_NORM);
    public function getId();
    public function getChannels();
    public function getIntChannels();
    public function getDriver(): string;

    public function getErrCode(): int;
    public function getErrMsg(): string;
}

final class queue
{
	protected static $_queue_driver = ['tea_db','tea_spl','tea_redis','tea_shm','tea_sysvmsg'];

	public static function make($queueconfig = []){
        $driver = $queueconfig['driver'] ? $queueconfig['driver'] : 'tea_spl'; //默认用 SplQueue 数组队列
        if(!in_array($driver,self::$_queue_driver))
        {
            debug::error('Queue Driver Error',"Queue Driver <b>$driver</b> not no support.");
        }
        load::file('lib.queue.BaseQueue',TEA_PATH);
        load::file('lib.queue.'.$driver,TEA_PATH);
        return new $driver($queueconfig);
    }
}