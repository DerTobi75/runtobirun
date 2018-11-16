<?php

## ToDo: Simple Logfile Integration
## ToDo: Better Error Handling on Upload and Process


require_once 'vendor/autoload.php';
require_once 'myStuff.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

$fs = new Filesystem();

$request = Request::createFromGlobals();

$output = array('uploaded' => false);
// get the file from the request object
$file = $request->files->get('file');
$fileName = $file->getClientOriginalName();

// set your uploads directory
$uploadDir = "fit/new";

if(!$fs->exists($uploadDir)) {
    $fs->mkdir($uploadDir);
}

if ($file->move($uploadDir, $fileName)) {
    $output['uploaded'] = 'OK';
    $output['fileName'] = $fileName;
}

// This is just a fast workaound, need to add if Statement
if($fs->exists($uploadDir . "/" . $fileName)) {

    // Build a better PDO SQL Query
    $sql = "SELECT lauf_id FROM laeufe WHERE lauf_fitfile = '" . $fileName . "'";
    $checkfile = $pdo->query($sql);

    if ($checkfile->rowCount() == 0) {

        $qInsertLauf = $pdo->prepare("INSERT INTO `laeufe` (`lauf_name`, `lauf_datum`, `lauf_laenge`, `lauf_dauer`, `lauf_fitfile`) 
                                        VALUES (:lauf, :datum, :laenge, :dauer, :filename)");

        // Let's think, which one is better!
        //$pFFA = new adriangibbons\phpFITFileAnalysis($fitfile->getRealPath());
        $pFFA = new adriangibbons\phpFITFileAnalysis('fit/new/' . $fileName);

        // Check if you can use an array or variables, you nearly use the same data above for the DB Query

        $output['datum'] = $pFFA->data_mesgs['session']['start_time'];
        $output['laenge'] = $pFFA->data_mesgs['session']['total_distance'];
        $output['dauer'] = $pFFA->data_mesgs['session']['total_timer_time'];
        $output['filename'] = $fileName;

        $inDB = [
            'lauf' => 'Laufname',
            'datum' => $output['datum'],
            'laenge' => $output['laenge'],
            'dauer' => $output['dauer'],
            'filename' => $fileName,
        ];

        // Make Date Output nice
        $output['datum'] = date('d.m.Y', $output['datum']);

        /*echo "<pre>";
        print_r($inDB);
        $qInsertLauf->execute($inDB);
        if ( $pdo->lastInsertId() > 0 ) {
            rename($fitfile->getRealPath(), 'fit/done/' . $fitfile->getFilename());
        }

        echo "</pre>"; */
        $qInsertLauf->execute($inDB);


    } else {
        // This must be another Error Message, like Fit File cannot be read, ...
        $output['uploaded'] = 'ERROR';
        $output['filename'] = $fileName;
    }


} else {
    $output['uploaded'] = 'ERROR';
}

if($output['uploaded'] == 'OK') {
    // And now move the file to the "Done" folder

    $fs->rename($uploadDir . "/" . $fileName, 'fit/done/' . $fileName);
}

$response = new JsonResponse($output);
$response->send();