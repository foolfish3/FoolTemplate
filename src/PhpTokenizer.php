<?php
namespace FoolTemplate;

class PhpTokenizer{

    public function __construct(){
        $this->setup_splitters(array("?>"));
    }

    public const T_VARIABLE_STATIC_PROPERTY=10001;
    public const T_VARIABLE_FUNCTION=10002;
    //public const T_VARIABLE_CLASS=10003;

    public const T_STRING_CLASS_NAME=11001;
    public const T_STRING_CLASS_CONSTANT=11002;
    public const T_STRING_CLASS_PROPERTY=11003;
    public const T_STRING_CLASS_STATIC_METHOD=11004;
    public const T_STRING_CLASS_METHOD=11005;
    public const T_STRING_FUNCTION=11006;

    public const T_OPEN_TAG=12001;
    public const T_CLOSE_TAG=12002;
    public static function token_name($code){
        switch($code){
            case 10001: return "T_VARIABLE_STATIC_PROPERTY";
            case 10002: return "T_VARIABLE_FUNCTION";
            //case 10003: return "T_VARIABLE_CLASS";
            case 11001: return "T_STRING_CLASS_NAME";
            case 11002: return "T_STRING_CLASS_CONSTANT";
            case 11003: return "T_STRING_CLASS_PROPERTY";
            case 11004: return "T_STRING_CLASS_STATIC_METHOD";
            case 11005: return "T_STRING_CLASS_METHOD";
            case 11006: return "T_STRING_FUNCTION";
        }
        return token_name($code);
    }
    public static function join_tokens($tokens){
        foreach($tokens as &$token){
            if(\is_array($token)){
                $token=$token[1];
            }
        }
        return \implode($tokens);
    }
    public static function dump_tokens($tokens,$return=false){
        $s="";
        foreach($tokens as $k => &$token){
            if(\is_array($token)){
                $s.="$k: (".self::token_name($token[0]).") ".\var_export($token[1],true)."\n";
            }else{
                $s.="$k: ".\var_export($token,true)."\n";
            }
        }
        if(!$return){
            echo $s;
        }
        return $s;
    }

	public static function is_digit($char){
		switch($char){
			case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
				return true;
			default:
				return false;
		}
    }

	public static function is_valid_name_char($char){
		$c=ord($char);
		return ($c>=48 && $c<=57) || ($c>=65 && $c<=90) || $c==95 || ($c>=97 && $c<=122) || $c>=127;
	}

	public static function is_valid_name_start_char($char){
		$c=ord($char);
		return ($c>=65 && $c<=90) || $c==95 || ($c>=97 && $c<=122) || $c>=127;
	}

    public static $keywords=array(
        '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor',
        'int','true','false','float','bool','string','null','void','object','iterable',
        '__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__',
        'self','parent',
    );

    public static function is_keyword($k){
		static $map=NULL;
		if($map===NULL){
			foreach(self::$keywords as $keyword){
				$map[strtolower($keyword)]=$keyword;
			}
		}
		return @$map[strtolower($k)];
	}

	//start with [0-9\\.]
	public static function get_next_number($chars,$index,$throw_error){
		$error=NULL;
		$oct_error=NULL;
		$is_float=false;
		$s=array();
		$state=0;
		for(;$index<\count($chars)-1;$index++){
			$c=$chars[$index];
			switch($state){
				case 0:
					switch($c){//初始状态，可以接受数字和.输入
						case '0':
							$s[]=$c;
							$state=1;
						break;
						case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
							$state=2;
						break;
						case '.':
							$s[]=$c;
							$state=3;
						break;
						default:
							throw new \ErrorException("BUG");
					}
				break;
				case 1://0~
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
							$state=4;
						break;
						case 'x':case 'X':
							$s[]=$c;
							$state=5;
						break;
						case 'b':case 'B':
							$s[]=$c;
							$state=6;
						break;
						case 'e':case 'E':
							$s[]=$c;
							$state=7;
						break;
						case '.':
							$s[]=$c;
							$state=8;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
							break 3;
					}
				break;
				case 2://[1-9]~
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
						break;
						case 'e':case 'E':
							$s[]=$c;
							$state=7;
						break;
						case '.':
							$s[]=$c;
							$state=8;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
						break 3;
					}
				break;
				case 3://.~
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
							$state=8;
						break;
						case '':
						default:
							return array('.',$index);
					}
				break;
				case 4://8进制状态 0888
					switch($c){
						case '8':case '9':
							if($oct_error===NULL){
								$oct_error="invalid char after number ".\implode($s);
							}
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':
							$s[]=$c;
						break;
						case 'e':case 'E':
							$s[]=$c;
							$state=7;
						break;
						case '.':
							$s[]=$c;
							$state=8;
						break;
						case '':
						default:
							if($oct_error!==NULL){
								$error=$oct_error;
							}elseif(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
						break 3;
					}
				break;
				case 5://0x~
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
						case 'a':case 'A':case 'b':case 'B':case 'c':case 'C':case 'd':case 'D':case 'e':case 'E':case 'f':case 'F':
							$s[]=$c;
							$state=9;
						break;
						case '':
						default:
							$s=\array_slice($s,0,-1);
							$index--;
							$error="invalid char after number ".\implode($s);
						break 3;
					}
				break;
				case 6://0b~
					switch($c){
						case '0':
						case '1':
							$s[]=$c;
							$state=10;
						break;
						case '':
						default:
							$s=\array_slice($s,0,-1);
							$index--;
							$error="invalid char after number ".\implode($s);
						break 3;
					}
				break;
				case 7://0E
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
							$state=11;
						break;
						case '+':case '-':
							$s[]=$c;
							$state=12;
						break;
						case '':
						default:
							$s=\array_slice($s,0,-1);
							$index--;
							$error="invalid char after number ".\implode($s);
						break 3;
					}
				break;
				case 8://1.
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
						break;
						case 'e':case 'E':
							$s[]=$c;
							$state=7;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
							$is_float=true;
						break 3;
					}
				break;
				case 9://0xA~
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
						case 'a':case 'A':case 'b':case 'B':case 'c':case 'C':case 'd':case 'D':case 'e':case 'E':case 'f':case 'F':
							$s[]=$c;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
						break 3;
					}
				break;
				case 10://0x0
					switch($c){
						case '0':case '1':
							$s[]=$c;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
						break 3;
					}
				break;
				case 11://0E1
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
						break;
						case '':
						default:
							if(self::is_valid_name_char($c)){
								$error="invalid char after number ".\implode($s);
							}
							$is_float=true;
						break 3;
					}
				break;
				case 12://0E+
					switch($c){
						case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
							$s[]=$c;
							$state=11;
						break;
						case '':
						default:
							$s=\array_slice($s,0,-2);
							$index-=2;
							$error="invalid char after number ".\implode($s);
						break 3;
					}
				break;
				default:
					throw new \ErrorException("BUG");
			}
		}
		if($throw_error && $error!==NULL){
			throw new \ErrorException($error);
		}
		return array(array($is_float?T_DNUMBER:T_LNUMBER,\implode($s),$error),$index);
	}

	//start with [\'\"\`]
	public static function get_next_quote($chars,$index,$throw_error){
		$quote=$chars[$index++];
		$s=array($quote);
		for(;$index<\count($chars)-1;$index++){
            $c=$chars[$index];
			if($c==="\\"){
				$s[]=$c;
				$s[]=$chars[$index++];
			}elseif($c==="\$" && ($quote=="\"" || $quote=="`")){//not allow $ in double quotes and back quotes
				$s[]="\\$";
			}elseif($c===$quote){
                $s[]=$c;
				return array(array(T_CONSTANT_ENCAPSED_STRING,\implode($s),NULL),$index+1);
			}else{
                $s[]=$c;
            }
		}
		$error="cannot find matched $quote in string ".\implode($s);
		if($throw_error && $error!==NULL){
			throw new \ErrorException($error);
		}
		return array(array(T_CONSTANT_ENCAPSED_STRING,\implode($s),$error),\count($chars)-1);
	}

	//start with [\#\/]
	public static function get_next_comment($chars,$index){
		if($chars[$index]==="/" && $chars[$index+1]==="*" ){
			$s=array();
			for(;$index<\count($chars)-1;$index++){
				$c=$chars[$index];
				if($c==="*" &&$chars[$index+1]==="/"){
					$s[]="*/";
					return array(array(T_COMMENT,\implode($s),NULL),$index+2);
				}elseif($c===""){
					break;
				}else{
					$s[]=$c;
				}
			}
			return array(array(T_COMMENT,\implode($s),NULL),\count($chars)-1);
		}elseif($chars[$index]==="#" || $chars[$index+1]==="/"){
			$s=array();
			for(;$index<\count($chars)-1;$index++){
				$c=$chars[$index];
				if($c==="\r"||$c==="\n"){
					return array(array(T_COMMENT,\implode($s),NULL),$index);
				}else{
					$s[]=$c;
				}
			}
			return array(array(T_COMMENT,\implode($s),NULL),\count($chars)-1);
		}else{
			return array("/",$index+1);
		}
	}

	//start with [\t\r\n ]
	public static function get_next_whitespace($chars,$index){
		$map=array(" "=>1,"\r"=>1,"\n"=>1,"\t"=>1);
		$s=array();
		for(;$index<\count($chars)-1;$index++){
			$c=$chars[$index];
			if(isset($map[$c])){
				$s[]=$c;
			}else{
				break;
			}
		}
		return array(array(T_WHITESPACE,\implode($s),NULL),$index);
	}

	public static function get_next_splitter($last,$chars,$index,$map){
		if($index>=\count($chars)-1){
			if(@$map[""]===true){
				return array($last,$index);
            }
			return false;
		}
		$c=$chars[$index];
		if(isset($map[$c])){
			$r=self::get_next_splitter($last.$c,$chars,$index+1,$map[$c]);
			if($r){
				return $r;
			}
        }
        if($last===""){
 			return false;
		}elseif(@$map[""]===true){
			return array($last,$index);
        }
 		return false;
	}

	private static function generate_splitter_map_set_map($str,&$map) {
		if($str===""){
			$map[""]=true;
		}else{
			if(!isset($map[$str[0]])){
				$map[$str[0]]=array();
			}
			self::generate_splitter_map_set_map(\substr($str,1),$map[$str[0]]);
		}
	}

	public static function generate_splitter_map($splitters){
		$map=array();
		foreach($splitters as $splitter){
			self::generate_splitter_map_set_map($splitter,$map);
        }
        //var_dump($map);
		return $map;
	}

	//start with [\$]
	public static function get_next_variable($chars,$index){
		$s=array($chars[$index++]);
		$state=0;
		for(;$index<\count($chars)-1;$index++){
			$c=$chars[$index];
			switch($state){
				case 0:
					if(!self::is_valid_name_start_char($c)){
						return array("\$",$index);
					}
					$s[]=$c;
					$state=1;
				break;
				case 1:
					if(!self::is_valid_name_char($c)){
						return array(array(T_VARIABLE,\implode($s),NULL),$index);
					}
					$s[]=$c;
				break;
				default:
					throw new \ErrorException("BUG");
			}
		}
		return array(array(T_VARIABLE,\implode($s),NULL),\count($chars)-1);
	}

	//start with [\\a-zA-Z_\x7f-\xff][
	public static function get_next_string($chars,$index){
		$s=array();
		for(;$index<\count($chars)-1;$index++){
			$c=$chars[$index];
			if(self::is_valid_name_char($c)){
				$s[]=$c;
			}else{
				break;
			}
		}
		return array(array(T_STRING,\implode($s),NULL),$index);
    }

    public function get_next_open_tag($chars,$index){
        if(!(@$chars[$index]==='<' && @$chars[$index+1]==='?')){
            return false;
        }
        if($chars[$index+2]=='='){
            return array(array(T_OPEN_TAG,"<?=",NULL),$index+3);
        }
        $s=\implode(\array_slice($chars,$index,5));//<?php
        if(strtolower($s)==="<?php" && self::is_token_blank($chars[$index+5])){
            return array(array(T_OPEN_TAG,$s,NULL),$index+5);
        }else{
            return array(array(T_OPEN_TAG,"<?",NULL),$index+2);
        }
    }


    public static function get_next_inline_html($chars,$index,$open_tag){
        $open_tag=\str_split($open_tag);
        $length=count($open_tag);
        $s=array();
        for(;$index<\count($chars)-1;$index++){
            $c=$chars[$index];
            if($c===$open_tag[0] && ($length==1 || \array_slice($chars,$index,$length)===$open_tag)){
                return array(array(T_INLINE_HTML,\implode($s),NULL),$index);
            }elseif($c===""){
                break;
            }else{
                $s[]=$c;
            }
        }
        return array(array(T_INLINE_HTML,\implode($s),NULL),\count($chars)-1);
    }



    public static function post_process_mark_keywords($tokens,$convert_keywords_case){
        //return $tokens;
        $last_token=NULL;
        foreach($tokens as &$token){
            if($token[0]==T_STRING){
                $keyword=self::is_keyword($token[1]);
                if($keyword && $last_token!="::" && $last_token!="->"){
                    $token=$convert_keywords_case?$keyword:$token[1];
                }
            }
            if(!self::is_token_blank($token)){
                $last_token=$token;
            }
        }
        return $tokens;
    }

    public static function is_token_blank($token){
        static $map=array(" "=>1,"\r"=>1,"\n"=>1,"\t"=>1,"\r\n"=>1);
        if(\is_array($token)){
            return $token[0]==T_COMMENT||$token[0]==T_WHITESPACE;
        }else{
            for($i=strlen($token);$i-->0;){
                if(!isset($map[$token[$i]])){
                    return false;
                }
            }
            return true;
        }
    }

    public static function post_process_strip_whitespace($tokens){
        $map=array("::"=>1,"->"=>1,":"=>1,"["=>1,"]"=>1,"{"=>1,"}"=>1,"("=>1,")"=>1,";"=>1,","=>1);
        $ss=array();
        $has_whitespace=false;
        $strip_next_whitespace=true;
        foreach($tokens as $token){
            if(self::is_token_blank($token)){
                if(!$strip_next_whitespace){
                    $has_whitespace=true;
                }
            }else{
                $strip_next_whitespace=!\is_array($token) && isset($map[$token]);
                if($strip_next_whitespace){
                    $has_whitespace=false;
                }else{
                    if($has_whitespace){
                        $has_whitespace=false;
                        $ss[]=" ";
                    }
                }
                $ss[]=$token;
            }
        }
        //end whitespace is ignored
        return $ss;
    }

    public static function post_process_mark_variable_string($tokens){
        $tokens=self::post_process_strip_whitespace($tokens);
        $tokens=self::post_process_mark_keywords($tokens,true);
        $tokens=self::post_process_merge_backslash($tokens);
        $last_token=NULL;
        for($index=0;$index<\count($tokens);$index++){
            $token=$tokens[$index];
            if(\is_array($token)){
                if($token[0]===T_VARIABLE){
                    if($last_token==="new"||@$tokens[$index+1]==="::"){
                        //$token[0]=self::T_VARIABLE_CLASS;
                    }elseif(@$tokens[$index+1]==="("){
                        $token[0]=self::T_VARIABLE_FUNCTION;
                    }elseif($last_token==="::"){
                        $token[0]=self::T_VARIABLE_STATIC_PROPERTY;
                    }elseif($last_token==="->"){//成员变量
                    
                    }
                }elseif($token[0]===T_STRING){
                    if($last_token==="new" ||$last_token==="instanceof"){
                        $token[0]=self::T_STRING_CLASS_NAME;
                    }elseif($last_token==="::"){
                        if(@$tokens[$index+1]==="("){
                            $token[0]=self::T_STRING_CLASS_STATIC_METHOD;
                        }else{
                            $token[0]=self::T_STRING_CLASS_CONSTANT;
                        }
                    }elseif($last_token==="->"){
                        if(@$tokens[$index+1]==="("){
                            $token[0]=self::T_STRING_CLASS_METHOD;
                        }else{
                            $token[0]=self::T_STRING_CLASS_PROPERTY;
                        }
                    }elseif(@$tokens[$index+1]==="("){
                        $token[0]=self::T_STRING_FUNCTION;
                    }elseif(\is_array($tokens[$index+1]) && $tokens[$index+1][0]===T_VARIABLE){
                        $token[0]=self::T_STRING_CLASS_NAME;
                    }
                }
                $tokens[$index]=$token;
            }
            if(!self::is_token_blank($token)){
                $last_token=$token;
            }
        }
        return $tokens;
    }

    public static function post_process_merge_backslash($tokens){
        $ss=array();
        for($index=0;$index<\count($tokens);$index++){
            $token=$tokens[$index];
            $token_str=$token=="\\"?$token:false;
RESTART:    if($token_str){
                for($j=$index+1;$j<\count($tokens);$j++){
                    $next_token=$tokens[$j];
                    if(self::is_token_blank($next_token)){
                        continue;
                    }elseif(\is_array($next_token)&&$next_token[0]==T_STRING){
                        $token=array(T_STRING,$token_str.$next_token[1]);
                        $index=$j;
                        $token_str=false;
                        goto RESTART;
                    }else{
                        break;
                    }
                }
            }elseif(\is_array($token)&&$token[0]==T_STRING){
                for($j=$index+1;$j<\count($tokens);$j++){
                    $next_token=$tokens[$j];
                    if(self::is_token_blank($next_token)){
                        continue;
                    }elseif($next_token=="\\"){
                        $token=array(T_STRING,$token[1].$next_token);
                        $index=$j;
                        $token_str=$token[1];
                        goto RESTART;
                    }else{
                        break;
                    }
                }
            }
            $ss[]=$token;
        }
        return $ss;
    }

    protected $splitters_map;
    protected function setup_splitters($addition_splitters){
        $splitters=array(
			"=>",//foreach
			"**","++","--",">>","<<",
            ">=", "<=","==","!=","===","!==","<>","<=>",
            "??","?:",
            "+=","-=","*=","**=","/=",".=","%=","&=","|=","^=","<<=",">>=",
            "\r\n","&&","||","::","->",
            "[","]","{","}",
            ">","<","!","^","&","|","=","(", ")", "\t","\r","\n"," ","@",":","+","-","*","/","%",";",",",".",":","?","#","~",
            "\"","'","`",
            "\\",
            //"{{","}}",
            /* ,"<?=","<?","<?php","?>" */
        );
        $this->splitters_map=self::generate_splitter_map(array_merge($splitters,$addition_splitters));
    }

	protected function get_next_token($chars,$index,$throw_error){
		$c=$chars[$index];
		switch($c){
			case "`":case "'":case "\"":
				return self::get_next_quote($chars,$index,$throw_error);
			case "#":case "/":
				return self::get_next_comment($chars,$index,$throw_error);
			case "\$":
                return self::get_next_variable($chars,$index,$throw_error);
            case '0':case '1':case '2':case '3':case '4':case '5':case '6':case '7':case '8':case '9':
                return self::get_next_number($chars,$index,$throw_error);
            case " ":case "\t":case "\n":
                return self::get_next_whitespace($chars,$index,$throw_error);
            case "":
                return false;//END
            default:
                if(self::is_valid_name_start_char($c)){
                    return self::get_next_string($chars,$index,$throw_error);
                }else{
                    if(($r=self::get_next_splitter("",$chars,$index,$this->splitters_map))){
                        return $r;
                    }else{
                        if($throw_error){
                            throw new \ErrorException("unkown char $c (".ord($c).")");
                        }
                    }
                }
		}
    }

    //override as you like
    public function token_get_all($str,$mode,$throw_error=false,$post_process_mark_variable_string=false){
        if($str===""){
			return array();
		}
        $chars=\str_split($str);
        $chars[]="";
        $tokens=array();
        $index=0;
        if($mode=="render"){
            $r=self::get_next_inline_html($chars,$index,"<?");
            $index=$r[1];
            if($r[0][1]!==""){
                $tokens[]=$r[0];
            }
            $r=$this->get_next_open_tag($chars,$index);
            if($r){
                $tokens[]=$r[0];
                $index=$r[1];
            }
        }elseif($mode=="eval"){
            
        }else{
            throw new \ErrorException("param mode should be render/eval");
        }
        for(;;){
            if($r=$this->get_next_token($chars,$index,$throw_error)){
                $index=$r[1];
                if($r[0]=="?>"){
                    $tokens[]=array(T_CLOSE_TAG,$r[0],NULL);
                    $r=self::get_next_inline_html($chars,$index,"<?");
                    $index=$r[1];
                    if($r[0][1]!==""){
                        $tokens[]=$r[0];
                    }
                    $r=$this->get_next_open_tag($chars,$index);
                    if($r){
                        $tokens[]=$r[0];
                        $index=$r[1];
                    }else{
                        break;
                    }
                }else{
                    $tokens[]=$r[0];
                }
            }else{
                break;
            }
        }
        if($post_process_mark_variable_string){
            $tokens=self::post_process_mark_variable_string($tokens);
        }
        return $tokens;
    }

}