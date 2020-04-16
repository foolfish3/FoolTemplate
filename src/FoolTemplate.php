<?php
namespace FoolTemplate;

class FoolTemplate {
	public static function missing(){
		static $obj;
		if($obj===NULL){
			$obj=new \stdClass();
		}
		return $obj;
	}

	public function __construct(){
		$this->reset();
	}

	private function compile_part($tokens,$mode){
		if(\count($tokens)==0){
			return "";
		}
		if($mode=="render"){
			if($tokens[0]===":"){
				$quote_name=NULL;
				$has_quote=true;
				\array_shift($tokens);
			}elseif(@$tokens[1]===":" && \is_array($tokens[0]) && $tokens[0][0]===T_STRING){
				$quote_name=$tokens[0][1];
				$has_quote=true;
				\array_shift($tokens);
				\array_shift($tokens);
			}else{
				$quote_name=NULL;
				$has_quote=false;
			}
			if(!$has_quote && $this->mode==1){
				if($this->tag_style=="php"){
					throw new \ErrorException("must <?= in strict mode");
				}else{
					throw new \ErrorException("must {: in strict mode");
				}
			}
		}
		$tokens[]="";
		$ss=array();
		for($index=0;$index<\count($tokens);$index++){
			$token=$tokens[$index];
			if(\is_array($token)){
				if(\in_array($token[0],[T_OPEN_TAG,T_CLOSE_TAG,T_INLINE_HTML])){
					throw new \ErrorException("inline html is not supported in eval mode");
				}
				if($token[0]===T_STRING){
					if($this->allow_constants && defined($token[1])){
						//this is a constant, don't replace it
					}else{
						$ss[]="\$this->callvar(".\var_export($token[1],true).")";
					}
				}elseif($token[0]===PhpTokenizer::T_STRING_FUNCTION){
					$func=\ltrim($token[1],"\\");
					if($this->allow_all_functions || isset($this->allow_functions["\\".$func]) && \function_exists("\\".$func)){
                        $ss[]="\\".$func;
					}else{
						if(\strpos($func,"\\")!==false){
							throw new \ErrorException("function name {$token[1]} error");
						}
						if(@$tokens[$index+2]==")"){
							$ss[]="\$this->callfunc(".\var_export($token[1],true);
						}else{
							$ss[]="\$this->callfunc(".\var_export($token[1],true).",";
						}
						$index++;
					}
				}elseif($token[0]===T_VARIABLE){
					$ss[]="\$var_".\substr($token[1],1);//rename it,for not conflict with Predefined Variables
				}else{
					$ss[]=$token[1];
				}
			}else{
				$ss[]=$token;
			}
		}
		$code=\rtrim(\implode($ss),";");
		if($mode=="render"){
			if($has_quote){
				if($quote_name===NULL){
					if($code===""){
						return "";
					}
					$code="echo($code)";
				}elseif($code===""){
					$code="echo(\$this->callfunc(".\var_export($quote_name,true).",''))";
				}else{
					$code="echo(\$this->callfunc(".\var_export($quote_name,true).",$code))";
				}
			}
		}
		$code.=";";
		return $code;
	}

	//render eval
	public function compile($str,$mode=NULL){
		if($str===""){
			return "";
		}
		$tokenizer = $this->tag_style=="php"?new PhpTokenizer():new FoolTemplateTokenizer();
		if($mode=="expr"){
			$str="return (".\rtrim($str,";").");";
			$mode="eval";
		}elseif($mode===NULL){
            $mode="render";
        }
		$tokens=$tokenizer->token_get_all($str,$mode,true,false);
		if(!$this->allow_comment){
			foreach($tokens as $token){
				if(\is_array($token) && $token[0]===T_COMMENT){
					throw new \ErrorException("comment is not allowed");
				}
			}
		}
        $tokens=PhpTokenizer::post_process_mark_variable_string($tokens);
		$allow_variables=false;
		$allowed_tokens =array("true"=>1,"false"=>1,"null"=>1,"isset"=>1,"echo"=>1,"array"=>1,"return"=>1,"and"=>1,"or"=>1);
		$disallowed_tokens=array();
		if(!$this->allow_class){
			$disallowed_tokens["::"]=1;
			$disallowed_tokens["\\"]=1;
			$disallowed_tokens["new"]=1;
		}
		if($this->mode>=2){
			foreach(['unset','list','new','instanceof','throw'] as $keyword){
				$allowed_keywords[$keyword]=1;
			}
			$allow_variables=true;
		}
		if($this->mode>=3){
			foreach(['function','return','while','do','for','foreach','as','break','contine','try','catch','switch','case','default','if','else','elsif','elseif','use'] as $keyword){
				$allowed_keywords[$keyword]=1;
			}
		}
		$last_token=NULL;
		foreach($tokens as $token){
			if(\is_array($token)){
				if($token[0]===PhpTokenizer::T_VARIABLE_FUNCTION){
					throw new \ErrorException("variable function is not allowed");
				}
				if(!$allow_variables && \in_array($token[0],[T_VARIABLE,PhpTokenizer::T_VARIABLE_FUNCTION])){
					throw new \ErrorException("variable is not allowed");
				}
				if($token[0]==T_STRING && $last_token=="function"){
					throw new \ErrorException("named function is not allowed");
				}
			}else{
				if(isset($disallowed_tokens[$token])){
					$allow=false;
				}elseif(!PhpTokenizer::is_keyword($token)){
					$allow=true;
				}elseif(isset($allowed_tokens[$token])){
					$allow=true;
				}else{
					$allow=false;
				}
				if(!$allow){
					throw new \ErrorException("token $token is not allowed");
				}
			}
			if(!PhpTokenizer::is_token_blank($token)){
                $last_token=$token;
            }
		}
		if($mode==="eval"){
			return $this->compile_part($tokens,$mode);
		}
		$tokens[]="";
		$ss=array();
		for($index=0;$index<\count($tokens);$index++){
			$token=$tokens[$index];
			if(\is_array($token)){
				if($mode==="eval" && \in_array($token[0],[T_OPEN_TAG,T_CLOSE_TAG,T_INLINE_HTML])){
					throw new \ErrorException("inline html is not supported in eval mode");
				}
				if($token[0]===T_INLINE_HTML){
					$ss[]="echo(".\var_export($token[1],true).");";
				}elseif($token[0]===T_OPEN_TAG){
					if($this->tag_style=="php" && $token[1]==="<?="){
						$sub_tokens=array(":");
					}else{
						$sub_tokens=array();
					}
					$index++;
					for(;$index<\count($tokens);$index++){
						$token=$tokens[$index];
						if($token===""||(\is_array($token) && $token[0]===T_CLOSE_TAG)){
							break;
						}else{
							$sub_tokens[]=$token;
						}
					}
					$ss[]=$this->compile_part($sub_tokens,true);
				}elseif($token[0]===T_CLOSE_TAG){
					throw new \ErrorException("not matched close tag {$token[1]}");
				}else{
					$ss[]=$token[1];
				}
			}
			else{
				$ss[]=$token;
			}
		}
		return \implode($ss);
	}
	protected $mode;

	//only expression allowed, statement is not allowed
	public function with_strict_mode(){
		$this->mode=1;
		return $this;
	}

	//allow statement, variable
	public function with_basic_mode(){
		$this->mode=2;
		return $this;
	}

	//allow for try catch anonymous function
	public function with_advanced_mode(){
		$this->mode=3;
		return $this;
	}

	protected $tag_style;
	public function with_php_tag_style(){
		$this->tag_style="php";
		return $this;
	}

	protected $allow_constants;
	public function allow_constants($allow=true){
		$this->allow_constants=$allow;
		return $this;
	}

	protected $allow_all_functions;
	public function allow_all_functions($allow=true){
		$this->allow_all_functions=$allow;
		return $this;
	}

	protected $allow_functions;
	public function allow_functions($list){
		foreach($list as $func){
			$this->allow_functions[strtolower("\\".ltrim($func,"\\"))]=1;
		}
		return $this;
	}

	protected $allow_class;
	public function allow_class($allow=true){
		$this->allow_class=$allow;
		return $this;
	}

	protected $allow_comment;
	public function allow_comment($allow=true){
		$this->allow_comment=$allow;
		return $this;
	}

	public function allow_all(){
		return $this->reset()->with_advanced_mode()->allow_comment()->allow_class()->allow_all_functions()->allow_constants();
	}

	protected $func_map;
	protected $func_fallback;
	public function callfunc($name){
		$params=func_get_args();
		$name=array_shift($params);
		if(method_exists($this,"func_$name")){
			return \call_user_func_array([$this,"func_$name"],$params);
		}elseif(key_exists($name,$this->func_map)){
			return \call_user_func_array($this->func_map[$name],$params);
		}elseif($this->func_fallback!==NULL){
			$r=\call_user_func_array($this->func_fallback,func_get_args());
			if($r!==self::missing()){
				return $r;
			}
		}
		throw new \ErrorException("function $name not found");
	}

	public function with_func_map($func_map,$func_fallback=NULL){
		$this->var_map=$func_map;
		$this->var_fallback=$func_fallback;
		return $this;
	}

	protected $var_map;
	protected $var_fallback;
	public function callvar($name){
		if(\key_exists($this->var_map,$name)){
			return $this->var_map[$name];
		}elseif($this->var_fallback!==NULL){
			$r=\call_user_func($this->var_fallback,$name);
			if($r!==self::missing()){
				return $r;
			}
		}
		throw new \ErrorException("var $name not found");
	}

	public function with_var_map($var_map,$var_fallback=NULL){
		$this->var_map=$var_map;
		$this->var_fallback=$var_fallback;
		return $this;
	}

	public function reset(){
		$this->mode=1;
		$this->tag_style="default";
		$this->allow_all_functions=false;
		$this->allow_constant=false;
		$this->allow_comment=false;
		$this->allow_functions=array();

		$this->var_map=array();
		$this->var_fallback=NULL;
		$this->func_map=array();
		$this->func_fallback=NULL;

		return $this;
	}

	public function expr(){
		return eval($this->compile(func_get_arg(0),"expr"));
	}

	public function doeval(){
		return eval($this->compile(func_get_arg(0),"eval"));
	}

	public function render(){
		if(func_num_args()<2 || func_get_arg(1)===NULL||func_get_arg(1)){
			ob_start();
			eval($this->compile(func_get_arg(0),"render"));
			$output=ob_get_contents();
			ob_end_clean();
			return $output;
		}else{
			eval($this->compile(func_get_arg(0),"render"));
		}
	}
}