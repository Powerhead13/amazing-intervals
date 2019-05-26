<?php

class Interval
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var DateTime
     */
    private $dateStart;

    /**
     * @var DateTime
     */
    private $dateEnd;

    /**
     * @var float
     */
    private $price;

    /**
     * @var int
     */
    private $propertyId;

    /**
     * Interval constructor.
     * @param array|null $params
     */
    public function __construct(array $params = null) {
        if($params) {
            foreach($params as $key => $value) {
                $method = "set" . str_replace(" ", "", ucwords(str_replace("_", " ", $key)));
                if(method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return DateTime
     */
    public function getDateStart(): DateTime
    {
        return $this->dateStart;
    }

    /**
     * @return null|string
     */
    public function getDateStartFormatted()
    {
        if($this->dateStart) {
            return $this->getDateStart()->format("Y-m-d");
        }
        return null;
    }

    /**
     * @param mixed $dateStart
     * @throws Exception
     */
    public function setDateStart($dateStart): void
    {
        if(!$dateStart instanceof DateTime) {
            $dateStart = new DateTime($dateStart);
        }
        $this->dateStart = $dateStart;
    }

    /**
     * @return DateTime
     */
    public function getDateEnd(): DateTime
    {
        return $this->dateEnd;
    }

    /**
     * @return null|string
     */
    public function getDateEndFormatted()
    {
        if($this->dateEnd) {
            return $this->getDateEnd()->format("Y-m-d");
        }
        return null;
    }

    /**
     * @param mixed $dateEnd
     * @throws Exception
     */
    public function setDateEnd($dateEnd): void
    {
        if(!$dateEnd instanceof DateTime) {
            $dateEnd = new DateTime($dateEnd);
        }
        $this->dateEnd = $dateEnd;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getPropertyId()
    {
        return $this->propertyId;
    }

    /**
     * @param int $propertyId
     */
    public function setPropertyId(int $propertyId): void
    {
        $this->propertyId = $propertyId;
    }

    /**
     * @return bool|DateInterval
     */
    public function getInterval() {
        return $this->getDateEnd()->diff($this->getDateStart());
    }

    /**
     * Get interval length in days
     * @return int
     */
    public function getLength() {
        return $this->getInterval()->d;
    }

    /**
     * @return string
     */
    public function __toString() {
        $str = $this->getDateStartFormatted() . " - " . $this->getDateEndFormatted() . " p:" . $this->getPrice();
        if($this->getId()) {
            $str = "#" . $this->getId() . " " . $str;
        }
        return $str;
    }
}