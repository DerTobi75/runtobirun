<?php

## ToDo: Better Error Handling on Upload and Process

require_once 'vendor/autoload.php';
require_once 'myStuff.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

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

    $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " moved to fit/new.\n";
    $fs->appendToFile($fitLogfile, $logLine);
} else {
    $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " CANNOT be moved to fit/new.\n";
    $fs->appendToFile($fitLogfile, $logLine);
}

if($fs->exists($uploadDir . "/" . $fileName)) {

    // Build a better PDO SQL Query
    $sql = "SELECT lauf_id FROM laeufe WHERE lauf_fitfile = '" . $fileName . "'";
    $checkfile = $pdo->query($sql);

    if ($checkfile->rowCount() == 0) {
        $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " is new. Try to get information from it.\n";
        $fs->appendToFile($fitLogfile, $logLine);
        $qInsertLauf = $pdo->prepare("INSERT INTO `laeufe` (`lauf_name`, `lauf_datum`, `lauf_laenge`, `lauf_dauer`, `lauf_fitfile`) 
                                        VALUES (:lauf, :datum, :laenge, :dauer, :filename)");

        // Let's think, which one is better!
        //$pFFA = new adriangibbons\phpFITFileAnalysis($fitfile->getRealPath());
        $pFFA = new adriangibbons\phpFITFileAnalysis('fit/new/' . $fileName);

        // Check if you can use an array or variables, you nearly use the same data above for the DB Query
        // Check how to know which array is running, in BRICK Activity it is right now 2

        if(is_array($pFFA->data_mesgs['session']['start_time'])) {
            $output['datum'] = $pFFA->data_mesgs['session']['start_time'][2];
            $output['laenge'] = $pFFA->data_mesgs['session']['total_distance'][2];
            $output['dauer'] = $pFFA->data_mesgs['session']['total_timer_time'][2];
            $output['filename'] = $fileName;
        } else {
            $output['datum'] = $pFFA->data_mesgs['session']['start_time'];
            $output['laenge'] = $pFFA->data_mesgs['session']['total_distance'];
            $output['dauer'] = $pFFA->data_mesgs['session']['total_timer_time'];
            $output['filename'] = $fileName;
        }

        $inDB = [
            'lauf' => 'Laufname',
            'datum' => $output['datum'],
            'laenge' => $output['laenge'],
            'dauer' => $output['dauer'],
            'filename' => $fileName,
        ];

        // Make Date Output nice
        $output['datum'] = date('d.m.Y', $output['datum']);

        $qInsertLauf->execute($inDB);

        if($pdo->lastInsertId() > 0) {
            $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " information read, insert into Database, ID: " . $pdo->lastInsertId() . "\n";
            $fs->appendToFile($fitLogfile, $logLine);

            // And now move the file to the "Done" folder
            try {
                $fs->rename($uploadDir . "/" . $fileName, 'fit/done/' . $fileName);
                $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " moved to fit/done.\n\n";
                $fs->appendToFile($fitLogfile, $logLine);
            } catch (IOExceptionInterface $exception) {
                $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " CANNOT be moved to fit/done.\n\n";
                $fs->appendToFile($fitLogfile, $logLine);
            }
        }
    } else {
        // This must be another Error Message, like Fit File cannot be read, ...
        $output['uploaded'] = 'ERROR';
        $output['filename'] = $fileName;
        $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " seems already to be in the Database. File is possibly not deleted yet!\n\n";
        $fs->appendToFile($fitLogfile, $logLine);
    }


} else {
    $output['uploaded'] = 'ERROR';
    $logLine = date('d.m.Y H:i:s') . " - File: " . $fileName . " does NOT exist.\n\n";
    $fs->appendToFile($fitLogfile, $logLine);
}

$response = new JsonResponse($output);
$response->send();