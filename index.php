<?php

require 'vendor/autoload.php';
require_once 'myStuff.php';

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
    'debug' => true,
));
$twig->addExtension(new Twig_Extension_Debug());

// ToDo:
// Need to get year and month from URL
$run_year = '2018';
$immutable = CarbonImmutable::createFromDate($run_year, 1, 1);
$period = CarbonPeriod::create($immutable->firstOfYear(), $immutable);

$qSelect = $pdo->prepare("SELECT * FROM `laeufe` WHERE FROM_UNIXTIME(`lauf_datum`, '%Y') = :run_year");

try {
    $qSelect->execute(array('run_year' => $run_year));
} catch (PDOException $e) {
    // echo $e->getMessage();
}

// echo "Zeilen: " . $qSelect->rowCount() . "<br />";
foreach($qSelect->fetchAll() AS $runs) {

    $run_day = Carbon::createFromTimestamp($runs['lauf_datum'])->dayOfYear;
    $dailyRuns[$run_day] = array(
        'run_id' => $runs['lauf_id'],
        'run_date' => $runs['lauf_datum'],
        'run_distance' => $runs['lauf_laenge'],
        'run_duration' => $runs['lauf_dauer'],
        'run_file' => $runs['lauf_fitfile'],
    );

}


$totalKM = 0;
$records['streak'] = 0;
$records['noRunning'] = 0;

for($i = $immutable->firstOfYear(); $i <= Carbon::today(); $i = $i->addDay(1)) {

    if (isset($dailyRuns[$i->dayOfYear])) {
        $totalKM += $dailyRuns[$i->dayOfYear]['run_distance'];
        if (isset($noRunningCounter)) {
            if($noRunningCounter > $records['noRunning']) {
                $records['noRunning'] = $noRunningCounter;
            }
            unset($noRunningCounter);
        }

        if(!isset($streakCounter)) {
            $streakCounter = 1;
        } else {
            $streakCounter++;
        }

        // echo $i->dayOfYear . " ";
        // echo "Hier: " . $dailyRuns[$i->dayOfYear]['run_distance'] . " Total: " .  $totalKM ."km <b>Streak:</b> " . $streakCounter . "<br />";

        // Small Idea
        // Period of Streak / No Running!

    } else {
        if(isset($streakCounter)) {
            if($streakCounter > $records['streak']) {
                $records['streak'] = $streakCounter;
            }
            unset($streakCounter);
        }

        if(!isset($noRunningCounter)) {
            $noRunningCounter = 1;
        } else {
            $noRunningCounter++;
        }

        // echo $i->dayOfYear . " no Run for " . $noRunningCounter . " day(s).<br />";
    }
}

// echo "Streak Record: " . $records['streak'] . " Days<br />";
// echo "NoRunning Record: " . $records['noRunning'] . " Days<br />";
// echo "Ende!";

echo $twig->render('runtable.twig', array());