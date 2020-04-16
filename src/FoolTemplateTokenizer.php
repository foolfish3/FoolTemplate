<?php
namespace FoolTemplate;

class FoolTemplateTokenizer extends PhpTokenizer{

    public function __construct(){
		$this->setup_splitters([]);
	}

    public function get_next_open_tag($chars,$index){
        if(@$chars[$index]!=='{'){
            return false;
        }else{
			return array(array(T_OPEN_TAG,"{",NULL),$index+1);
		}
    }

    public function token_get_all($str,$mode,$throw_error=false,$post_process_mark_variable_string=false){
        if($str===""){
			return array();
		}
        $chars=\str_split($str);
        $chars[]="";
        $tokens=array();
        $index=0;
        if($mode=="render"){
			$r=self::get_next_inline_html($chars,$index,"{");
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
		$cnt=1;
        for(;;){
            if($r=$this->get_next_token($chars,$index,$throw_error)){
				$index=$r[1];
				if($r[0]=="}"){
					$cnt--;
					if($cnt==0){
						$tokens[]=array(T_CLOSE_TAG,$r[0],NULL);
						$r=self::get_next_inline_html($chars,$index,"{");
						$index=$r[1];
						if($r[0][1]!==""){
							$tokens[]=$r[0];
						}
						$r=$this->get_next_open_tag($chars,$index);
						if($r){
							$cnt=1;
							$tokens[]=$r[0];
							$index=$r[1];
						}else{
							break;
						}
					}
				}else{
					if($r[0]=="{"){
						$cnt++;
					}
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