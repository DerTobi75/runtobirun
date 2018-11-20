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



echo "Current Week Of Year: " . $immutable->weekOfYear . "<br />";
echo $immutable->week(44)->startOfWeek() . "<br />";

for($i = $immutable->firstOfYear(); $i <= $immutable->lastOfYear(); $i = $i->addDay(1)) {

    if($i->eq($i->firstOfMonth())) {
        if($i->month > 1) {
            echo "<br />";
        }
        echo $i->monthName . "<br />";
        if($i->eq($i->isWeekend())) {
            echo "<b>" . $i->format('d.m.Y') . " (" . $i->dayOfYear . " / " . $i->weekOfYear . ")</b><br />";
        } else {
            echo $i->format('d.m.Y') . " (" . $i->dayOfYear . " / " . $i->weekOfYear . ")<br />";
        }

    } else {
        if($i->eq($i->isWeekend())) {
            echo "<b>" . $i->dayOfWeekIso . " - " . $i->format('d.m.Y') . " (" . $i->dayOfYear . " / " . $i->weekOfYear . ")</b><br />";
        } else {
            echo $i->format('d.m.Y') . " (" . $i->dayOfYear . " / " . $i->weekOfYear . ")<br />";
        }
    }


}

$tester = $immutable->firstOfYear();
echo $tester->addDay(3) . "<br /><br />";

echo $mutable->firstOfYear() . "<br />";
echo $mutable->lastOfYear() . "<br />";

echo "<br /><br />Ende!";