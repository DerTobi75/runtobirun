<?php

require 'vendor/autoload.php';

use Symfony\Component\Stopwatch\Stopwatch;

$stopwatch = new Stopwatch();
// starts event named 'eventName'
$stopwatch->start('eventName');

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$immutable = CarbonImmutable::now();
$mutable = Carbon::now();



echo "Tage bis Ende des Jahres: " . $immutable->diffInDays($immutable->lastOfYear()) . "<br />";
echo "Tage bis Ende des Monats: " . $immutable->diffInDays($immutable->lastOfMonth()) . "<br />";
echo "Tage vom 1.11. bis Ende des Jahres: " . $immutable->firstOfMonth()->diffInDays($immutable->lastOfYear()) . "<br />";


$yearsInStats = array('2018', '2019');
if(!empty($request->query->get('jahr')) AND in_array($request->query->get('jahr'), $yearsInStats)) {
    $jahr = $request->query->get('jahr');
} else {
    $jahr = $immutable->year;
}

if(!empty($request->query->get('monat')) AND ($request->query->get('monat') > 0 AND $request->query->get('monat') < 13)) {
    $monat = $request->query->get('monat');
} else {
    $monat = $immutable->month;
}
$stopwatch->lap("eventName");

echo "Jahr: " . $jahr . "<br />";
echo "Monat: " . $monat . "<br />";

/*echo "<pre>";
    var_dump($immutable);
echo "</pre>";*/

$jahr = $immutable->year;

$firstDay = $immutable->firstOfYear();

echo "Das aktuelle Jahr: " . $jahr . "<br />";
echo "Erster Tag des Jahres: " . $firstDay . "<br />";

echo $firstDay->addDay(1) . "<br />";

$yearDays = $immutable->daysInYear;
$today = $immutable->dayOfYear;

$restDays = $yearDays - $today;
echo $restDays . "<br />";
echo $yearDays . " " . $today . "<br />";


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

sleep(10);

echo $mutable->firstOfYear() . "<br />";
echo $mutable->lastOfYear() . "<br />";

$event = $stopwatch->stop('eventName');


$tester = Carbon::createFromTimestampMs($event->getOrigin());
echo "Urghs: " . $tester . "<br />";
echo "<br /><br />Ende! " . $event->getOrigin() . " " . $event->getDuration() . " " . $event->getStartTime() . " " . $event->getEndTime();