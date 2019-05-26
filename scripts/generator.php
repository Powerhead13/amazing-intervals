<?php
/**
 * Script to populate database with fake intervals of different properties
 * Used for performance testing
 * Usage: php generator.php -n 1000  (will insert 1K - 20K intervals in db table interval_full)
 */

require_once(dirname(__FILE__) . "/../src/defines.php");
require_once(SRC_ROOT . "/IntervalModel.php");
require_once(SRC_ROOT . "/Interval.php");

ini_set('memory_limit','2G');

$options = getopt("n:t:");
if(!isset($options["n"])) {
    die("Generates fake interval records in database\nUsage: php generator.php -n NUMBER_OF_PROPERTIES [-t table_name]\n");
}
$model = new IntervalModel();

if(isset($options["t"])) {
    $model->setTable($options["t"]);
} else {
    $model->setTable(IntervalModel::TABLE_FULL);
}

$dbManager = $model->getDbManager();
$dbManager->query("CREATE TABLE IF NOT EXISTS `{$model->getTable()}` LIKE `".IntervalModel::TABLE_DEFAULT."`");
$currentRecordsCnt = $model->count();


$recordsNum = (int) $options["n"];
echo "Adding $recordsNum fake properties to database table '{$model->getTable()}''. Current records num: {$currentRecordsCnt} \n";

$propertyId = $model->getMaxPropertyId() + 1;
if($propertyId == IntervalModel::DEFAULT_PROPERTY_ID) {
    $propertyId++;
}

$batch = [];
$batchSize = 1000;
$totalRecords = $step = 0;
$displayStep = 100000;
for($i = 0; $i < $recordsNum; $i++) {

    $intervalsNum = rand(1, 20);

    $prevPrice = 0;
    /**
     * @var DateTime $prevDate
     */
    $prevDate = null;
    for($n = 0; $n <= $intervalsNum; $n++) {

        $price = rand(10, 100);

        if($prevDate) {

            $daysBetweenIntervals = rand(1, 60);
            $intervalBetween = new DateInterval("P{$daysBetweenIntervals}D");
            $dateStart = clone $prevDate;
            $dateStart->add($intervalBetween);
            $diff = $dateStart->diff($prevDate);
            if($diff->d == 1 && $price == $prevPrice) {
                $price += 25;
            }


        } else {
            $startYear = rand(2017, 2019);
            $startMonth = rand(1, 5);
            $startDay = rand(1, 25);
            $dateStart = new DateTime("$startYear-$startMonth-$startDay");
        }

        $rangeDays = rand(1, 12);
        $intervalRange = new DateInterval("P{$rangeDays}D");
        $dateEnd = clone $dateStart;
        $dateEnd->add($intervalRange);

        $interval = new Interval([
            "propertyId" => $propertyId,
            "price" => $price,
            "dateStart" => $dateStart,
            "dateEnd" => $dateEnd,
        ]);

        $prevPrice = $price;
        $prevDate = clone $dateEnd;

        $batch[] = $interval;

        if(sizeof($batch) >= $batchSize) {
            $totalRecords += sizeof($batch);
            release($batch, $model);
        }

        $step++;
        if($step == $displayStep) {
            $step = 0;
            echo number_format($totalRecords) . " records added\n";
        }
    }


    $propertyId++;

}
if(sizeof($batch) > 0) {
    $totalRecords += sizeof($batch);
    release($batch, $model);
}

echo "\nInserted $totalRecords interval records into {$model->getTable()}\n\n";


/**
 * @param array $batch
 * @param DbManager $dbManager
 */
function release(array &$batch, IntervalModel &$model)  {
    //echo "Insert batch of " . sizeof($batch) . " records\n";

    $items = [];
    /**
     * @var Interval $interval
     */
    foreach ($batch as $interval) {
        $items[] = "(DEFAULT, '{$interval->getDateStartFormatted()}', '{$interval->getDateEndFormatted()}', {$interval->getPrice()}, {$interval->getPropertyId()})";
    }
    $sql = "INSERT INTO `{$model->getTable()}` VALUES\n\t";
    $sql .= join(",\n\t", $items) . ";\n";

    $model->getDbManager()->query($sql);

    $batch = [];
}



