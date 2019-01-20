<?php namespace App\Command\Foursquare\Data\Venue;

use App\Command\Foursquare\Data\Venue\Category\Icon;
use App\Command\Foursquare\Data\GetOptionTrait;

class Category
{
    use GetOptionTrait;

    /** @var  string */
    public $id;

    /** @var  string */
    public $name;

    /** @var  string */
    public $pluralName;

    /** @var  string */
    public $shortName;

    /** @var  Icon */
    public $icon;

    /** @var  bool */
    public $primary;

    public function __construct($options)
    {
        $this->id = self::getOption($options, 'id', '');
        $this->name = self::getOption($options, 'name', '');
        $this->pluralName = self::getOption($options, 'pluralName', '');
        $this->shortName = self::getOption($options, 'shortName', '');
        $this->icon = new Icon(self::getOption($options, 'icon', []));
        $this->primary = self::getOption($options, 'primary', false);
    }
}
