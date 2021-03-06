<?php

/**
 * This file is part of Twig.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Emanuele Gaspari Castelletti <inmarelibero@gmail.com>
 * @package Twig
 * @subpackage Twig-extensions
 */
class Twig_Extensions_Extension_Array extends Twig_Extension
{
    /**
     * Returns a list of filters
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            'sort_by_attribute'   => new Twig_Filter_Function('twig_sort_by_attribute_filter'),
        );
    }

    /**
     * Name of this extension
     *
     * @return string
     */
    public function getName()
    {
        return 'Array';
    }
}

/**
 * Sorts an array.
 * Allows to sort an array of objects by a specified object property.
 * Allows to sort an array of arrays by a specified index/key.
 * Allows to sort hybrid arrays of objects, string, numbers
 * 
 * $options accepted values:
 * 	'caseSensitive': true|false(default)
 * 
 * @param array $array The array to be sorted
 * @param string $attribute An object property or an array index/key
 * @param array $options An array of options
 */
function twig_sort_by_attribute_filter($array, $attribute = null, $options = array('caseSensitive' => false))
{
    // returns the original array if $attribute is not specified
    if (null === $attribute) {
        return $array;
    }
    
    /*
     * builds $arrItemsToSort, a temp array to be sorted
     * 
     * $arrItemsToSort keys		= $array keys
     * $arrItemsToSort values	= values of each object's attribute of $array or values of $array at specified index/key
     */
    $arrItemsToSort = array();
    
    // array to store the items which cannot be sorted. they will be merged to $arrItemsToSort in the end
    $arrExcludedItemsFromSorting = array();
    
    foreach ($array as $k => $v) {
        // implements ArrayAccess
        if ($v instanceof ArrayAccess && isset($v[$attribute])) {
            $v = $v[$attribute];
        // $v is an object
        } elseif (is_object($v)) {
            $getter = preg_replace("/[^a-zA-Z0-9]/", "", "get".$attribute);
            $isser  = preg_replace("/[^a-zA-Z0-9]/", "", "is".$attribute);
            
            if (method_exists($v, $getter)) {
                $v = $v->$getter();
            } elseif (method_exists($v, $isser)) {
                $v = $v->$isser();
            } else {
                array_push($arrExcludedItemsFromSorting, $v);
                continue;
            }
        // $v is an array
        } elseif (is_array($v) && isset($v[$attribute])) {
            $v = $v[$attribute];
        // otherwise the item is excluded from sorting
        } else {
            array_push($arrExcludedItemsFromSorting, $v);
            continue;
        }
        
        // applies "caseSensitive" option
        if (!isset($options['caseSensitive']) || true !== $options['caseSensitive']) {
            if (is_string($v)) {
                $v = strtolower($v);
            }
        }
        
        // $v is now te value the user wants to sort the $array by
        $arrItemsToSort[$k] = $v;
    }
    
    // actually sort the $arrItemsToSort array
    asort($arrItemsToSort);
    
    // replaces the $arrItemsToSort values with the original $array values
    foreach ($arrItemsToSort as $k => $v) {
        $arrItemsToSort[$k] = $array[$k];
    }
    
    // returns the sorted portion of the original $array merged with the items excluded from sorting
    return array_merge($arrItemsToSort, $arrExcludedItemsFromSorting);
}