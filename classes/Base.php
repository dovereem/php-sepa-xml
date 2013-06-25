<?php
/**
 * Base class for SEPA files
 *
 * @author  Damien Overeem ( SoHosted B.V. - www.sohosted.com )
 * @package Sohosted.com
 *
 * @version $Id$
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