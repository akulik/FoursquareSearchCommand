<?php namespace App\Command\Foursquare\Data;

trait GetOptionTrait
{
    protected static function getOption($options, $key, $defaultValue)
    {
        if (isset($options[$key])) {
            if (is_array($defaultValue)) {
                return is_array($options[$key]) ? $options[$key] : $defaultValue;
            } elseif (is_numeric($defaultValue)) {
                return is_numeric($options[$key]) ? $options[$key] : $defaultValue;
            } elseif (is_string($defaultValue)) {
                return is_string($options[$key]) ? $options[$key] : $defaultValue;
            }
        }

        return $defaultValue;
    }
}
