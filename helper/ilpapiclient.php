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
 */

/**
 * ILP API client
 *
 **/

class ilpapiclient {
    public function __construct() {
    }

    private $_service;

    /**
     * Gets all properties required to perform a service call to ILP
     *
     * @return object - service
     */
    public function init() {
        $this->_service = new stdClass();
        $this->_service->baseurl = trim(get_config('blocks/intelligent_learning', 'ilpapi_url'));
        $connectionid = trim(get_config('blocks/intelligent_learning', 'ilpapi_connectionid'));
        $connectionpassword = trim(get_config('blocks/intelligent_learning', 'ilpapi_connectionpassword'));
        $this->_service->auth_header = $this->build_service_header($connectionid, $connectionpassword);
        $this->_service->is_ca_authority = trim(get_config('blocks/intelligent_learning', 'ilpapi_issslcaauthority'));
        $this->_service->cert_path = trim(get_config('blocks/intelligent_learning', 'ilpapi_certpath'));

        $this->_service->curldefaults = $this->get_curl_defaults();
    }

    /**
     * Build the header information for service calls (authorization string)
     *
     * @return string - base-64 header
     */
    private function build_service_header($connectionid, $connectionpwd) {
        $credentials = base64_encode($connectionid . ":" . $connectionpwd);
        $header = "Basic " . $credentials;
        // debugging("Service header: ". $header, DEBUG_DEVELOPER);
        return $header;
    }

     /**
      * Get the defaults to be using when connecting to ILP services
      *
      * @param $service_url - the url to connect to
      * @param $use_ssl - whether to use SSL (https)
      * @param $ca_authority - 1 if using a CA authority certificate
      * @param $cert_path - path to the certificate to be used for the connection
      *
      * @return array defaults
      */
    public function get_curl_defaults() {
        $options = array(
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => 1,
                CURLOPT_RETURNTRANSFER => true
        );
        $options[CURLOPT_SSL_VERIFYPEER] = $this->_service->is_ca_authority;
        $options[CURLOPT_SSL_VERIFYHOST] = 2;

        if ($this->_service->cert_path != "") {
            $options[CURLOPT_CAINFO] = $this->_service->cert_path;
        }

        return $options;
    }

    /**
     * Send a request to the API
     *
     * @param $service
     * @param $endpoint - api endpoint for this request
     * @param $contents - api request contents, in json format
     *
     * @return json - api response
     */
    public function send_request($endpoint, $contents) {

        $options = array();
        $ch = curl_init();
        $defaults = $this->_service->curldefaults;

        curl_setopt_array($ch, ($options + $defaults));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '. $this->_service->auth_header, 'Content-Type: application/json', 'Accept: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
        $requesturl = $this->_service->baseurl . "/" . $endpoint;
        curl_setopt($ch, CURLOPT_URL, $requesturl);

        // Send the request.

        if (!$serviceresponse = curl_exec($ch)) {
            debugging("Error calling ILP service at $requesturl . $contents", DEBUG_NORMAL);
            debugging("Error: " . curl_error($ch), DEBUG_NORMAL);
            throw error ("Unable to update grades. Please contact your system administrator.");
        } else {
            $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpstatus == 200) {
                $response = json_decode($serviceresponse, true);
                $results = $this->get_service_results($response);
                debugging("Response from ILP service: " . $serviceresponse, DEBUG_NORMAL);
            } else {
                debugging("Error processing ILP service. Return code $httpstatus.", DEBUG_NORMAL);
                debugging("Service response: " . $serviceresponse, DEBUG_NORMAL);
                throw error("Unable to update grades. Please contact your system administrator");
            }
        }

        curl_close($ch);

        return $results;
    }

    /**
     * A service response object
     * @response array - Array of response messages
     */
    public function get_service_results($response) {
        /*
          A sample response:
            {"status": "failure",  "messages": [
                {
                    "message": "Midterm grade 5 (B) may not be deleted.",
                        "data": {
                            "targetSis": "COLLEAGUE",
                            "studentId": "0011184",
                            "status": "failure",
                            "property": "MidtermGrade5"
                            }
                     },
                {
                    "message": "A final grade of B does not allow an expiration date.",
                        "data": {
                            "targetSis": "COLLEAGUE",
                            "studentId": "0011184",
                            "status": "failure",
                            "property": "FinalGrade"
                        }
                }]}
            */

        $results = new stdClass();
        $results->status = (string)$response['status'];
        $results->messages = $response['messages'];
        $results->errors = array();
        $results->successes = 0;

        if (is_array($results->messages)) {
            foreach ($results->messages as $ms) {
                /*
                 "message": "A final grade of B does not allow an expiration date.",
                 "data": {
                         "targetSis": "COLLEAGUE",
                          "studentId": "0011184",
                          "status": "failure",
                          "property": "FinalGrade"
                          }
                 */
                if (($results->status == 'failure') and (is_null($ms['data']))) {
                    $error = new stdClass();
                    $error->message = (string)$ms["message"];
                    array_push($results->errors, $error);
                } else {
                    $data = $ms['data'];
                    if ((string)$data["status"] == 'success') {
                        $results->successes++;
                    }
                    if ((string)$data["status"] == 'failure') {
                        $error = new stdClass();
                        $error->message = (string)$ms["message"];
                        $error->uidnumber = (string)$data["studentId"];
                        switch ((string)$data['property']) {
                            case 'MidTermGrade':
                                $error->property = 'mt1';
                                break;
                            case 'MidtermGrade1':
                                $error->property = 'mt1';
                                break;
                            case 'MidtermGrade2':
                                $error->property = 'mt2';
                                break;
                            case 'MidtermGrade3':
                                $error->property = 'mt3';
                                break;
                            case 'MidtermGrade4':
                                $error->property = 'mt4';
                                break;
                            case 'MidtermGrade5':
                                $error->property = 'mt5';
                                break;
                            case 'MidtermGrade6':
                                $error->property = 'mt6';
                                break;
                            case 'FinalGrade':
                                $error->property = 'finalgrade';
                                break;
                            case 'FinalGradeExpirationDate':
                                $error->property = 'expiredate';
                                break;
                            case 'LastAttendanceDate':
                                $error->property = 'lastaccess';
                                break;
                            case 'NeverAttended':
                                $error->property = 'neverattended';
                                break;
                            default:
                                $error->property = (string)$data['property'];
                        }
                        array_push($results->errors, $error);
                    }
                }
            }
        }
        return $results;
    }

    /**
     *
     * Builds the json request payload to update grades in the SIS     
     * @param string $facultyid - id of the faculty member posting grades
     * @param array $sisgrades - array of grades to update
     */
    public static function build_grades_request_payload($facultyid, $sisgrades) {
        /*
         {
         "ModifiedBy":"0029382",
         "StudentGrades": [
             {
             "CourseId":"22938",
             "StudentId":"0011184",
             "MidtermGrade1":"A",
             "MidtermGrade2":"B",
             "MidtermGrade3":"B",
             "MidtermGrade4":"A",
             "MidtermGrade5":"B",
             "MidtermGrade6":"B",
             "FinalGrade":"B",
             "FinalGradeExpirationDate":"",
             "ClearFinalGradeExpirationDateFlag":"false",
             "LastAttendanceDate":"",
             "ClearLastAttendanceDateFlag":"false",
             "NeverAttended" : "true",
             "DefaultIncompleteGrade": "I"
             },
             {
             "CourseId":"22938",
             "StudentId":"0011185",
             "MidtermGrade1":"B",
             ...
             }]}
         */

        $json = '{
                  "ModifiedBy":"' . $facultyid . '"' .
                  ', "StudentGrades": [';

        foreach ($sisgrades as $grades) {
            $json .= '{"CourseId":"' . $grades->cidnumber . '"';
            $json .= ',"StudentId":"' . $grades->uidnumber . '"';

            // Only send updates for fields that have changed.
            if (!is_null($grades->finalgrade)) {
                $json .= ', "FinalGrade":"' . $grades->finalgrade. '"';
            }

            if (!is_null($grades->mt1)) {
                $json .= ', "MidtermGrade1":"' . $grades->mt1. '"';
            }

            if (!is_null($grades->mt2)) {
                $json .= ', "MidtermGrade2":"' . $grades->mt2. '"';
            }

            if (!is_null($grades->mt3)) {
                $json .= ', "MidtermGrade3":"' . $grades->mt3. '"';
            }

            if (!is_null($grades->mt4)) {
                $json .= ', "MidtermGrade4":"' . $grades->mt4. '"';
            }

            if (!is_null($grades->mt5)) {
                $json .= ', "MidtermGrade5":"' . $grades->mt5. '"';
            }

            if (!is_null($grades->mt6)) {
                $json .= ', "MidtermGrade6":"' . $grades->mt6. '"';
            }

            if (!is_null($grades->neverattended)) {
                $json .= ', "NeverAttended" : ' . ($grades->neverattended == '0' ? '"false"' : '"true"');
            }

            if (!is_null($grades->incompletefinalgrade)) {
                $json .= ',"DefaultIncompleteGrade": "' . $grades->incompletefinalgrade . '"';
            }

            if (!is_null($grades->expiredate)) {
                $json .= ', "FinalGradeExpirationDate":"' . date("c", $grades->expiredate) . '"';
            }

            if ($grades->clearexpireflag) {
                $json .= ', "ClearFinalGradeExpirationDateFlag":' . ($grades->clearexpireflag == '0' ? '"false"' : '"true"');
            }

            if (!is_null($grades->lastaccess)) {
                $json .= ', "LastAttendanceDate":"' . date("c", $grades->lastaccess) . '"';
            }

            if ($grades->clearlastattendflag) {
                $json .= ', "ClearLastAttendanceDateFlag":' . ($grades->clearlastattendflag == '0' ? '"false"' : '"true"');
            }

            $json .= '},';
        }
        $json = rtrim($json, ",") . "]}";
        debugging("Will send the following grades update request to the SIS: " . $json, DEBUG_NORMAL);
        return $json;
    }

}
