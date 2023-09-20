<?php
//////////////////////////////////////////////////////////////////////
// 分页类，可配置分页模版
//////////////////////////////////////////////////////////////////////
class pager
{
	//初始化变量及配置
    public $page_var 		= "page";		//分页用的page变量，用来控制url生成，如xxx.php?a=1&teapage=2中的teapage
    //产生连接的字符串
    public $str_firstpage 	= '首页';		//首页
    public $str_prevpage 	= '上一页';		//上一页
    public $str_nextpage 	= '下一页';		//下一页
    public $str_lastpage 	= '尾页';		//尾页
    //样式设置字符串
    public $css_disable		= 'disabled';	//样式表 失效状态
    public $css_active		= 'active';		//样式表 当前选择
    public $css_normal		= '';			//样式表 正常状态
    //HTML模板
    public $html_tag		= 'li';    		//HTML标签选择，请配合模板使用
    public $html_tpl 		= '<ul class="pagination">[firstpage][prevpage][pagebar][nextpage][lastpage]</ul>'; //模板
    //数量初始化
    public $pagebar_num 	= 10;			//控制 数字页面条 显示的个数 [1][2][3][4][5][6][7][8][9][10]
    public $pagesize 		= 10;			//每页记录数
    public $total 			= 0;			//总记录数
    public $pagenow			= 1;			//当前页
    public $offset			= 0;			//记录偏移
    
    //构造函数
    function __construct($arr)
    {
        if(is_array($arr)){
            if(!array_key_exists('total',$arr))	debug::error('Pager Error',__FUNCTION__.' need a param of total');
            $this->total = intval($arr['total']);
            $this->pagesize = (array_key_exists('pagesize',$arr)) ? intval($arr['pagesize']) : $this->pagesize;
            $this->pagenow = (array_key_exists('pagenow',$arr)) ? intval($arr['pagenow']) : $this->pagenow;
            $url = (array_key_exists('url',$arr)) ? $arr['url'] : '';
        }else{
            $this->total = intval($arr);
            $nowindex = '';
            $url = '';
        }
        if((!is_int($this->total)) || ($this->total < 0)){
        	debug::error('Pager Error',__FUNCTION__.' [total] is not a positive integer!');
        }
        if((!is_int($this->pagesize)) || ($this->pagesize <= 0)){
        	debug::error('Pager Error',__FUNCTION__.' [pagesize] is not a positive integer!');
        }
        
        if(!empty($arr['page_var'])){
	        $this->page_var	=	$arr['page_var'];	//设置页面变量
        }
        if(function_exists('id62')){
        	$this->pagenow = isset($_GET[$this->page_var]) ? intval(id62($_GET[$this->page_var],1)) : $this->pagenow;
        }else{
        	$this->pagenow = isset($_GET[$this->page_var]) ? intval($_GET[$this->page_var]) : $this->pagenow;
        }   
        $this->totalpages = ceil($this->total/$this->pagesize);
        $this->offset = ($this->pagenow-1) * $this->pagesize;

        //根据浏览器语言,自动设置翻页文字
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
        if (preg_match("/zh-c/i", $lang)){
            $this->str_firstpage = '首页';$this->str_prevpage = '上一页';$this->str_nextpage = '下一页';$this->str_lastpage = '尾页';
        }else if (preg_match("/zh/i", $lang)){
            $this->str_firstpage = '首頁';$this->str_prevpage = '上一頁';$this->str_nextpage = '下一頁';$this->str_lastpage = '尾頁';
        }else if (preg_match("/en/i", $lang)){
            $this->str_firstpage = 'First';$this->str_prevpage = 'Previous ';$this->str_nextpage = 'Next';$this->str_lastpage = 'Last';
        }else if (preg_match("/fr/i", $lang)){
            $this->str_firstpage = 'Premier';$this->str_prevpage = 'Précédent';$this->str_nextpage = 'Suivant';$this->str_lastpage = 'Dernier';
        }else if (preg_match("/de/i", $lang)){
            $this->str_firstpage = 'Erste';$this->str_prevpage = 'Vorherige';$this->str_nextpage = 'Nächste';$this->str_lastpage = 'Letzte';
        }else if (preg_match("/ja/i", $lang)){
            $this->str_firstpage = '最初';$this->str_prevpage = '前';$this->str_nextpage = '次';$this->str_lastpage = '最後';
        //}else if (preg_match("/ko/i", $lang)){
            //$this->str_firstpage = '';$this->str_prevpage = '';$this->str_nextpage = '';$this->str_lastpage = '';
        //}else if (preg_match("/th/i", $lang)){ //泰语
            //$this->str_firstpage = '';$this->str_prevpage = '';$this->str_nextpage = '';$this->str_lastpage = '';
        //}else if (preg_match("/ar/i", $lang)){ //阿拉伯语
            //$this->str_firstpage = '';$this->str_prevpage = '';$this->str_nextpage = '';$this->str_lastpage = '';
        }else if (preg_match("/ru/i", $lang)){
            $this->str_firstpage = 'Первый';$this->str_prevpage = 'Предыдущий';$this->str_nextpage = 'Следующий';$this->str_lastpage = 'Последний';
        }else if (preg_match("/es/i", $lang)){
            $this->str_firstpage = 'Primero';$this->str_prevpage = 'Anterior';$this->str_nextpage = 'Siguiente';$this->str_lastpage = 'Último';
        }else{
            $this->str_firstpage = 'First';$this->str_prevpage = 'Previous ';$this->str_nextpage = 'Next';$this->str_lastpage = 'Last';
        }
    }
    
    //渲染输出函数
    function render($config = null)
    {
    	if(!empty($config) && is_array($config)){
    		foreach ($config as $key=>$val){
    			$this->{$key} = $val;
    		}
    	}
    	$pager['total'] = $this->total;
    	$pager['pagesize'] = $this->pagesize;
    	$pager['totalpages'] = $this->totalpages;
    	$pager['pagenow'] = $this->pagenow;
    	$pager['firstpage'] = $this->getfirstpage();
    	$pager['prevpage'] = $this->getprevpage();
    	$pager['nextpage'] = $this->getnextpage();
    	$pager['lastpage'] = $this->getlastpage();
    	$pager['pagebar'] = $this->getpagebar();
    	$html = $this->html_tpl;
    	foreach ($pager as $key=>$val){
    		$html = str_replace('['.$key.']',$val,$html);
    	}
    	return $html;
    }
    
    //首页
    function getfirstpage()
    {
        if($this->pagenow == 1){
        	return $this->_get_link($this->_get_url('#', true),$this->str_firstpage,$this->css_disable);
        }
        return $this->_get_link($this->_get_url(),$this->str_firstpage,$this->css_normal,'id="page_first"');
    }
    
    //上一页
    function getprevpage()
    {
        if($this->pagenow > 1){
            return $this->_get_link($this->_get_url($this->pagenow - 1),$this->str_prevpage,$this->css_normal,'id="page_prev"');
        }
        return $this->_get_link($this->_get_url('#', true),$this->str_prevpage,$this->css_disable);
    }
    
    //下一页
    function getnextpage()
    {
        if($this->pagenow < $this->totalpages)
        {
            return $this->_get_link($this->_get_url($this->pagenow + 1),$this->str_nextpage,$this->css_normal,'id="page_next"');
        }
        return $this->_get_link($this->_get_url('#', true),$this->str_nextpage,$this->css_disable);
    }
    
    //最尾页
    function getlastpage()
    {
        if($this->pagenow == $this->totalpages){
        	return $this->_get_link($this->_get_url('#', true),$this->str_lastpage,$this->css_disable);
        }
        return $this->_get_link($this->_get_url($this->totalpages),$this->str_lastpage,$this->css_normal,'id="page_last"');
    }
    //页码盘 [1][2][3][4][5][6][7][8][9][10]
    function getpagebar()
    {
        $plus = ceil($this->pagebar_num/2);
        if(($this->pagebar_num-$plus + $this->pagenow) > $this->totalpages){
        	$plus = ($this->pagebar_num - ($this->totalpages - $this->pagenow));
        }
        $begin = $this->pagenow - $plus + 1;
        $begin = ($begin>=1) ? $begin : 1;
        $return='';
        for($i=$begin; $i < $begin+$this->pagebar_num; $i++)
        {
            if( $i <= $this->totalpages){
                if($i != $this->pagenow){
                	$return .= $this->_get_link($this->_get_url($i),$i,$this->css_normal);
                }else{
                	$return .= $this->_get_link($this->_get_url($i.'#', true),$i,$this->css_active);
                }
            }else{
                break;
            }
            $return.="\n";
        }
        unset($begin);
        return $return;
    }
    
    function select()
    {
        $return='<select name="Tea_Page_Select">';
        for($i=1;$i<=$this->totalpages;$i++)
        {
            if($i==$this->pagenow){
                $return.='<option value="'.$i.'" selected>'.$i.'</option>';
            }else{
                $return.='<option value="'.$i.'">'.$i.'</option>';
            }
        }
        unset($i);
        $return.='</select>';
        return $return;
    }
    
    function _get_tag($start=0,$style=''){
    	if($start){
    		if(!empty($style)){
    			return '<'.$this->html_tag.' class="'.$style.'">';
    		}else{
    			return '<'.$this->html_tag.'>';
    		}
    	}else{
    		return '</'.$this->html_tag.'>';
    	}
    }

    function _get_url($pageno = 1, $isself = false)
    {
        if($isself == true)	return '#'; //如果是本页,判断是true,修复id62下50页pageno=0时候,返回的情况
        if(function_exists('id62')){
            return url(null,array($this->page_var=>id62($pageno)));
        }
        return url(null,array($this->page_var=>$pageno));
    }
	
    //内部函数，获取连接
    function _get_link($url,$text,$style='',$idstr=''){
        return $this->_get_tag(1,$style).'<a href="'.$url.'" '.$idstr.'>'.$text.'</a>'.$this->_get_tag();
    }
    
}
	