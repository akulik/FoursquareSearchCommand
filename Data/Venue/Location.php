<?php namespace App\Command\Foursquare\Data\Venue;

use App\Command\Foursquare\Data\GetOptionTrait;

class Location
{
    use GetOptionTrait;

    /** @var  string */
    public $address;

    /** @var  string */
    public $crossStreet;

    /** @var  float */
    public $lat;

    /** @var  float */
    public $lng;

    /** @var  float */
    public $distance;

    /** @var  string */
    public $postalCode;

    /** @var  string */
    public $cc;

    /** @var  string */
    public $neighborhood;

    /** @var  string */
    public $city;

    /** @var  string */
    public $state;

    /** @var  string */
    public $country;

    /** @var  string[] */
    public $formattedAddress;

    public function __construct($options)
    {
        $this->address = self::getOption($options, 'address', '');
        $this->crossStreet = self::getOption($options, 'crossStreet', '');
        $this->lat = self::getOption($options, 'lat', 0);
        $this->lng = self::getOption($options, 'lng', 0);
        $this->distance = self::getOption($options, 'distance', 0);
        $this->postalCode = self::getOption($options, 'postalCode', '');
        $this->cc = self::getOption($options, 'cc', '');
        $this->neighborhood = self::getOption($options, 'neighborhood', '');
        $this->city = self::getOption($options, 'city', '');
        $this->state = self::getOption($options, 'state', '');
        $this->country = self::getOption($options, 'country', '');
        $this->formattedAddress = self::getOption($options, 'formattedAddress', []);
    }
}
