# BigqueryArchive

Archives set of bigquery tables into google cloud storage.

## Installation

Just include class in php

```
include_once __DIR__ . '/BigqueryArchive.php';
```
**You must have google cloud sdk installed and configured**
https://cloud.google.com/sdk/docs/

## Usage

```
/*creating archiver object */
echo "\n  Bucket: chocolate_bigquery_backup";
echo "\n  Dataset: reporting_chocolate";
echo "\n  Table Pattern: auctions_2017010";

$archiver = new BigqueryArchive("chocolate_bigquery_backup","reporting_chocolate", "auctions_2017010");

/* Get list of tables */
$list = $archiver->getTableListFromBq();
$archiver->echoList($list);

/* Archiving .... */
$archiver->archive();

/* Restoring from Archive ....*/
$archiver->restore();
$archiver->deleteRestoredBackup();


/*
 'delete' could be used for deleting table without checking backup
*/
//$archiver->delete();
```
## Output

```
$ php test.php

  Bucket: chocolate_bigquery_backup
  Dataset: reporting_chocolate
  Table Pattern: auctions_2017010
 Getting list of tables
Dataset: reporting_chocolate
Table List:
                auctions_20170101
                auctions_20170103
                auctions_20170104
                auctions_20170106
                auctions_20170107
                auctions_20170108
                auctions_20170109
 Archiving tables started ........................
  Backing up schemas.....................

  ..................................done.

  Backing up tables:
  Dataset: reporting_chocolate
  Table List:
                  auctions_20170101
                  auctions_20170103
                  auctions_20170104
                  auctions_20170106
                  auctions_20170107
                  auctions_20170108
                  auctions_20170109
  .......................................

  Following tables backed up Successfully:
  Dataset: reporting_chocolate
  Table List:
                  auctions_20170101
                  auctions_20170102
                  auctions_20170103
                  auctions_20170104
                  auctions_20170105
                  auctions_20170106
                  auctions_20170107
                  auctions_20170108
                  auctions_20170109
  ..................................done.


   Deleting backed up tables ...........
   Following tables are deleted:
   Dataset: reporting_chocolate
   Table List:
                   auctions_20170101
                   auctions_20170102
                   auctions_20170103
                   auctions_20170104
                   auctions_20170105
                   auctions_20170106
                   auctions_20170107
                   auctions_20170108
                   auctions_20170109
  ..................................done.

 Following tables archived Successfully:
 Dataset: reporting_chocolate
 Table List:
                 auctions_20170101
                 auctions_20170102
                 auctions_20170103
                 auctions_20170104
                 auctions_20170105
                 auctions_20170106
                 auctions_20170107
                 auctions_20170108
                 auctions_20170109
 ............................................done.


 Restoring .....................................
 Downloading schemas ....................

  ..................................done.
  Restoring tables:
   Dataset: reporting_chocolate
   Table List:
                   auctions_20170101
                   auctions_20170102
                   auctions_20170103
                   auctions_20170104
                   auctions_20170105
                   auctions_20170106
                   auctions_20170107
                   auctions_20170108
                   auctions_20170109
  ...
  Following tables restored Successfully:
   Dataset: reporting_chocolate
   Table List:
                   auctions_20170101
                   auctions_20170103
                   auctions_20170104
                   auctions_20170106
                   auctions_20170107
                   auctions_20170108
                   auctions_20170109
  ........................................done.

  Deleting backup of restored tables..............
  Dataset: reporting_chocolate
  Table List:
                  auctions_20170101
                  auctions_20170103
                  auctions_20170104
                  auctions_20170106
                  auctions_20170107
                  auctions_20170108
                  auctions_20170109
  Backup of following tables deleted Successfully:
  Dataset: reporting_chocolate
  Table List:
                  auctions_20170101
                  auctions_20170103
                  auctions_20170104
                  auctions_20170106
                  auctions_20170107
                  auctions_20170108
                  auctions_20170109
  .........................................done.
```
