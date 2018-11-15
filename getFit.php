<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;

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
    $output['uploaded'] = true;
    $output['fileName'] = $fileName;
}

// This is just a fast workaound, need to add if Statement
$pFFA = new adriangibbons\phpFITFileAnalysis('fit/new/' . $fileName);

$output['datum'] = date('d.m.Y',$pFFA->data_mesgs['session']['start_time']);
$output['laenge'] = $pFFA->data_mesgs['session']['total_distance'];
$output['dauer'] = $pFFA->data_mesgs['session']['total_timer_time'];
$output['filename'] = $fileName;


$response = new JsonResponse($output);
$response->send();