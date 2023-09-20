<?php

class filter
{
	private static $_allowtags 			= 'p|br|b|strong|hr|a|img|object|param|form|input|label|dl|dt|dd|div|font',
	               $_allowattrs 		= 'id|class|align|valign|src|border|href|target|width|height|title|alt|name|action|method|value|type',
	               $_disallowattrvals 	= 'expression|javascript:|behaviour:|vbscript:|mocha:|livescript:';
	
	function __construct($allowtags = null, $allowattrs = null, $disallowattrvals = null)
	{
		if ($allowtags) self::$_allowtags = $allowtags;
		if ($allowattrs) self::$_allowattrs = $allowattrs;
		if ($disallowattrvals) self::$_disallowattrvals = $disallowattrvals;
	}
	
	static function input($cleanxss = 1)
	{
        if (get_magic_quotes_gpc())
        {
           $_POST = stripslashes_deep($_POST);
           $_GET = stripslashes_deep($_GET);
           $_COOKIE = stripslashes_deep($_COOKIE);
           $_REQUEST = stripslashes_deep($_REQUEST);
           $_SERVER = stripslashes_deep($_SERVER);
        }
        
        if ($cleanxss)
        {
        	$_POST = self::xss($_POST);
        	$_GET = self::xss($_GET);
        	$_COOKIE = self::xss($_COOKIE);
        	$_REQUEST = self::xss($_REQUEST);
        }
	}
	
	static function xss($string)
	{
		if (is_array($string))
		{
			$string = array_map(array('self', 'xss'), $string);
		}
		else 
		{
			if (strlen($string) > 10)
			{
				$string = self::_strip_tags($string);
			}
			//下面会替换POST里面JSON里面的引号,导致服务端decode失效,暂时注释掉 TODO:寻找替换方案
			//$string = self::filterWords($string);
		}
		return $string;
	}
	
	static function _strip_tags($string)
	{
		return preg_replace_callback("|(<)(/?)(\w+)([^>]*)(>)|", array('self', '_strip_attrs'), $string);
	}
	
	static function _strip_attrs($matches)
	{
		if (preg_match("/^(".self::$_allowtags.")$/", $matches[3]))
		{
			if ($matches[4])
			{
				preg_match_all("/\s(".self::$_allowattrs.")\s*=\s*(['\"]?)(.*?)\\2/i", $matches[4], $m, PREG_SET_ORDER);
				$matches[4] = '';
				foreach ($m as $k=>$v)
				{
					if (!preg_match("/(".self::$_disallowattrvals.")/", $v[3]))
					{
						$matches[4] .= $v[0];
					}
				}
			}
		}
		else 
		{
			$matches[1] = '&lt;';
			$matches[5] = '&gt;';
		}
		unset($matches[0]);
		return implode('', $matches);
	}
	
	static public function filterWords($str)
    {
        $farr = array(
                "/<|>|\"|\'|\/\*|\*|\.\.\/|\.\//is"
        		//上面白名单处理过，就不用替换这么多了。
                //"/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
                //"/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
                //"/<|>|\"|select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
        );
        $str = preg_replace($farr,'',$str);
        return $str;
    }
	
}