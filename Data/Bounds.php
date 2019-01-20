<?php namespace App\Command\Foursquare\Data;

class Bounds
{
    /** @var  Coordinates */
    public $northEast;

    /** @var  Coordinates */
    public $southWest;

    public function __construct(Coordinates $northEast, Coordinates $southWest)
    {
        $this->northEast = $northEast;
        $this->southWest = $southWest;
    }
}
