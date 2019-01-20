<?php namespace App\Command\Foursquare\Data\Venue\Category;

use App\Command\Foursquare\Data\GetOptionTrait;

class Icon
{
    use GetOptionTrait;

    /** @var  string */
    public $prefix;

    /** @var  string */
    public $suffix;

    public function __construct($options)
    {
        $this->prefix = self::getOption($options, 'prefix', '');
        $this->suffix = self::getOption($options, 'suffix', '');
    }
}
