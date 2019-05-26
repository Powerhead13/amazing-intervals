<?php

/**
 * Class IntervalPool
 * Keeps DB operations that need to be performed with intervals
 * Handles three lists of intervals: update, delete, insert
 */
class IntervalPool
{
    /**
     * @var array Interval[]
     */
    private $update = [];

    /**
     * @var array Interval[]
     */
    private $delete = [];

    /**
     * @var array Interval[]
     */
    private $insert = [];

    /**
     * @param Interval $interval
     */
    public function addDelete(Interval $interval) {
        $this->add($this->delete, $interval);
    }

    /**
     * @param Interval $interval
     */
    public function addUpdate(Interval $interval) {
        $this->add($this->update, $interval);
    }

    /**
     * @param Interval $interval
     */
    public function addInsertOrUpdate(Interval $interval) {
        if($interval->getId()) {
            $this->add($this->update, $interval);
        } else {
            $this->add($this->insert, $interval);
        }
    }

    public function reset() {
        $this->update = [];
        $this->insert = [];
        $this->delete = [];
    }

    /**
     * @return Interval|null
     */
    public function &getLatestUpdate() {
        if(!empty($this->update)) {
            return $this->update[sizeof($this->update) - 1];
        }
        return null;
    }


    /**
     * Push element into the list sorted by date_start and checked for uniqueness
     * @param $list
     * @param Interval $interval
     */
    private function add(&$list, Interval $interval) {
        if(!$this->exists($list, $interval)) {
            $list[] = $interval;
            usort($list, [$this, "compare"]);
        }
    }

    /**
     * Compate date_start of two intervals for sorting
     * @param Interval $a
     * @param Interval $b
     * @return int
     */
    private function compare(Interval $a, Interval $b)
    {
        if ($a->getDateStart() == $b->getDateStart()) {
            return 0;
        }
        return ($a->getDateStart() < $b->getDateStart()) ? -1 : 1;
    }


    /**
     * Check that lists keep only unique intervals to avoid duplicates
     * @param $list
     * @param Interval $interval
     * @return bool
     */
    private function exists(&$list, Interval $interval) {
        if(!empty($list)) {
            /**
             * @var Interval $in
             */
            foreach($list as &$in) {
                if($in->getDateStart() == $interval->getDateStart()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getUpdate(): array
    {
        return $this->update;
    }

    /**
     * @return array
     */
    public function getDelete(): array
    {
        return $this->delete;
    }

    /**
     * @return array
     */
    public function getInsert(): array
    {
        return $this->insert;
    }
}