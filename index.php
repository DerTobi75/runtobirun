<?php

// KMToGoal = how many km to run to reach the yearly goal
// pTotalKMToGoal = p means perfect, how many is milage I had to run, meeting my goal
// diffToGoal = shows the difference between total milage run versus total milage that should be run

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
        'run_date' => date('d.m.y', $runs['lauf_datum']),
        'run_distance' => $runs['lauf_laenge'],
        'run_duration' => $runs['lauf_dauer'],
        'run_file' => $runs['lauf_fitfile'],
    );

}


$totalKM = 0;
$totalGoalKM = 0;
$diffToGoal = 0;
// ToDo: get yearlyGoal from the database or set it elsewhere,
// ToDo: but do not let it be static!
$yearlyGoal = 2018;
$dailyGoalKM = $yearlyGoal / 365;
$records['streak'] = 0;
$records['noRunning'] = 0;


for($i = $immutable->firstOfYear(); $i <= Carbon::today(); $i = $i->addDay(1)) {
    $totalGoalKM += round($dailyGoalKM, 2);

    if(!isset($avgDailyToGoKM) AND $i->eq($i->firstOfYear())) {
        $avgDailyToGoKM = $yearlyGoal / $i->daysInYear;
    }

    if (isset($dailyRuns[$i->dayOfYear])) {
        $totalKM += $dailyRuns[$i->dayOfYear]['run_distance'];
        $KMtoGo = $yearlyGoal - $totalKM;
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

        // Small Idea
        // Period of Streak / No Running!

    } else {
        // we need a date for the table
        $dailyRuns[$i->dayOfYear]['run_date'] = $i->format('d.m.y');
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
    }

    $avgDailyRunKM = $totalKM / $i->dayOfYear;
    // do not know if this is the best place
    // data the array needs, that can be generated
    // in if OR else, ...

    $dailyRuns[$i->dayOfYear]['run_day'] = $i->dayOfYear;
    $dailyRuns[$i->dayOfYear]['totalKM'] = $totalKM;
    $dailyRuns[$i->dayOfYear]['totalGoalKM'] = $totalGoalKM;
    $dailyRuns[$i->dayOfYear]['KMtoGo'] = $KMtoGo;
    // $dailyRuns[$i->dayOfYear]['diffToGoal'] = $dailyRuns[$i->dayOfYear]['totalGoalKM'] - $dailyRuns[$i->dayOfYear]['totalKM'];
    $dailyRuns[$i->dayOfYear]['diffToGoal'] = $dailyRuns[$i->dayOfYear]['totalKM'] - $dailyRuns[$i->dayOfYear]['totalGoalKM'];
    $dailyRuns[$i->dayOfYear]['avgDailyToGoKM'] = round($avgDailyToGoKM, 2);
    $dailyRuns[$i->dayOfYear]['avgDailyRunKM'] = round($avgDailyRunKM, 2);

    // data we need in the next round!
    $avgDailyToGoKM = $KMtoGo / $i->diffInDays($i->lastOfYear());


}

// echo "Streak Record: " . $records['streak'] . " Days<br />";
// echo "NoRunning Record: " . $records['noRunning'] . " Days<br />";
// echo "Ende!";


// sort the dailyRun Array
ksort($dailyRuns);
echo $twig->render('runtable.twig', array('myRuns' => $dailyRuns));