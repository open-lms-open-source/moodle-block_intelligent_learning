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
 * @copyright Copyright (c) 2018 Ellucian
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Ellucian
 */

/**
 * Web Services Rest Course Categories
 *
 * @author Ellucian
 * @package block_intelligent_learning
 */

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once('../../../config.php');
require($CFG->dirroot.'/local/mr/bootstrap.php');

require_once($CFG->dirroot.'/blocks/intelligent_learning/model/response.php');
require_once("$CFG->dirroot/blocks/intelligent_learning/model/service/course_categories.php");

$config = get_config('blocks/intelligent_learning');

$validator = new Zend_Validate();
$validator->addValidator(new mr_server_validate_token($config->webservices_token))
->addValidator(new mr_server_validate_method())
->addValidator(new mr_server_validate_ip($config->webservices_ipaddresses));

$server = new mr_server_rest('blocks_intelligent_learning_model_service_course_categories', 'blocks_intelligent_learning_model_response', $validator);

$server->handle();
