<?php
include_once __DIR__ . "/ParallelExecutePHP/ParallelExecutor.php";
/*
 * Backups bigquery table into google cloud storage and can restore them as well.
 * @usage: 
 * 
 *include_once __DIR__ . '/BigqueryArchive.php';
 *$archiver = new BigqueryArchive("chocolate_bigquery_backup","reporting_chocolate", "auctions_20161213");
 *$archiver->archive();
 *$archiver->restore();
 *$archiver->deleteRestoredBackup(); 
 */
class BigqueryArchive{
        private $pattern;
        private $dataset;
        private $bucket;
        private $executor;
        private $verbose = false;

        /**
         * 
         * @param type $bucket Google Cloud Storage Bucket for backup.
         * @param type $dataset
         * @param type $pattern Pattern of table to search
         */
        public function BigqueryArchive($bucket,$dataset,$pattern){
                $this->pattern = $pattern;
                $this->dataset = $dataset;
                $this->bucket = $bucket;
                
        }
        
        /**
         * Backups table to google cloud storage
         * @return Array list of tables backed up successfully.
         */
        public function backup(){
               $this->setupExecutor();
               $tableList = $this->getTableListFromBq();
               
               echo "\n  Backing up schemas.....................\n";
               foreach($tableList as $table){
                   $this->saveSchema($table);
               }
               echo "\n\n  ..................................done.";
               echo "\n\n  Backing up tables:";
               $this->echoList($tableList, "  ");
               echo "\n  .......................................";
               foreach($tableList as $table){
                   $cmd = $this->getBackupCommand($table);
                   $this->executor->addCommand($cmd);
               }
               $this->executor->run();
               $this->executor->wait();
               $tableList = $this->getTableListFromGCS();
               echo "\n\n  Following tables backed up Successfully:";
               $this->echoList($tableList, "  ");
               echo "\n  ..................................done.\n";
               return $tableList;
        }
        
        /**
         * Archives tables. Backup + Delete
         * @return Array List of successully archived tables.
         */
        public function archive(){
               echo "\n Archiving tables started ........................";
               $this->backup();
               $this->deleteBackedUpTables();
               $tableList = $this->getTableListFromGCS();
               echo "\n\n Following tables archived Successfully:";
               $this->echoList($tableList," ");
               echo "\n ............................................done.\n";
               return $tableList;
        }

        /**
         *
         * @return Array List of deleted tables
         */
        public function delete(){
               $this->setupExecutor();
               $tableList = $this->getTableListFromBq();
               echo "\n\n   Deleting tables ...........";

               foreach($tableList as $table){
                   $cmd = $this->getTableDeleteCommand($table);
                   $this->executor->addCommand($cmd);
               }
               $this->executor->run();
               $this->executor->wait();
               echo "\n   Following tables are deleted:";
               $this->echoList($tableList, "   ");
               echo "\n  ..................................done.";
               return $tableList;
        }
        
        /**
         * 
         * @return Array List of deleted tables
         */
        public function deleteBackedUpTables(){
               $this->setupExecutor(); 
               $tableList = $this->getTableListFromGCS();
               echo "\n\n   Deleting backed up tables ...........";
               
               foreach($tableList as $table){
                   $cmd = $this->getTableDeleteCommand($table);
                   $this->executor->addCommand($cmd);
               }
               $this->executor->run();
               $this->executor->wait();
               echo "\n   Following tables are deleted:";
               $this->echoList($tableList, "   ");
               echo "\n  ..................................done.";
               return $tableList;
        }
        
        /**
         * Deletes backed up data in Google Storage if table is restored
         */
        public function deleteRestoredBackup(){
               $this->setupExecutor(); 
               $tableList = $this->getTableListFromBq();
               echo "\n  Deleting backup of restored tables..............";
               $this->echoList($tableList,"  ");
               foreach($tableList as $table){
                   $cmd = $this->getGcsDeleteCommand($table);
                   $this->executor->addCommand($cmd);
               }
               $this->executor->run();
               $this->executor->wait();
               echo "\n  Backup of following tables deleted Successfully:";
               $this->echoList($tableList, "  ");
               echo "\n  .........................................done.\n";
        }
        
        /**
         * Restores table from Google cloud storage backup
         */
        public function restore(){
           $this->setupExecutor(); 
           
           $tableList = $this->getTableListFromGCS();
           echo "\n\n Restoring .....................................";
           echo "\n Downloading schemas ....................\n";
           foreach($tableList as $table){
               $this->restoreSchema($table);
           }
           echo "\n\n  ..................................done.";
           echo "\n  Restoring tables:";
           $this->echoList($tableList, "   ");
           echo "\n  ...";
           foreach($tableList as $table){
               $cmd = $this->getRestoreCommand($table);
               $this->executor->addCommand($cmd);
           }
           $this->executor->run();
           $this->executor->wait();
           $tableList = $this->getTableListFromBq();
               echo "\n  Following tables restored Successfully:";
               $this->echoList($tableList, "   ");
            echo "\n  ........................................done.\n";   
        }
        private function getBackupCommand($table){
            $cmd = "bq extract --compression=GZIP --destination_format=NEWLINE_DELIMITED_JSON '{$this->dataset}.{$table}' 'gs://{$this->bucket}/{$this->dataset}/{$table}/*.gz'";
            return $cmd;
        }
        private function getSchemaPath($table){
            return $path = __DIR__ . "/schemas/{$this->dataset}.{$table}.json";
        }
        private function saveSchema($table){
            $cmd = "bq --format=json show {$this->dataset}.{$table}";
                //echo "\n\n Backing up schema ..\n";
                //echo "\n executing $cmd";
                exec($cmd, $output, $status);
                $data = json_decode($output[0], true);
                $schema = json_encode($data['schema']['fields']);
                
                $path = $this->getSchemaPath($table);
                file_put_contents($path, $schema);
                $cmd = "gsutil mv  $path gs://{$this->bucket}/{$this->dataset}/$table/schema.json";
                //echo "\n executing $cmd";
                exec($cmd, $output, $status);
                //echo "\n   Schema backed up to: gs://{$this->bucket}/{$this->dataset}/$table/schema.json";
                //echo "\n status: {$output[0]}";
        }
        private function getTableDeleteCommand($table){
            return "bq rm -f {$this->dataset}.{$table}";
        }
        private function getGcsDeleteCommand($table){
            return "gsutil -m rm -r gs://{$this->bucket}/{$this->dataset}/$table";
        }
        private function restoreSchema($table){
                $path = $this->getSchemaPath($table);
                $cmd = "gsutil cp gs://{$this->bucket}/{$this->dataset}/$table/schema.json $path";
                //echo "\n executing $cmd";
                exec($cmd, $output, $status);
                //echo "\n status: {$output[0]}";
                //echo "\n  schema restored to $path";
        }
        private function getRestoreCommand($table){
            $path = $this->getSchemaPath($table);
            $gcsPath = "gs://{$this->bucket}/{$this->dataset}/$table/*.gz";
            $cmd = "bq load --source_format=NEWLINE_DELIMITED_JSON --schema={$path} {$this->dataset}.$table $gcsPath";
            return $cmd;
            
        }
        /**
         * 
         * @return Array List of tables matching table pattern
         */
        public function getTableListFromBq(){
                $cmd = "bq --format=json ls -n 10000 {$this->dataset}";
                //echo "\n executing $cmd";
                exec($cmd, $output, $status);
                $list = json_decode($output[0], true);
                $out = array();
                foreach($list as $item){
                    if(strpos($item["tableId"], $this->pattern) !== false){
                            $out[] = $item["tableId"];
                    }
                }
                return $out;
        }
        /**
         * 
         * @return Array List of archived tables matching table pattern
         */
        public function getTableListFromGCS(){
            $cmd = "gsutil ls gs://{$this->bucket}/{$this->dataset}/*{$this->pattern}*/*.gz";
            //echo "\n executing $cmd";
            exec($cmd, $output, $status);
	    $out = array();
            foreach($output as $path){
                $temp = explode("/",$path);
                $table = $temp[sizeof($temp)-2];
                if(strpos($table, $this->pattern) !== false){
                    $out[$table] =1 ;
                }
            }
            return array_keys($out);
        }
        private function setupExecutor(){
                $executor = new ParallelExecutor();
                $executor->setVerbose($this->verbose);
                $executor->setMaxParallelJobs(31);
                $this->executor = $executor;
        }
        
        public function echoList($list,$prefix=""){
            echo "\n{$prefix}Dataset: {$this->dataset} \n{$prefix}Table List:";
            foreach($list as $item){
                echo "\n{$prefix}                $item";
            }
            echo ""; 
        }
        
        /**
         * 
         * @param Boolean $verbose true/false
         */
        public function setVerbose($verbose = true){
            $this->verbose = $verbose;
        }
}
