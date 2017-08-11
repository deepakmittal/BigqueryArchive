<?php

include_once __DIR__ . '/BigqueryArchive.php';
/*creating archiver object */
echo "\n  Bucket: chocolate_bigquery_backup";
echo "\n  Dataset: reporting_chocolate";
echo "\n  Table Pattern: auctions_2017010";
$archiver = new BigqueryArchive("chocolate_bigquery_backup","reporting_chocolate", "auctions_2017010");

/*
//shows all commands executed and their output
$archiver->verbose(); 
*/
echo "\n Getting list of tables";
$list = $archiver->getTableListFromBq();
$archiver->echoList($list);
$archiver->archive();
$archiver->restore();
$archiver->deleteRestoredBackup();
//$archiver->delete();
