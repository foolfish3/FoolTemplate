<?php

namespace FoolTemplate;

class PhpTokenizer
{

    public function __construct()
    {
        $this->setup_splitters(array("?>"));
    }

    const T_VARIABLE_STATIC_PROPERTY = 10001;
    const T_VARIABLE_FUNCTION = 10002;
    //public const T_VARIABLE_CLASS=10003;

    const T_STRING_CLASS_NAME = 11001;
    const T_STRING_CLASS_CONSTANT = 11002;
    const T_STRING_CLASS_PROPERTY = 11003;
    const T_STRING_CLASS_STATIC_METHOD = 11004;
    const T_STRING_CLASS_METHOD = 11005;
    const T_STRING_FUNCTION = 11006;

    const T_OPEN_TAG = 12001;
    const T_CLOSE_TAG = 12002;
    public static function token_name($code)
    {
        switch ($code) {
            case 10001:
                return "T_VARIABLE_STATIC_PROPERTY";
            case 10002:
                return "T_VARIABLE_FUNCTION";
                //case 10003: return "T_VARIABLE_CLASS";
            case 11001:
                return "T_STRING_CLASS_NAME";
            case 11002:
                return "T_STRING_CLASS_CONSTANT";
            case 11003:
                return "T_STRING_CLASS_PROPERTY";
            case 11004:
                return "T_STRING_CLASS_STATIC_METHOD";
            case 11005:
                return "T_STRING_CLASS_METHOD";
            case 11006:
                return "T_STRING_FUNCTION";
        }
        return token_name($code);
    }

    public static function join_tokens($tokens)
    {
        foreach ($tokens as &$token) {
            if (\is_array($token)) {
                $token = $token[1];
            }
        }
        return \implode($tokens);
    }

    public static function dump_tokens($tokens, $return = false)
    {
        $s = "";
        foreach ($tokens as $k => &$token) {
            if (\is_array($token)) {
                $s .= "$k: (" . self::token_name($token[0]) . ") " . \var_export($token[1], true) . "\n";
            } else {
                $s .= "$k: " . \var_export($token, true) . "\n";
            }
        }
        if (!$return) {
            echo $s;
        }
        return $s;
    }

    public static function is_digit($char)
    {
        static $map = array(0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 1, 8 => 1, 9 => 1);
        return isset($map[$char]);
    }

    public static function is_valid_name_char($char)
    {
        $c = ord($char);
        return ($c >= 48 && $c <= 57) || ($c >= 65 && $c <= 90) || $c == 95 || ($c >= 97 && $c <= 122) || $c >= 127;
    }

    public static function is_valid_name_start_char($char)
    {
        $c = ord($char);
        return ($c >= 65 && $c <= 90) || $c == 95 || ($c >= 97 && $c <= 122) || $c >= 127;
    }

    public static $keywords = array(
        '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor',
        'int', 'true', 'false', 'float', 'bool', 'string', 'null', 'void', 'object', 'iterable',
        '__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__',
        'self', 'parent',
    );

    public static function is_keyword($k)
    {
        static $map = NULL;
        if ($map === NULL) {
            foreach (self::$keywords as $keyword) {
                $map[strtolower($keyword)] = $keyword;
            }
        }
        return @$map[strtolower($k)];
    }

    //start with [0-9\\.]
    public static function get_next_number($str, $index, $throw_error)
    {
        static $map = array(
            "0" => 0, "1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9,
            "A" => 10, "a" => 10, "B" => 11, "b" => 11, "C" => 12, "c" => 12, "D" => 13, "d" => 13, "E" => 14, "e" => 14, "F" => 15, "f" => 15,
            "" => 16, "." => 17,
            "X" => 18, "x" => 18,
            "+" => 19, "-" => 20,
            "DEFAULT" => -1,
        );
        $error = NULL;
        $oct_error = NULL;
        $is_float = false;
        $s = "";
        $state = 0;
        $c = @$str[$old_index = $index];
        for (;;) {
            switch ($state) {
                case 0:
                    switch ($map[$c]) { //初始状态，可以接受数字和.输入
                        case 0:
                            $s .= $c;
                            $state = 1;
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            $state = 2;
                            break;
                        case 17: //.
                            $s .= $c;
                            $state = 3;
                            break;
                        default:
                            throw new \ErrorException("BUG");
                    }
                    $c = @$str[++$index];
                    break;
                case 1: //0~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($case) {
                        case 8:
                        case 9:
                            if ($oct_error === NULL) {
                                $oct_error = "invalid char after number $s";
                                $oct_index = $index;
                                $oct_s = $s;
                            }
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                            $s .= $c;
                            $state = 4;
                            break;
                        case 18: //X
                            $s .= $c;
                            $state = 5;
                            break;
                        case 11: //B
                            $s .= $c;
                            $state = 6;
                            break;
                        case 14: //E
                            $s .= $c;
                            $state = 7;
                            break;
                        case 17: //.
                            $s .= $c;
                            $state = 8;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 2: //[1-9]~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($case) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            break;
                        case 14: //E
                            $s .= $c;
                            $state = 7;
                            break;
                        case 17: // .
                            $s .= $c;
                            $state = 8;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 3: //.~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($case) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            $state = 8;
                            break;
                        case 16: // ''
                        default:
                            return array(false, $old_index);
                    }
                    $c = @$str[++$index];
                    break;
                case 4: //8进制状态 0888
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 8:
                        case 9:
                            if ($oct_error === NULL) {
                                $oct_error = "invalid char after number $s";
                                $oct_index = $index;
                                $oct_s = $s;
                            }
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                            $s .= $c;
                            break;
                            break 3;
                        case 14:
                            $s .= $c;
                            $state = 7;
                            break;
                        case 17:
                            $s .= $c;
                            $state = 8;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 5: //0x~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                        case 10:
                        case 11:
                        case 12:
                        case 13:
                        case 14:
                        case 15:
                            $s[] = $c;
                            $state = 9;
                            break;
                        case 16: // ''
                        default:
                            $index--;
                            $s = \substr($s, 0, -1);
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 6: //0b~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                            $s .= $c;
                            $state = 10;
                            break;
                        case 16:
                        default:
                            $index--;
                            $s = \substr($s, 0, -1);
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 7: //0E
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            $state = 11;
                            break;
                        case 19:
                        case 20:
                            $s .= $c;
                            $state = 12;
                            break;
                        case 16:
                        default:
                            $index--;
                            $s = \substr($s, 0, -1);
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 8: //1.
                    $is_float = true;
                    $oct_error = NULL;
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            break;
                        case 14:
                            $s .= $c;
                            $state = 7;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 9: //0xA~
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                        case 10:
                        case 11:
                        case 12:
                        case 13:
                        case 14:
                        case 15:
                            $s .= $c;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 10: //0x0
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                            $s .= $c;
                            break;
                        case 16: // ''
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 11: //0E1
                    $is_float = true;
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            break;
                        case 16:
                        default:
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                case 12: //0E+
                    $case = isset($map[$c]) ? $map[$c] : -1;
                    switch ($map[$c]) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            $s .= $c;
                            $state = 11;
                            break;
                        case 16:
                        default:
                            $index -= 2;
                            $s = \substr($s, 0, -2);
                            if ($c !== '' && self::is_valid_name_char($c)) {
                                $error = "invalid char after number $s";
                            }
                            break 3;
                    }
                    $c = @$str[++$index];
                    break;
                default:
                    throw new \ErrorException("BUG");
            }
        }
        if ($oct_error) {
            if ($throw_error) {
                throw new \ErrorException($oct_error);
            }
            return array(array(T_LNUMBER, $oct_s, $oct_error), $oct_index);
        }
        if ($throw_error && $error !== NULL) {
            throw new \ErrorException($error);
        }
        return array(array($is_float ? T_DNUMBER : T_LNUMBER, $s, $error), $index);
    }

    //start with [\'\"\`]
    public static function get_next_quote($str, $index, $throw_error)
    {
        $s = $quote = $str[$index];
        $c = @$str[++$index];
        for (;;) {
            if ($c === "\\") {
                $s .= $c;
                $c = @$str[++$index];
                if ($c === "") {
                    break;
                }
                $s .= $c;
                $c = @$str[++$index];
            } elseif ($c === "\$" && ($quote == "\"" || $quote == "`")) { //not allow $ in double quotes and back quotes
                $s .= "\\$";
                $c = @$str[++$index];
            } elseif ($c === $quote) {
                $s .= $c;
                return array(array(T_CONSTANT_ENCAPSED_STRING, $s, NULL), $index + 1);
            } else {
                $s .= $c;
                $c = @$str[++$index];
            }
        }
        $error = "cannot find matched $quote in string $s";
        if ($throw_error && $error !== NULL) {
            throw new \ErrorException($error);
        }
        return array(array(T_CONSTANT_ENCAPSED_STRING, $s, $error), $index);
    }

    //start with [\#\/]
    public static function get_next_comment($str, $index)
    {
        if (@$str[$index] === "/" && @$str[$index + 1] === "*") {
            $s = $str[$index]; // /
            $s .= $str[++$index]; // *
            $c = $str[++$index];
            for (;;) {
                if ($c === "*" && @$str[$index + 1] === "/") {
                    $s .= $str[++$index]; // *
                    $s .= $str[++$index]; // /
                    $index++;
                    break;
                } elseif ($c === "") {
                    break;
                } else {
                    $s .= $c;
                    $c = @$str[++$index];
                }
            }
            return array(array(T_COMMENT, $s, NULL), $index);
        } elseif (@$str[$index] === "#" || @$str[$index + 1] === "/") {
            $s = "";
            $c = $str[$index];
            for (;;) {
                if ($c === "\r" || $c === "\n" || $c === "") {
                    break;
                } else {
                    $s .= $c;
                    $c = @$str[++$index];
                }
            }
            return array(array(T_COMMENT, $s, NULL), $index);
        } else {
            return array(false, $index);
        }
    }

    //start with [\t\r\n ]
    public static function get_next_whitespace($str, $index)
    {
        static $map = array(" " => 1, "\r" => 1, "\n" => 1, "\t" => 1);
        $s = $c = $str[$index];
        for (;;) {
            if (isset($map[$c])) {
                $s .= $c;
                $c = @$str[++$index];
            } else {
                break;
            }
        }
        return array(array(T_WHITESPACE, $s, NULL), $index);
    }

    public static function get_next_splitter($last, $str, $index, $map)
    {
        $c = @$str[$index];
        if ($c === "") {
            if (@$map[""] === true) {
                return array($last, $index);
            }
            return false;
        }
        if (isset($map[$c])) {
            $r = self::get_next_splitter($last . $c, $str, $index + 1, $map[$c]);
            if ($r) {
                return $r;
            }
        }
        if ($last === "") {
            return false;
        } elseif (@$map[""] === true) {
            return array($last, $index);
        }
        return false;
    }

    private static function generate_splitter_map_set_map($str, &$map)
    {
        if ($str === "") {
            $map[""] = true;
        } else {
            if (!isset($map[$str[0]])) {
                $map[$str[0]] = array();
            }
            self::generate_splitter_map_set_map(\substr($str, 1), $map[$str[0]]);
        }
    }

    public static function generate_splitter_map($splitters)
    {
        $map = array();
        foreach ($splitters as $splitter) {
            self::generate_splitter_map_set_map($splitter, $map);
        }
        //var_dump($map);
        return $map;
    }

    //start with [\$]
    public static function get_next_variable($str, $index)
    {
        $s = @$str[$old_index = $index]; // $
        $c = @$str[++$index];
        if ($c === "" || !self::is_valid_name_start_char($c)) {
            return array(false, $old_index);
        }
        $s .= $c;
        $c = @$str[++$index];
        for (;;) {
            if ($c === "" || !self::is_valid_name_char($c)) {
                break;
            }
            $s .= $c;
            $c = @$str[++$index];
        }
        return array(array(T_VARIABLE, $s, NULL), $index);
    }

    //start with [\\a-zA-Z_\x7f-\xff][
    public static function get_next_string($str, $index)
    {
        $s = "";
        $c = @$str[$index];
        for (;;) {
            if (self::is_valid_name_char($c)) {
                $s .= $c;
                $c = @$str[++$index];
            } else {
                break;
            }
        }
        return array(array(T_STRING, $s, NULL), $index);
    }

    public static function get_next_open_tag($str, $index)
    {
        if (\substr_compare($str, "<?", $index) === 0) {
            return array(false, $index);
        }
        if (\substr_compare($str, "<?=", $index) == 0) {
            return array(array(T_OPEN_TAG, "<?=", NULL), $index + 3);
        } elseif (\substr_compare($str, "<?php", $index, null, true)) {
            return array(array(T_OPEN_TAG, \substr($str, $index, 5), NULL), $index + 5);
        } else {
            return array(array(T_OPEN_TAG, "<?", NULL), $index + 2);
        }
    }


    public static function get_next_inline_html($str, $index, $open_tag)
    {
        $s = "";
        $open_tag_length = \strlen($open_tag);
        $c = @$str[$index];
        for (;;) {
            if ($c === $open_tag[0] && ($open_tag_length == 1 || \substr($str, $index, $open_tag_length) === $open_tag)) {
                return array(array(T_INLINE_HTML, $s, NULL), $index);
            } elseif ($c === "") {
                break;
            } else {
                $s .= $c;
                $c = @$str[++$index];
            }
        }
        return array(array(T_INLINE_HTML, $s, NULL), $index);
    }

    public static function post_process_mark_keywords($tokens, $convert_keywords_case)
    {
        //return $tokens;
        $last_token = NULL;
        foreach ($tokens as &$token) {
            if (\is_array($token)) {
                if ($token[0] == T_STRING) {
                    $keyword = self::is_keyword($token[1]);
                    if ($keyword && $last_token != "::" && $last_token != "->") {
                        $token = $convert_keywords_case ? $keyword : $token[1];
                    }
                } elseif ($token[0] == T_OPEN_TAG) {
                    $token[1] = \strtolower($token[1]); //<?php
                }
            }
            if (!self::is_token_blank($token)) {
                $last_token = $token;
            }
        }
        return $tokens;
    }

    public static function is_token_blank($token)
    {
        static $map = array(" " => 1, "\r" => 1, "\n" => 1, "\t" => 1, "\r\n" => 1);
        if (\is_array($token)) {
            return $token[0] == T_COMMENT || $token[0] == T_WHITESPACE;
        } else {
            for ($i = strlen($token); $i-- > 0;) {
                if (!isset($map[$token[$i]])) {
                    return false;
                }
            }
            return true;
        }
    }

    public static function post_process_strip_whitespace($tokens)
    {
        $map = array("::" => 1, "->" => 1, ":" => 1, "[" => 1, "]" => 1, "{" => 1, "}" => 1, "(" => 1, ")" => 1, ";" => 1, "," => 1);
        $ss = array();
        $has_whitespace = false;
        $strip_next_whitespace = true;
        foreach ($tokens as $token) {
            if (self::is_token_blank($token)) {
                if (!$strip_next_whitespace) {
                    $has_whitespace = true;
                }
            } else {
                $strip_next_whitespace = !\is_array($token) && isset($map[$token]);
                if ($strip_next_whitespace) {
                    $has_whitespace = false;
                } else {
                    if ($has_whitespace) {
                        $has_whitespace = false;
                        $ss[] = " ";
                    }
                }
                $ss[] = $token;
            }
        }
        //end whitespace is ignored
        return $ss;
    }

    public static function post_process_mark_variable_string($tokens)
    {
        $tokens = static::post_process_strip_whitespace($tokens);
        $tokens = static::post_process_mark_keywords($tokens, true);
        $tokens = static::post_process_merge_backslash($tokens);

        $last_token = NULL;
        for ($index = 0; $index < \count($tokens); $index++) {
            $token = $tokens[$index];
            if (\is_array($token)) {
                if ($token[0] === T_VARIABLE) {
                    if ($last_token === "new" || @$tokens[$index + 1] === "::") {
                        //$token[0]=self::T_VARIABLE_CLASS;
                    } elseif (@$tokens[$index + 1] === "(") {
                        $token[0] = self::T_VARIABLE_FUNCTION;
                    } elseif ($last_token === "::") {
                        $token[0] = self::T_VARIABLE_STATIC_PROPERTY;
                    } elseif ($last_token === "->") { //成员变量

                    }
                } elseif ($token[0] === T_STRING) {
                    if ($last_token === "new" || $last_token === "instanceof") {
                        $token[0] = self::T_STRING_CLASS_NAME;
                    } elseif ($last_token === "::") {
                        if (@$tokens[$index + 1] === "(") {
                            $token[0] = self::T_STRING_CLASS_STATIC_METHOD;
                        } else {
                            $token[0] = self::T_STRING_CLASS_CONSTANT;
                        }
                    } elseif ($last_token === "->") {
                        if (@$tokens[$index + 1] === "(") {
                            $token[0] = self::T_STRING_CLASS_METHOD;
                        } else {
                            $token[0] = self::T_STRING_CLASS_PROPERTY;
                        }
                    } elseif (@$tokens[$index + 1] === "(") {
                        $token[0] = self::T_STRING_FUNCTION;
                    } elseif (\is_array($tokens[$index + 1]) && $tokens[$index + 1][0] === T_VARIABLE) {
                        $token[0] = self::T_STRING_CLASS_NAME;
                    }
                }
                $tokens[$index] = $token;
            }
            if (!self::is_token_blank($token)) {
                $last_token = $token;
            }
        }
        return $tokens;
    }

    public static function post_process_merge_backslash($tokens)
    {
        $ss = array();
        for ($index = 0; $index < \count($tokens); $index++) {
            $token = $tokens[$index];
            $token_str = $token == "\\" ? $token : false;
            RESTART: if ($token_str) {
                for ($j = $index + 1; $j < \count($tokens); $j++) {
                    $next_token = $tokens[$j];
                    if (self::is_token_blank($next_token)) {
                        continue;
                    } elseif (\is_array($next_token) && $next_token[0] == T_STRING) {
                        $token = array(T_STRING, $token_str . $next_token[1]);
                        $index = $j;
                        $token_str = false;
                        goto RESTART;
                    } else {
                        break;
                    }
                }
            } elseif (\is_array($token) && $token[0] == T_STRING) {
                for ($j = $index + 1; $j < \count($tokens); $j++) {
                    $next_token = $tokens[$j];
                    if (self::is_token_blank($next_token)) {
                        continue;
                    } elseif ($next_token == "\\") {
                        $token = array(T_STRING, $token[1] . $next_token);
                        $index = $j;
                        $token_str = $token[1];
                        goto RESTART;
                    } else {
                        break;
                    }
                }
            }
            $ss[] = $token;
        }
        return $ss;
    }

    protected $splitters_map;
    protected function setup_splitters($addition_splitters)
    {
        $splitters = array(
            "=>", //foreach
            "**", "++", "--", ">>", "<<",
            ">=", "<=", "==", "!=", "===", "!==", "<>", "<=>",
            "??", "?:",
            "+=", "-=", "*=", "**=", "/=", ".=", "%=", "&=", "|=", "^=", "<<=", ">>=",
            "\r\n", "&&", "||", "::", "->",
            "[", "]", "{", "}",
            ">", "<", "!", "^", "&", "|", "=", "(", ")", "\t", "\r", "\n", " ", "@", ":", "+", "-", "*", "/", "%", ";", ",", ".", ":", "?", "#", "~",
            "\"", "'", "`",
            "\\",
            //"{{","}}",
            /* ,"<?=","<?","<?php","?>" */
        );
        $this->splitters_map = self::generate_splitter_map(array_merge($splitters, $addition_splitters));
    }

    protected function get_next_token($str, $index, $throw_error)
    {
        static $map = array(
            "`" => 1, "'" => 1, "\"" => 1,
            "#" => 2, "/" => 2,
            "\$" => 3,
            "0" => 4, "1" => 4, "2" => 4, "3" => 4, "4" => 4, "5" => 4, "6" => 4, "7" => 4, "8" => 4, "9" => 4, "." => 4,
            " " => 5, "\t" => 5, "\r" => 5, "\n" => 5,
            "" => 6,
        );
        $c = @$str[$index];
        if (!isset($map[$c])) {
            DEF: if (self::is_valid_name_start_char($c)) {
                return self::get_next_string($str, $index, $throw_error);
            } else {
                if (($r = self::get_next_splitter("", $str, $index, $this->splitters_map))) {
                    return $r;
                } else {
                    if ($throw_error) {
                        throw new \ErrorException("unkown char $c (" . ord($c) . ")");
                    }
                }
            }
        }
        switch ($map[$c]) {
            case 1:
                list($token, $index) = self::get_next_quote($str, $index, $throw_error);
                break;
            case 2:
                list($token, $index) = self::get_next_comment($str, $index, $throw_error);
                break;
            case 3:
                list($token, $index) = self::get_next_variable($str, $index, $throw_error);
                break;
            case 4:
                list($token, $index) = self::get_next_number($str, $index, $throw_error);
                break;
            case 5:
                list($token, $index) = self::get_next_whitespace($str, $index, $throw_error);
                break;
            case 6:
                return array(false, $index); //END
            default:
                throw new \ErrorException("BUG");
        }
        if ($token === false) {
            goto DEF;
        }
        return array($token, $index);
    }

    //override as you like
    public function token_get_all($str, $mode, $throw_error = false, $post_process_mark_variable_string = false)
    {
        if ($str === "") {
            return array();
        }
        $tokens = array();
        $index = 0;
        if ($mode == "render") {
            list($token, $index) = self::get_next_inline_html($str, $index, "<?");
            if ($token[1] !== "") {
                $tokens[] = $token;
            }
            list($token, $index) = self::get_next_open_tag($str, $index);
            if ($token !== false) {
                $tokens[] = $token;
            }
        } elseif ($mode == "eval") {
        } else {
            throw new \ErrorException("param mode should be render/eval");
        }
        for (;;) {
            list($token, $index) = $this->get_next_token($str, $index, $throw_error);
            if ($token === false) {
                break;
            } elseif ($token == "?>") {
                $tokens[] = array(T_CLOSE_TAG, $token, NULL);
                list($token, $index) = self::get_next_inline_html($str, $index, "<?");
                if ($token[1] !== "") {
                    $tokens[] = $token;
                }
                list($token, $index) = self::get_next_open_tag($str, $index);
                if ($token !== false) {
                    $tokens[] = $token;
                } else {
                    break;
                }
            } else {
                $tokens[] = $token;
            }
        }
        if ($post_process_mark_variable_string) {
            $tokens = self::post_process_mark_variable_string($tokens);
        }
        return $tokens;
    }
}
