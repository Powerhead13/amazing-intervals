<?php

require_once SRC_ROOT . "/IntervalModel.php";
require_once SRC_ROOT . "/Controller.php";

class IntervalController extends Controller {


    /**
     * @var IntervalModel
     */
    private $model;

    /**
     * @var bool
     */
    private $profilingEnabled = false;

    /**
     * IntervalController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->model = new IntervalModel();

        $table = $this->param("table");
        $this->profilingEnabled = isset($_REQUEST["profile"]);

        // Testing requests on table full of records
        if("full" == $table) {
            $this->model->setTable(IntervalModel::TABLE_FULL);
        }

        $this->model->getDbManager()->setProfilingEnabled($this->profilingEnabled);
    }

    /**
     * @throws Exception
     */
    public function indexAction() {
        require APP_ROOT . "/views/dashboard.php";
    }

    /**
     * API delete interval by id
     */
    public function deleteAction() {

        try {
            $intervalId = (int) $this->param("id");
            if(!$intervalId) {
                throw new Exception("Wrong arguments");
            }
            $this->model->delete($intervalId);
            $this->jsonResponseOk();
        } catch (Exception $e) {
            $this->jsonResponseError($e->getMessage());
        }
    }

    /**
     * API Update existing interval. Make optimal injection
     */
    public function updateAction() {
        $intervalId = (int) $this->param("id");
        if(!$intervalId) {
            $this->jsonResponseError("Missed interval id");
        }
        $this->addAction($intervalId);
    }

    /**
     * API Inject new or update existing interval in most optimal way
     * @param bool $intervalId
     */
    public function addAction($intervalId = false) {

        $dateStart = $this->param("date_start");
        $dateEnd   = $this->param("date_end");
        $price     = (float) $this->param("price");

        try {
            if(!$dateStart || !$dateEnd || !$price) {
                throw new InvalidArgumentException("Missed input data");
            }

            $this->validateDateIntervals($dateStart, $dateEnd);

            // Init new interval
            $interval = new Interval([
                "date_start" => $dateStart,
                "date_end"   => $dateEnd,
                "price"      => $price,
                "propertyId" => IntervalModel::DEFAULT_PROPERTY_ID
            ]);

            if($intervalId) {
                $interval->setId($intervalId);
            }
            $this->model->inject($interval);

            $profilingData = $this->collectProfilingData();

            $listIntervals = $this->model->listIntervals();

            $this->jsonResponseOk([
                "intervals" => $listIntervals,
                "profile"   => $profilingData,
            ]);

        } catch (Exception $e) {
            $this->jsonResponseError($e->getMessage());
        }
    }

    /**
     * @return array|null
     */
    private function collectProfilingData() {
        $profilingData = null;
        if($this->profilingEnabled) {
            $profiler = $this->model->getDbManager()->getProfiler();
            $profiler->explain($this->model->getDbManager());
            $profilingData = [
                "queries"     => $profiler->getQueries(false),
                "all_queries" => $profiler->getQueries(true),
                "time"        => $profiler->getOverallTime(),
                "counters"    => $profiler->getCounters(),
                "mutations"   => $profiler->getModifyingQueryCount(),
            ];
        }
        return $profilingData;
    }



    /**
     * API endpoint to truncate intervals table
     */
    public function clearAction() {
        try {
            $table     = $this->param("table");
            // Testing requests on table full of records
            if("full" == $table) {
                $this->model->setTable(IntervalModel::TABLE_FULL);
                $this->model->clear(IntervalModel::DEFAULT_PROPERTY_ID);
            } else {
                $this->model->clear();
            }

            $this->jsonResponseOk();
        } catch (Exception $e) {
            $this->jsonResponseError($e->getMessage());
        }
    }

    /**
     * API endpoint to list all intervals
     */
    public function listAction() {
        $table     = $this->param("table");
        try {
            // Testing requests on table full of records
            if("full" == $table) {
                $this->model->setTable(IntervalModel::TABLE_FULL);
            }
            $list = $this->model->listIntervals();
            $this->jsonResponseOk($list);
        } catch (Exception $e) {
            $this->jsonResponseError($e->getMessage());
        }
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     * @return bool
     */
    private function validateDateIntervals($dateStart, $dateEnd) {
        try {
            $ds = new DateTime($dateStart);
            $de = new DateTime($dateEnd);

            if(!$ds->getTimestamp() || !$de->getTimestamp()) {
                throw new InvalidArgumentException("Unexpected date format provided. Use yyyy-mm-dd");
            }

            if($de <= $ds) {
                throw new InvalidArgumentException("Checkout date should be greater than checkin date");
            }

            return true;

        } catch (InvalidArgumentException $e) {
            $this->jsonResponseError($e->getMessage());
        }
        catch (Exception $e) {
            $this->jsonResponseError("Unexpected date format provided. Use yyyy-mm-dd");
        }
    }


}