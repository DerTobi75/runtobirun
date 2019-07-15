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


$pageTitle = "Meine Läufe, ...";

// ToDo:
// Need to get year and month from URL
$run_year = '2019';
$today = Carbon::today();
$immutable = CarbonImmutable::createFromDate($run_year, 1, 1);
$period = CarbonPeriod::create($immutable->firstOfYear(), $immutable);

if ($immutable->isCurrentYear()) {
    $navBarMonth = $today->month;
} else {
    // ToDo: Built a better exit method when runYear greater current year!
    if ($run_year < $today->year) {
        $navBarMonth = 12;
    } else {
        die('You cannot run in the Future!');
    }
}

// ToDo: This is shit!
$myMonth = array('1' => 'Januar', '2' => 'Februar', '3' => 'März', '4' => 'April', '5' => 'Mai', '6' => 'Juni', '7' => 'Juli', '8' => 'August', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember', );

for($cMonth = 1; $cMonth <= $navBarMonth; $cMonth++) {
    $navBarItems[$cMonth]['monthInt'] = $cMonth;
    $navBarItems[$cMonth]['monthName'] = $myMonth[$cMonth];
}

$qSelect = $pdo->prepare("SELECT * FROM `laeufe` WHERE FROM_UNIXTIME(`lauf_datum`, '%Y') = :run_year");

try {
    $qSelect->execute(array('run_year' => $run_year));
} catch (PDOException $e) {
    echo $e->getMessage();
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
$yearlyGoal = 2019;

// ToDo: Check if this makes sense!!
$KMtoGo = $yearlyGoal;

$dailyGoalKM = $yearlyGoal / 365;
$records['streak'] = 0;
$records['noRunning'] = 0;
$monthlyStats = array('runCount' => 0, 'runDistance' => 0);

for($i = $immutable->firstOfYear(); $i <= Carbon::today(); $i = $i->addDay(1)) {
    $totalGoalKM += round($dailyGoalKM, 2);

    if(!isset($avgDailyToGoKM) AND $i->eq($i->firstOfYear())) {
        $avgDailyToGoKM = $yearlyGoal / $i->daysInYear;
    }

    if (isset($dailyRuns[$i->dayOfYear])) {
        $totalKM += $dailyRuns[$i->dayOfYear]['run_distance'];
        $monthlyStats['runDistance'] += $dailyRuns[$i->dayOfYear]['run_distance'];
        $monthlyStats['runCount']++;
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

    $navBarItems[$i->month]['stats'] = $monthlyStats;

    if($i->isLastOfMonth()) {
        $dailyRuns[$i->dayOfYear]['lastOfMonth'] = $i->month;
        //$navBarItems[$i->month]['stats'] = $monthlyStats;
        $monthlyStats = array('runCount' => 0, 'runDistance' => 0);
    }

    if($i->eq($i->firstOfMonth())) {
        $dailyRuns[$i->dayOfYear]['firstOfMonth'] = $i->month;
    }

    // data we need in the next round!
    $avgDailyToGoKM = $KMtoGo / $i->diffInDays($i->lastOfYear());

}

// sort the dailyRun Array
ksort($dailyRuns);

$qSelectWeekly = $pdo->prepare("SELECT WEEK(FROM_UNIXTIME(`lauf_datum`),1) AS `runWeek`, sum(`lauf_laenge`) AS `weekDistance`, count(`lauf_id`) AS `weekCount` FROM `laeufe` WHERE YEAR(FROM_UNIXTIME(`lauf_datum`)) = :run_year GROUP BY WEEK(FROM_UNIXTIME(`lauf_datum`),1)");

try {
    $qSelectWeekly->execute(array('run_year' => $run_year));
} catch (PDOException $e) {
    echo $e->getMessage();
}

// Think about this! Maybe do something else!
// ToDo: This might get a completly revamp!

$weeks = $qSelectWeekly->fetchAll();

$myCounter = 0;
$runWeeks = array();
$weekKMTotal = 0;
$weeklyKMTogo = $yearlyGoal;
// This is experimantal
//$weeksInYear = $immutable->weeksInYear;
$weekYearlyGoal = 0;
$weeklyGoal = $yearlyGoal / $immutable->weeksInYear;
$weeklyKMToRun = $weeklyGoal;
for($i = $immutable->firstOfYear()->week; $i <= Carbon::today()->week; $i++) {

    // Think about this shit again
    $weekYearlyGoal += $weeklyGoal;
    $runWeeks[$i]['weekGoalTotal'] = $weekYearlyGoal;
    // $runWeeks[$i]['weekGoalTotalToGo'] = $weekYearlyGoal - $runWeeks[$i]['weekGoal'];
    // $weekYearlyGoal = $runWeeks[$i]['weekGoalTotalToGo'];

    if(isset($weeks[$myCounter]['runWeek']) AND $weeks[$myCounter]['runWeek'] == $i) {
        $weekKMTotal += $weeks[$myCounter]['weekDistance'];
        $weeklyKMTogo -= $weeks[$myCounter]['weekDistance'];
        $runWeeks[$i]['weekNr'] = $i;
        $runWeeks[$i]['weekDistance'] = $weeks[$myCounter]['weekDistance'];
        $runWeeks[$i]['weekCount'] = $weeks[$myCounter]['weekCount'];
        $runWeeks[$i]['weeksTotal'] = $weekKMTotal;
        $runWeeks[$i]['weeksKMToGo'] = $weeklyKMTogo;
        $runWeeks[$i]['weeksKMToRun'] = $weeklyKMToRun;
        $myCounter++;
    } else {
        $runWeeks[$i]['weekNr'] = $i;
        $runWeeks[$i]['weekDistance'] = 0;
        $runWeeks[$i]['weekCount'] = 0;
        $runWeeks[$i]['weeksTotal'] = $weekKMTotal;
        $runWeeks[$i]['weeksKMToGo'] = $weeklyKMTogo;
        $runWeeks[$i]['weeksKMToRun'] = round($weeklyKMToRun, 2);
    }


    if($runWeeks[$i]['weekDistance'] > 0) {
        $weekPercent = ($runWeeks[$i]['weekDistance'] * 100) / $runWeeks[$i]['weeksKMToRun'];
    } else {
        $weekPercent = 0;
    }

    $runWeeks[$i]['weekPercent'] = round($weekPercent);

    if($runWeeks[$i]['weekPercent'] >= 100) {
        $runWeeks[$i]['progressClass'] = "bg-success";
    } elseif ($runWeeks[$i]['weekPercent'] > 49) {
        $runWeeks[$i]['progressClass'] = "bg-warning";
    } else {
        $runWeeks[$i]['progressClass'] = "bg-danger";
    }

    $weeksToGo = $immutable->weeksInYear - $i;
    $weeklyKMToRun = $weeklyKMTogo / $weeksToGo;
}


$qSelectRunsInMonth = $pdo->prepare("SELECT * FROM `laeufe` WHERE month(from_unixtime(lauf_datum)) = :month AND year(from_unixtime(lauf_datum)) = :year");

$rMonth = $today->month;
$pMonth = $today->subMonth()->month;
$rYear = $today->year;
$pYear = $today->subMonth()->year;

$qSelectRunsInMonth->execute(array('month' => $rMonth, 'year' => $rYear));
foreach ($qSelectRunsInMonth->fetchAll() AS $rMonthData) {
    $rMonDay = Carbon::createFromTimestamp($rMonthData['lauf_datum'])->day;

    if(!isset($rMonSumKM)) {
        $rMonSumKM = $rMonthData['lauf_laenge'];
    } else {
        $rMonSumKM += $rMonthData['lauf_laenge'];
    }

    $rMonData[] = array('day' => $rMonDay,
        'kmSum' => $rMonSumKM);
}

$qSelectRunsInMonth->execute(array('month' => $pMonth, 'year' => $pYear));
foreach ($qSelectRunsInMonth->fetchAll() AS $pMonthData) {
    $pMonDay = Carbon::createFromTimestamp($pMonthData['lauf_datum'])->day;

    if(!isset($pMonSumKM)) {
        $pMonSumKM = $pMonthData['lauf_laenge'];
    } else {
        $pMonSumKM += $pMonthData['lauf_laenge'];
    }

    $pMonData[] = array('day' => $pMonDay,
        'kmSum' => $pMonSumKM);
}


$qSelectMonthlyStats = $pdo->prepare('SELECT month(FROM_UNIXTIME(lauf_datum)) AS monat, sum(lauf_laenge) AS laenge, count(FROM_UNIXTIME(lauf_datum)) AS anzahl FROM `laeufe` WHERE year(FROM_UNIXTIME(lauf_datum)) = :year GROUP By month(FROM_UNIXTIME(lauf_datum))');
$qSelectMonthlyStats->execute(array('year' => $run_year));


$countTotal = 0;
$countLength = 0;
$countMontlyGoal = 0;

$monthlyGoal = $yearlyGoal / 12;
foreach ($qSelectMonthlyStats->fetchAll() AS $mStats) {

    $countTotal += $mStats['anzahl'];
    $countLength += round($mStats['laenge'], 2);
    $countMonthlyGoal += $monthlyGoal;
    $monthlyToGo = $yearlyGoal - $countLength;

    if($mStats['monat'] == 1) {
        $monthlyToGoReal = $monthlyGoal;
    } else {
        $monthlyToGoReal = ($yearlyGoal - $countLength) / (12 - $mStats['monat']);
    }

    $mBehind = $countLength - $countMonthlyGoal;

    $monthlyGoalDiff = $mStats['laenge'] - $monthlyToGoReal;

    if($mStats['laenge'] > 0) {
        $monthlyPercent = ($mStats['laenge'] * 100) / $monthlyToGoReal;
    } else {
        $monthlyPercent = 0;
    }

    if($monthlyPercent >= 100) {
        $progressClass = "bg-success";
    } elseif ($monthlyPercent > 49) {
        $progressClass = "bg-warning";
    } else {
        $progressClass = "bg-danger";
    }


    $monthlyRuns[$mStats['monat']] = array('month' => $mStats['monat'],
                                            'length' => round($mStats['laenge'], 2),
                                            'count' => $mStats['anzahl'],
                                            'countTotal' => $countTotal,
                                            'countLength' => round($countLength, 2),
                                            'countMonthlyGoal' => round($countMonthlyGoal, 2),
                                            'monthlyToGo' => round($monthlyToGo, 2),
                                            'monthlyToGoReal' => $monthlyToGoReal,
                                            'monthlyBehind' => round($mBehind, 2),
                                            'monthlyGoalDiff' => round($monthlyGoalDiff, 2),
                                            'monthlyPercent' => $monthlyPercent,
                                            'progressClass' => $progressClass);
}




echo $twig->render('runtable.twig', array('myRuns' => $dailyRuns,
                                                'navBarItems' => $navBarItems,
                                                'runWeeks' => $runWeeks,
                                                'runMonths' => $monthlyRuns,
                                                'rMonth' => $rMonth,
                                                'pMonth' => $pMonth,
                                                'rYear' => $rYear,
                                                'pYear' => $pYear,
                                                'rMonData' => $rMonData,
                                                'pMonData' => $pMonData,
                                                'pageTitle' => $pageTitle));
