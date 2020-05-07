<?php
use FoolTemplate\FoolTemplate;
use FoolTemplate\PhpTokenizer;
use FoolTemplate\FoolTemplateTokenizer;
require_once __DIR__."/../src/FoolTemplate.php";
require_once __DIR__."/../src/PhpTokenizer.php";
require_once __DIR__."/../src/FoolTemplateTokenizer.php";


$code11="echo 11;";
$code12="echo 11.;";
$code13="echo .11;";
$code14="echo 1.111E2;";
$code15="echo 0111;";
$code16="echo 0888;";

$code21="echo '11';";
$code22="echo '1\\'1';";
$code23="echo \"1\\\"1\";";

$code31="/* eee */ echo 1;";
$code32="# eee  echo 1;";

$code41="echo \$f;";
$code42="echo 1+-1;";

$tokenizer=new PhpTokenizer();
$tokens=$tokenizer->token_get_all($code42,"eval",true);
PhpTokenizer::dump_tokens($tokens);