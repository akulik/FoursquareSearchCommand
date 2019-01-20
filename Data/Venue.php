<?php namespace App\Command\Foursquare\Data;

use App\Command\Foursquare\Data\Venue\Category;
use App\Command\Foursquare\Data\Venue\Contact;
use App\Command\Foursquare\Data\Venue\Location;
use App\Command\Foursquare\Data\Venue\Photo;

class Venue
{
    use GetOptionTrait;

    /** @var  string */
    public $id;

    /** @var  string */
    public $name;

    /** @var  Contact */
    public $contact;

    /** @var  Location */
    public $location;

    /** @var  Category[] */
    public $categories;

    /** @var  bool */
    public $verified;

    /** @var  string */
    public $url;

    /** @var  float */
    public $rating;

    /** @var  int */
    public $ratingSignals;

    /** @var  Photo[] */
    public $photos;

    public function __construct($options)
    {
        $this->id = self::getOption($options, 'id', '');
        $this->name = self::getOption($options, 'name', '');
        $this->contact = new Contact(self::getOption($options, 'contact', []));
        $this->location = new Location(self::getOption($options, 'location', []));
        $this->categories = self::getOption($options, 'categories', []);
        foreach ($this->categories as &$category) {
            $category = new Category($category);
        }
        $this->verified = self::getOption($options, 'verified', false);
        $this->url = self::getOption($options, 'url', '');
        $this->rating = self::getOption($options, 'rating', 0.0);
        $this->ratingSignals = self::getOption($options, 'ratingSignals', 0);

        $this->photos = [];
        $groups = self::getOption(self::getOption($options, 'photos', []), 'groups', []);
        foreach ($groups as $group) {
            $this->photos = array_merge($this->photos, self::getOption($group, 'items', []));
        }
        foreach ($this->photos as &$photo) {
            $photo = new Photo($photo);
        }
    }
}
