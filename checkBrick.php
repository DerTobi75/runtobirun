<?php

require_once 'vendor/autoload.php';
$fileName = '3949837230.fit';


$options = ['units' => 'statute', 'pace' => true];

$pFFA = new adriangibbons\phpFITFileAnalysis('fit/done/' . $fileName, $options);

$pFFA->data_mesgs['session']['total_distance'] . "<br />";

if(is_array($output['datum'] = $pFFA->data_mesgs['session']['start_time'])) {
    echo $output['datum'] = $pFFA->data_mesgs['session']['start_time'] . "<br />";
    echo $output['datum'] = $pFFA->data_mesgs['session']['total_distance'] . "<br />";
    echo $output['datum'] = $pFFA->data_mesgs['session']['total_timer_time'] . "<br />";
}


echo "<pre>";
print_r($pFFA);
echo "</pre>";