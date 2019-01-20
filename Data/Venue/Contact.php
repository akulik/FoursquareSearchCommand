<?php namespace App\Command\Foursquare\Data\Venue;

use App\Command\Foursquare\Data\GetOptionTrait;

class Contact
{
    use GetOptionTrait;

    /** @var  string */
    public $phone;

    /** @var  string */
    public $formattedPhone;

    /** @var  string */
    public $twitter;

    public function __construct($options)
    {
        $this->phone = self::getOption($options, 'phone', '');
        $this->formattedPhone = self::getOption($options, 'formattedPhone', '');
        $this->twitter = self::getOption($options, 'twitter', '');
    }
}
