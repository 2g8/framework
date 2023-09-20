<?php

class verify
{
	private $add_rules = null;
	private $verifier = null;
	private $messages = null;
	private $checkvalues = null;
	
    public function __input(& $obj, $args){
		$this->verifier = (null != $obj->verifier) ? $obj->verifier : array();
		if(isset($args[1]) && is_array($args[1])){
			$this->verifier["rules"] = $this->verifier["rules"] + $args[1]["rules"];
			$this->verifier["messages"] = isset($args[1]["messages"]) ? ( $this->verifier["messages"] + $args[1]["messages"] ) : $this->verifier["messages"];
		}
		if(is_array($obj->addrules) && !empty($obj->addrules) ){foreach($obj->addrules as $addrule => $addveri)$this->addrules($addrule, $addveri);}
		if(empty($this->verifier["rules"]))	debug::error('Verify Error','rules are empty.');
		return is_array($args[0]) ? $this->checkrules($args[0]) : true;
	}
	
	public function addrules($rule_name, $checker){
		$this->add_rules[$rule_name] = $checker;
	}
	
	private function checkrules($values){ 
		$this->checkvalues = $values;
		foreach( $this->verifier["rules"] as $rkey => $rval ){
			$inputval = isset($values[$rkey]) ? $values[$rkey] : '';
			foreach( $rval as $rule => $rightval ){
				if(method_exists($this, $rule)){
					if(true == $this->$rule($inputval, $rightval)) continue;
				}else{
					debug::error('Verify Error',"Unknown rules {$rule}.");
				}
				$this->messages[$rkey][] = (isset($this->verifier["messages"][$rkey][$rule])) ? $this->verifier["messages"][$rkey][$rule] : "{$rule}";
			}
		}
		return (null == $this->messages) ? false : $this->messages; 
	}
	
	private function notnull($val, $right){return $right === ( isset($val) && !empty($val) && "" != $val );}
	
	private function minlength($val, $right){return $this->cn_strlen($val) >= $right;}
	
	private function maxlength($val, $right){return $this->cn_strlen($val) <= $right;}
	
	private function equalto($val, $right){return $val == $this->checkvalues[$right];}
	
	private function istime($val, $right){$test = @strtotime($val);return $right == ( $test !== -1 && $test !== false );}

	private function email($val, $right){
		return $right == ( preg_match('/^[A-Za-z0-9]+([._\-\+]*[A-Za-z0-9]+)*@([A-Za-z0-9-]+\.)+[A-Za-z0-9]+$/', $val) != 0 );
	}

	private function url($val, $right){
		return $right == ( preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $val) != 0 );
	}
	
	public function cn_strlen($val){$i=0;$n=0;
		while($i<strlen($val)){$clen = ( strlen("茶框架") == 6 ) ? 2 : 3;
			if(preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/",$val[$i])){$i+=$clen;}else{$i+=1;}$n+=1;}
		return $n;
	}
}