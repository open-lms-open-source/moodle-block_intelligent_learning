<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * ILP Integration
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */

/**
 * Webservice xml reader helper
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 */

class block_intelligent_learning_helper_xmlreader extends mr_helper_abstract {

    /**
     * Stores any XML validation error strings
     *
     * @var string
     */
    protected $xmlerror = '';

    /**
     * Helper direct method
     *
     * Not used by this helper
     */
    public function direct() {
        throw new Exception('XMLReader helper called incorrectly');
    }

    /**
     * Validate XML and apply restrictions
     * based on allowed actions, mappings and
     * map options.
     *
     * @param string $xml The XML to validate
     * @param blocks_intelligent_learning_model_service_abstract $service An instance of a subclass of this service model
     * @return array
     */
    public function validate_xml($xml, $service) {
        global $CFG;

        $schema = <<<XML
<?xml version="1.0"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
    <xs:element name="data">
        <xs:complexType>
            <xs:sequence>
                <xs:element name="datum" minOccurs="1" maxOccurs="1">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="mapping" minOccurs="1" maxOccurs="unbounded">
                              <xs:complexType>
                                <xs:simpleContent>
                                  <xs:extension base="xs:string">
                                    <xs:attribute name="name" type="xs:string" use="required"/>
                                  </xs:extension>
                                </xs:simpleContent>
                              </xs:complexType>
                            </xs:element>
                        </xs:sequence>
                        <xs:attribute name="action" type="xs:string" use="required"/>
                    </xs:complexType>
                </xs:element>
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>
XML;

        $dom = new DOMDocument();
        $xml = trim(stripslashes($xml));

        set_error_handler(array($this, 'validate_xml_error_handler'));

        $dom->loadXML($xml);
        if (!$dom->schemaValidateSource($schema)) {
            restore_error_handler();

            if (!empty($this->xmlerror)) {
                throw new Exception("The passed XML Schema failed to validate with error: $this->xmlerror");
            } else {
                throw new Exception('The passed XML Schema failed to validate');
            }
        }
        unset($dom);
        restore_error_handler();

        $xml = simplexml_load_string($xml);

        $action = (string) $xml->datum['action'];
        $action = clean_param($action, PARAM_ACTION);

        $serviceactions = $service->get_actions();

        if (!empty($serviceactions) and !in_array($action, $serviceactions)) {
            throw new Exception("Invalid action found: $action. Valid actions: ".implode(', ', $serviceactions));
        }

        $data = array();
        foreach ($xml->datum->mapping as $mapping) {
            $data[(string) $mapping['name']] = (string) $mapping;
        }

        $servicemappings   = $service->get_mappings();
        $servicemapoptions = $service->get_mapoptions();

        if (!empty($servicemappings)) {
            $newdata = array();
            foreach ($servicemappings as $mapping) {
                if (array_key_exists($mapping, $data)) {
                    if (!empty($servicemapoptions[$mapping]['type'])) {
                        $newdata[$mapping] = clean_param($data[$mapping], $servicemapoptions[$mapping]['type']);
                    } else {
                        $newdata[$mapping] = $data[$mapping];
                    }
                }
            }
            $data = $newdata;
        }
        if (!empty($servicemapoptions)) {
            foreach ($servicemapoptions as $field => $options) {
                // Check to see if the field is required and is empty.
                if (!empty($options['required']) and (!isset($data[$field]) or (empty($data[$field]) and $data[$field] != 0))) {
                    $mapstrs = array();
                    foreach ($servicemappings as $mapping) {
                        $mapstr = "$mapping";
                        if (!empty($servicemapoptions[$mapping]['required'])) {
                            $mapstr .= ' (REQUIRED)';
                        }
                        $mapstrs[] = $mapstr;
                    }
                    throw new Exception('Invalid data sets were found (count = 1).  Either no data was mapped properly or required mappings were missing. '.
                                                     'Was looking for these mappings: '.implode(', ', $mapstrs));
                }
            }
        }
        return array($action, $data);
    }

    /**
     * XML Validation Error handler
     *
     * @param int $errno Error number
     * @param string $errstr Error description
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return boolean
     */
    protected function validate_xml_error_handler($errno, $errstr, $errfile, $errline) {
        $this->xmlerror .= " [$errno] ".strip_tags($errstr);
        return true;
    }
}
