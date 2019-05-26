<?php

require_once(SRC_ROOT . "/Interval.php");
require_once(SRC_ROOT . "/IntervalPool.php");

class IntervalModel
{
    const DEFAULT_PROPERTY_ID = 1;
    const TABLE_DEFAULT       = "interval";
    const TABLE_TEST          = "interval_test";
    const TABLE_FULL          = "interval_full";

    /**
     * @var DbManager
     */
    private $dbManager;

    /**
     * @var string
     */
    private $table = self::TABLE_DEFAULT;

    /**
     * @var IntervalPool
     */
    private $pool;

    /**
     * IntervalModel constructor.
     * @param DbManager $dbManager
     * @throws Exception
     */
    public function __construct(DbManager $dbManager = null) {
        if(!$dbManager) {
            $dbManager = new DbManagerMySql();
        }
        $this->dbManager = $dbManager;
    }


    /**
     * @param int $intervalId
     * @param bool $persist
     * @return Interval
     */
    public function getById(int $intervalId, $persist = false) {
        $forUpdate = ($persist ? "FOR UPDATE" : "");
        $row = $this->getDbManager()->query("SELECT * FROM `{$this->table}` WHERE id = ? $forUpdate", [$intervalId])
            ->fetchArray();
        if($row) {
            return new Interval($row);
        }
        return null;
    }

    /**
     * List all intervals of specified property
     * @param bool $fields
     * @param int $propertyId
     * @param bool $persist
     * @return array Collection of Interval
     * @throws Exception
     */
    public function getIntervals($fields = false, $propertyId = self::DEFAULT_PROPERTY_ID) {
        $f = "*";
        if($fields) {
            $f = join(",", $fields);
        }
        $sql = "SELECT $f FROM `{$this->table}` WHERE property_id = ? ORDER BY date_start";
        $result = [];
        $rows = $this->dbManager->query($sql, [$propertyId]
        )->fetchAll();

        if(!empty($rows)) {
            foreach($rows as $row) {
                $result[] = new Interval($row);
            }
        }

        return $result;
    }

    /**
     * Get intervals as array
     * @return array
     * @throws Exception
     */
    public function listIntervals() {
        $intervals = $this->getIntervals();
        $result = [];
        if(!empty($intervals)) {
            /**
             * @var Interval $int
             */
            foreach($intervals as $int) {
                $result[] = [
                    $int->getDateStartFormatted(),
                    $int->getDateEndFormatted(),
                    $int->getPrice(),
                    $int->getId(),
                ];
            }
        }
        return $result;
    }

    /**
     * @return int
     */
    public function count() {
        $r = $this->getDbManager()->query("SELECT COUNT(*) cnt FROM `{$this->table}`")->fetchArray();
        return $r["cnt"];
    }

    /**
     * @return mixed
     */
    public function getMaxPropertyId() {
        $r = $this->getDbManager()->query("SELECT MAX(property_id) max_property_id FROM `{$this->table}`")->fetchArray();
        return $r["max_property_id"];
    }

    /**
     * @return DbManager
     */
    public function getDbManager(): DbManager
    {
        return $this->dbManager;
    }

    /**
     * @return IntervalPool
     */
    public function getPool(): IntervalPool
    {
        return $this->pool;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param int $intervalId
     */
    public function delete(int $intervalId) {
        if($intervalId) {
            $this->getDbManager()->query("DELETE FROM `{$this->table}` WHERE id = ? LIMIT 1", [$intervalId]);
        }
    }

    /**
     * Truncate table or clear intervals by property
     * @param int $propertyId
     */
    public function clear(int $propertyId = 0) {
        if($propertyId) {
            $this->getDbManager()->query("DELETE FROM `{$this->table}` WHERE property_id = ?", [$propertyId]);
        } else {
            $this->getDbManager()->query("TRUNCATE TABLE`{$this->table}`");
        }
    }

    /**
     * Injects interval into database within minimum DB operations
     * @param Interval $interval
     * @throws Exception
     */
    public function inject(Interval &$interval) {

        if($interval->getDateEnd() <= $interval->getDateStart()) {
            throw new Exception("Unsupported interval range");
        }

        // Start transaction for data consistency
        $this->dbManager->beginTransaction();

        try {
            // Pool that collects dp operations with intervals
            $pool = $this->constructOperationsPool($interval);

            $this->pool = $pool;
            // Make actual DB operations
            $this->processOperationsPool($pool);

            // Commit transaction
            $this->dbManager->commit();

        } catch(Exception $e) {
            // Rollback on any transaction exception
            $this->dbManager->rollback();
            $this->dbManager->close();
            throw $e;
        }
    }

    /**
     * Collects optimal DB operations pool that need to perform interval injection
     * @param Interval $interval
     * @return IntervalPool|void
     * @throws Exception
     */
    private function constructOperationsPool(Interval &$interval) {

        // Pool that collects db operations on intervals
        $pool = new IntervalPool();

        // Updating operation requested
        if($interval->getId()) {
            // Get original interval from DB
            $updating = $this->getById($interval->getId(), true);
            // Nothing changed. No operations needed
            if($updating == $interval) {
                return $pool;
            }
            // Only price changed. 1 UPDATE operation needed
            elseif(
                $updating->getDateStart() == $interval->getDateStart() &&
                $updating->getDateEnd() == $interval->getDateEnd()) {
                $pool->addInsertOrUpdate($interval);
                return $pool;
            }
        }

        /**
         * Find potential overlaps
         * This operation will lock affected records for update until transaction complete
         */
        $overlapped = $this->getOverlappedIntervals($interval);

        if($overlapped) {
            // In case of overlaps exists, apply logic to perform injection in optimal way
            $this->processOverlappedIntervals($interval, $overlapped, $pool);
        } else {
            // If no overlaps, insert or update record only
            $pool->addInsertOrUpdate($interval);
        }

        return $pool;
    }



    /**
     * Query DB to find overlaps with current interval
     * This query will lock affected records for update inside transaction
     * @param Interval $interval
     * @return array|null
     * @throws Exception
     */
    private function getOverlappedIntervals(Interval &$interval) {
        /**
         * Find intersected intervals
         * FOR UPDATE is locking records until transaction complete to guarantee data consistency
         * Record-level locking performed within InnoDb storage
         */
        $cond = "";
        $params = [
            $interval->getPropertyId(),
            $interval->getDateStartFormatted(),
            $interval->getDateEndFormatted(),
        ];
        /**
         * Update interval context.
         * In this case we won't include same interval that we're going to update into overlaps condition
         */
        if($interval->getId()) {
            $cond .= "AND id != ?";
            $params[] = $interval->getId();
        }
        $rows = $this->dbManager->query("SELECT * FROM `{$this->table}`
                WHERE property_id = ?
                AND ? <= date_end
                AND ? >= date_start
                $cond
                ORDER BY date_start
                FOR UPDATE
            ", $params)->fetchAll();

        if(!empty($rows)) {
            $overlapped = [];
            foreach($rows as $row) {
                $overlapped[] = new Interval($row);
            }
            return $overlapped;
        }
        return null;
    }


    /**
     * Analyze intersections of interval with overlaps to find most optimal injection operations
     * Populate IntervalPool with INSERT, UPDATE, DELETE operations
     * @param Interval $interval
     * @param array $overlapped
     * @param IntervalPool $pool
     */
    private function processOverlappedIntervals(Interval &$interval, array &$overlapped, IntervalPool &$pool)
    {
        /**
         * @var Interval $overlap
         */
        foreach($overlapped as $i => $overlap) {

            $samePrice = $overlap->getPrice() == $interval->getPrice();

            /**
             * Overlap with same price (x) absorbs interval.
             * ---a====b--- x interval
             * -a======b--- x
             * ---a====b--- x
             * ----a=====b- x
             * There are no scenario where any DB changes needed in such case.
             * No actions needed.
             */
            if($samePrice &&
                $overlap->getDateStart() <= $interval->getDateStart() &&
                $overlap->getDateEnd() >= $interval->getDateEnd()) {
                $pool->reset();
                return;
            }

            /**
             * Overlap with same price (x) intersects interval
             * ---a====b--- x
             * -a===b------ x
             * -a=b-------- x
             * ------a===b- x
             * --------a=b- x
             */
            /**
             * OR overlap with different price (y) absorbed by interval
             * --a======b-- x
             * --a======b-- y
             * --a===b----- y
             * ----a==b---- y
             * -----a===b-- y
             *
             * Update overlap to convert it to interval
             */
            if($samePrice || (
                    $overlap->getDateStart() >= $interval->getDateStart() &&
                    $overlap->getDateEnd() <= $interval->getDateEnd()
                )
            ) {

                if($interval->getId() && !$samePrice) {
                    $pool->addDelete($overlap);
                    continue;
                }

                $interval->setDateStart(min($overlap->getDateStart(), $interval->getDateStart()));
                $interval->setDateEnd(max($overlap->getDateEnd(), $interval->getDateEnd()));
                $interval->setPrice($interval->getPrice());

                if(!$interval->getId()) {
                    $interval->setId($overlap->getId());
                } else {
                    $pool->addDelete($overlap);
                }
            }
            /**
             * All other scenarions with different price
             */
            else {

                // Adjoined from the left or right interval with different orice. No action needed
                if($overlap->getDateStart() == $interval->getDateEnd() ||
                    $overlap->getDateEnd() == $interval->getDateStart()) {
                    continue;
                }

                if($overlap->getDateStart() < $interval->getDateStart()) {

                    if($overlap->getDateEnd() > $interval->getDateEnd()) {
                        $pool->addInsertOrUpdate(new Interval([
                            "date_start" => $interval->getDateEnd(),
                            "date_end" => $overlap->getDateEnd(),
                            "price" => $overlap->getPrice(),
                            "property_id" => $interval->getPropertyId(),
                        ]));
                    }
                    $overlap->setDateEnd($interval->getDateStart());

                } elseif($overlap->getDateEnd() > $interval->getDateEnd()) {
                    $overlap->setDateStart($interval->getDateEnd());
                }

                $pool->addUpdate($overlap);
            }
        }
        $pool->addInsertOrUpdate($interval);
    }


    /**
     * Perform DB operations on intervals from the pool
     * @param IntervalPool $pool
     * @throws Exception
     */
    private function processOperationsPool(IntervalPool $pool) {

        if(!empty($pool->getDelete())) {
            $this->deleteIntervals($pool->getDelete());
        }

        if(!empty($pool->getUpdate())) {
            $this->updateIntervals($pool->getUpdate());
        }

        if(!empty($pool->getInsert())) {
            /**
             * IntervalPool $interval
             */
            foreach($pool->getInsert() as $interval) {
                $this->insertInterval($interval);
            }
        }
    }

    /**
     * Delete list of intervals in single DB query
     * @param array $intervals
     * @throws Exception
     */
    private function deleteIntervals(array $intervals) {
        if(!empty($intervals)) {
            $deleteIds = [];
            /**
             * @var Interval $interval
             */
            foreach($intervals as $interval) {
                $deleteIds[] = (int) $interval->getId();
            }
            $condition = join(",", $deleteIds);
            $this->dbManager->query("DELETE FROM `{$this->table}` WHERE id IN ($condition)");
        }
    }

    /**
     * @param array $intervals
     * @throws Exception
     */
    private function updateIntervals(array $intervals) {
        if(!empty($intervals)) {
            /**
             * @var Interval $interval
             */
            foreach($intervals as $interval) {
                $this->dbManager->query("UPDATE `{$this->table}` SET date_start = ?, date_end = ?, price = ? WHERE id = ?", [
                    $interval->getDateStartFormatted(),
                    $interval->getDateEndFormatted(),
                    $interval->getPrice(),
                    $interval->getId(),
                ]);
            }
        }
    }

    /**
     * Prepare statement and store interval in dataase
     * @param Interval $interval
     * @return Interval
     * @throws Exception
     */
    private function insertInterval(Interval &$interval) {
        $insertId = $this->dbManager->query("INSERT INTO `{$this->table}` VALUES (DEFAULT,?,?,?,?)", [
            $interval->getDateStart()->format("Y-m-d"),
            $interval->getDateEnd()->format("Y-m-d"),
            $interval->getPrice(),
            $interval->getPropertyId()
        ])->getId();

        if(!$insertId) {
            throw new Exception("Could not store interval: " . $this->dbManager->getLatestError());
        }

        $interval->setId($insertId);

        return $interval;
    }
}