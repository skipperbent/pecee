<?php
require_once 'Html2.php';

$html = new \Pecee\UI\Html\Html2();

$input = new \Pecee\UI\Html\Html2('input');
$input->attr('type', 'text');
$input->attr('value', 'hej');
$input->addClass('hello');
$input->attr('checked', '');


$div = new \Pecee\UI\Html\Html2('div');
$div->attr('fisse', 'nej');
$div->attr('id', 'tester');
$div->append($input);
$div->addClass('mongol');
$div->attr('data-id', '22');

$html->append($div);

$nodes = $html->find('#tester[data-id="22"] input[type="text"]');

var_dump($nodes);