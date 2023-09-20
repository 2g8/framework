<?php

class acl
{
    public $tea,$rules,$role;
    public $defaultFailedRoute;
    
    function __construct($tea)
    {
        $this->tea = $tea;
        if($tea->session === NULL){
        	$tea->debug->error("Tea Framework Error","the ACL's autoload needs SESSION autoload first.");
        }
        $this->role = !empty($_SESSION['role']) ? $_SESSION['role'] : 'guest';
        $this->defaultFailedRoute = $this->tea->conf->uri['default_controller'];
		$this->rules = $tea->conf->acl;
        $this->check();
    }
    
    public function isAllowed($role, $c, $a='') {
		if (!$this->hasDenied($role, $c, $a)) {
			if ($this->hasAllowed($role, $c, $a)) {
				return true;
			}
		}
		return false;
	}
	
	protected function hasAllowed($role, $c, $a='') {
		if ($a=='') {
			return isset($this->rules[$role]['allow'][$c]);
		} else {
			if(isset($this->rules[$role]['allow'][$c])) {
				$actionlist = $this->rules[$role]['allow'][$c];
				if ($actionlist==='*')
					return true;
				else
					return in_array($a, $actionlist);
			} else {
				if( isset($this->rules[$role]['allow']) && is_array($this->rules[$role]['allow']) && isset($this->rules[$role]['allow'][0]) ){
					return ($this->rules[$role]['allow'][0] == '*');
				}
				return false;
			}
		}
	}
	
	public function isDenied($role, $c, $a='') {
		if ($this->hasDenied($role, $c, $a)) {
			return true;
		}
		return false;
	}
	
	protected function hasDenied($role, $c, $a='') {
		if ($a=='') {
			return isset($this->rules[$role]['deny'][$c]);
		} else {
			if( isset($this->rules[$role]['deny']) && $this->rules[$role]['deny']=='*'){
				$this->rules[$role]['deny'] = array('*');
			}
			if (isset($this->rules[$role]['deny'][$c])) {
				$actionlist = $this->rules[$role]['deny'][$c];
				if($actionlist==='*')
					return true;
				else
					return in_array($a, $actionlist);
			} else {
				return false;
			}
		}
	}
	
	public function check() {
		$role = $this->role;
		$cap = $this->tea->uri->cap;
		$c = $cap['controller'];
		$a = $cap['action'];
		if ($this->isDenied($role, $c, $a) ) {
			if (isset($this->rules[$role]['fail'])) {
				$route = $this->rules[$role]['fail'];
				if (is_string($route)) {
					$this->tea->uri->redirect($route);	//如果是字符串的url，直接跳转
				}else{
					$failroute = isset($route[$c]) ? $route[$c] : (isset($route[$c.'/'.$a]) ? $route[$c.'/'.$a] : $route['default']);
					if(is_string($failroute)){
						$this->tea->uri->redirect(makeurl($failroute));
					}else{
						$this->tea->uri->redirect(makeurl(key($failroute),current($failroute)));
					}
				}
			}
			$this->tea->uri->redirect(makeurl($this->defaultFailedRoute));
		} else if($this->isAllowed($role, $c, $a)==false) {
			if (isset($this->rules[$role]['fail'])) {
				$route = $this->rules[$role]['fail'];
				if (is_string($route)) {
					$this->tea->uri->redirect($route);	//如果是字符串的url，直接跳转
				}else{
					$failroute = isset($route[$c]) ? $route[$c] : (isset($route[$c.'/'.$a]) ? $route[$c.'/'.$a] : $route['default']);
					if(is_string($failroute)){
						$this->tea->uri->redirect(makeurl($failroute));
					}else{
						$this->tea->uri->redirect(makeurl(key($failroute),current($failroute)));
					}
				}
			}
			$this->tea->uri->redirect(makeurl($this->defaultFailedRoute));
		}
	}
	
}
    