<?php

namespace FoolTemplate;

class FoolTemplateTokenizer extends PhpTokenizer
{

    public function __construct()
    {
        $this->setup_splitters([]);
    }

    public static function get_next_open_tag($str, $index)
    {
        if (@$str[$index] !== '{') {
            return array(false, $index);
        } else {
            return array(array(T_OPEN_TAG, "{", NULL), $index + 1);
        }
    }

    public function token_get_all($str, $mode, $throw_error = false, $post_process_mark_variable_string = false)
    {
        if ($str === "") {
            return array();
        }
        $tokens = array();
        $index = 0;
        if ($mode == "render") {
            list($token, $index) = static::get_next_inline_html($str, $index, "{");
            if ($token[1] !== "") {
                $tokens[] = $token;
            }
            list($token, $index) = static::get_next_open_tag($str, $index);
            if ($token !== false) {
                $tokens[] = $token;
            }
        } elseif ($mode == "eval") {
        } else {
            throw new \ErrorException("param mode should be render/eval");
        }
        $cnt = 1;
        for (;;) {
            list($token, $index) = $this->get_next_token($str, $index, $throw_error);
            if ($token === false) {
                break;
            } elseif ($token == "}") {
                $cnt--;
                if ($cnt == 0) {
                    $tokens[] = array(T_CLOSE_TAG, $token, NULL);
                    list($token, $index) = self::get_next_inline_html($str, $index, "{");
                    if ($token[1] !== "") {
                        $tokens[] = $token;
                    }
                    list($token, $index) = self::get_next_open_tag($str, $index);
                    if ($token !== false) {
                        $cnt = 1;
                        $tokens[] = $token;
                    } else {
                        break;
                    }
                }
            } else {
                if ($token === "{") {
                    $cnt++;
                }
                $tokens[] = $token;
            }
        }
        if ($post_process_mark_variable_string) {
            $tokens = self::post_process_mark_variable_string($tokens);
        }
        return $tokens;
    }
}
