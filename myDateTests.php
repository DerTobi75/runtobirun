<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonImmutable;

$immutable = CarbonImmutable::now();
$mutable = Carbon::now();

echo "<pre>";
    var_dump($immutable);
echo "</pre>";

$jahr = $immutable->year;

$firstDay = $immutable->firstOfYear();

echo "Das aktuelle Jahr: " . $jahr . "<br />";

echo "Erster Tag des Jahres: " . $firstDay . "<br />";

echo $firstDay->addDay(1) . "<br />";



for($i = $immutable->firstOfYear(); $i <= $immutable->lastOfYear(); $i = $i->addDay(1)) {

    if($i == $i->firstOfMonth()) {
        if($i->month > 1) {
            echo "<br />";
        }
        echo $i->monthName . "<br />" . $i . "<br />";
    } else {
        echo $i . "<br />";
    }
}

$tester = $immutable->firstOfYear();
echo $tester->addDay(3) . "<br /><br />";

if($immutable->firstOfYear() > $tester->addDay(7)) {
    echo "Klappt, ...<br />";
} else {
    echo "Klappt nicht!<br />";
}



echo $mutable->firstOfYear() . "<br />";
echo $mutable->lastOfYear() . "<br />";

echo "<br /><br />Ende!";