<?php
/**
 * Base class for SEPA files
 *
 * @license GNU Lesser General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Lesser Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Damien Overeem ( SoHosted B.V. - www.sohosted.com )
 * @package     Sepa PHP XML tools
 * @version     0.1-alpha
 *
 */
class Sepa_Base {

    /**
     * The generated XML file
     * @var SimpleXml
     */
    protected $_xml;

    /**
     * Return the XML string.
     * @return string
     */
    public function asXML() {
        $this->_generateXml();
        return $this->_xml->asXML();
    }

    /**
     * Output the XML string to the screen.
     */
    public function outputXML() {
        $this->_generateXml();
        header('Content-type: text/xml');
        echo $this->_xml->asXML();
    }

    /**
     * Download the XML string into XML File
     */
    public function downloadXML() {
        $this->_generateXml();
        header("Content-type: text/xml");
        header('Content-disposition: attachment; filename=sepa_' . date('dmY-His') . '.xml');
        echo $this->_xml->asXML();
        exit();
    }

    /**
     * Format an integer as a monetary value.
     */
    public static function intToCurrency($amount) {
        return sprintf("%01.2f", ($amount / 100));
    }

    /**
     * Format an float as a monetary value.
     */
    public static function floatToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param type $code
     * @return string currency ISO code
     * @throws Exception
     */
    public static function validateCurrency($code) {
        if (strlen($code) !== 3) throw new Exception("Invalid ISO currency code: $code");
        return $code;
    }

    /**
     * Removes all non accepted characters
     *
     * @param string $string
     * @param int $length
     * @return type
     */
    public static function alphanumeric($string, $length) {
        /* Replace the special characters */
        $string = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));

        /* TODO: Remove the unwanted characters */

        /* Return the string with the given max. length */
        return substr($string, 0, $length);
    }

    /**
     * Alternative to the asXml method
     */
    public function __toString() {
        return $this->asXml();
    }


}