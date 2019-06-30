<?php

require_once 'vendor/autoload.php';
$fileName = '3794900499.fit';

$pFFA = new adriangibbons\phpFITFileAnalysis('fit/new/' . $fileName);

if(is_array($output['datum'] = $pFFA->data_mesgs['session']['start_time'])) {
    echo $output['datum'] = $pFFA->data_mesgs['session']['start_time'][2] . "<br />";
    echo $output['datum'] = $pFFA->data_mesgs['session']['total_distance'][2] . "<br />";
    echo $output['datum'] = $pFFA->data_mesgs['session']['total_timer_time'][2] . "<br />";
}


echo "<pre>";
print_r($pFFA);
echo "</pre>";