<?php namespace App\Command\Foursquare\Data;

class Coordinates
{
    /** @var  float */
    public $latitude;

    /** @var  float */
    public $longitude;

    /**
     * @param float $latitude
     *
     * @return float
     */
    protected static function normalizeNegativeLatitude($latitude)
    {
        return $latitude < -90 ? self::normalizeLatitude($latitude + 180) : $latitude;
    }

    /**
     * @param float $latitude
     *
     * @return float
     */
    protected static function normalizePositiveLatitude($latitude)
    {
        return $latitude > 90 ? self::normalizeLatitude($latitude - 180) : $latitude;
    }

    /**
     * @param float $latitude
     *
     * @return float
     */
    protected static function normalizeLatitude($latitude)
    {
        return $latitude >= 0
            ? self::normalizePositiveLatitude($latitude)
            : self::normalizeNegativeLatitude($latitude);
    }

    /**
     * @param float $longitude
     *
     * @return float
     */
    protected static function normalizeNegativeLongitude($longitude)
    {
        return $longitude < -180 ? self::normalizeLongitude($longitude + 360) : $longitude;
    }

    /**
     * @param float $longitude
     *
     * @return float
     */
    protected static function normalizePositiveLongitude($longitude)
    {
        return $longitude > 180 ? self::normalizeLongitude($longitude - 360) : $longitude;
    }

    /**
     * @param float $latitude
     *
     * @return float
     */
    protected static function normalizeLongitude($latitude)
    {
        return $latitude >= 0
            ? self::normalizeNegativeLongitude($latitude)
            : self::normalizePositiveLongitude($latitude);
    }

    /**
     * CoordinatesType constructor.
     *
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude = self::normalizeLatitude($latitude);
        $this->longitude = self::normalizeLongitude($longitude);
    }
}
