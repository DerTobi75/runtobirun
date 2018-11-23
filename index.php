<?php

require 'vendor/autoload.php';
require_once 'myStuff.php';

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

// ToDo:
// Need to get year and month from URL
$run_year = '2018';
$immutable = CarbonImmutable::createFromDate($run_year, 1, 1);
$period = CarbonPeriod::create($immutable->firstOfYear(), $immutable);

$qSelect = $pdo->prepare("SELECT * FROM `laeufe` WHERE FROM_UNIXTIME(`lauf_datum`, '%Y') = :run_year");

try {
    $qSelect->execute(array('run_year' => $run_year));
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo "Zeilen: " . $qSelect->rowCount() . "<br />";
foreach($qSelect->fetchAll() AS $runs) {

    $run_day = Carbon::createFromTimestamp($runs['lauf_datum'])->dayOfYear;
    $dailyRuns[$run_day] = array(
        'run_id' => $runs['lauf_id'],
        'run_date' => $runs['lauf_datum'],
        'run_distance' => $runs['lauf_laenge'],
        'run_duration' => $runs['lauf_dauer'],
        'run_file' => $runs['lauf_fitfile'],
    );

    echo $runs['lauf_id'] . " ";

    echo $run_day . "<br />";
}


for($i = $immutable->firstOfYear(); $i <= Carbon::today(); $i = $i->addDay(1)) {

    if (isset($dailyRuns[$i->dayOfYear])) {
        echo "Hier: " . $dailyRuns[$i->dayOfYear]['run_id'] . "<br />";
    }
}

echo "Ende!";