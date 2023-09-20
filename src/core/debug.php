<?php

class debug
{
	public static function error($msg,$content){
		//关闭debug
		if(DEBUG=='off') exit;
		//转义代码防止出现xss
		$msg = htmlspecialchars_deep($msg);
		$content = htmlspecialchars_deep($content);
		$info = <<<HTMLS
		<div id="tea_debug_error">
		<h1>$msg</h1>
		<p>$content</p><pre>
HTMLS;
        $trace = debug_backtrace();
        unset($trace[0]);
        foreach($trace as $t)
        {
        	$t['class'] = isset($t['class']) ? $t['class'] : '';
        	$t['type'] = isset($t['type']) ? $t['type'] : '';
        	$t['function'] = isset($t['function']) ? $t['function'] : '';
            $info .= "line : {$t['line']},	function : {$t['class']}{$t['type']}{$t['function']},	file : {$t['file']}\n";
        }
		$info .= '</pre></div>';
		echo $info;
		exit;
	}
}