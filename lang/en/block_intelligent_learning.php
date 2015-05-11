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
 * Ellucian Grades block strings
 *
 * @author Sam Chaffee
 * @version $Id$
 * @package block_intelligent_learning
 **/

$string['addcutoff'] = 'Add cutoff';
$string['addedactivity'] = 'Added {$a->modname} by {$a->fullname}';
$string['attendancedaily'] = 'Daily Attendance';
$string['attendancepid'] = 'Attendance process ID';
$string['attendancepiddesc'] = 'Attendance process ID. (Note: This setting applies to Colleague clients only.)';
$string['attendancetab'] = 'Attendance';
$string['backtodatatel'] = 'Back to Portal';
$string['categorycutoff'] = 'Category grade cutoff';
$string['categorycutoffdesc'] = 'You can define one or more cutoff dates, after which midterm grades and final grades will no longer be visible in either the ILP integration block or the grading form. A cutoff date is associated with a course category, and applies to all courses in that category and its subcategories, unless you define a separate cutoff date for a subcategory.';
$string['categorycutoff_help'] = '<p>You can define one or more cutoff dates, after which midterm grades and final grades will no
    longer be visible in either the ILP integration block or the grading form. A cutoff date is associated with a course category,
    and applies to all courses in that category and its subcategories, unless you define a separate cutoff date for a subcategory.</p>

<p>Example: A category has been defined for each term, with subcategories for each department. Course sites exist under the categories
    SP2010/English, SP2010/Math, and SP2010/History. A cutoff date defined for the SP2010 category would apply to all courses in those
    categories. However, a separate cutoff date could be defined for the SP2010/Math category, and would apply to all courses in that category.</p>

<p>To add a new cutoff: Choose the course category, enter a date, and click Add cutoff.</p>
<p>To delete an existing cutoff: Select the Delete check box next to that cutoff and click Save Changes at the bottom of the form.</p>';
$string['checkatleastone'] = 'Please check the checkbox next to a user\'s name to update their grades';
$string['configure'] = 'Configure';
$string['confirmunsaveddata'] = 'You are about to change the group and will lose unsaved data.  Are you sure you want to continue without saving?';
$string['couldnotsave'] = 'A record could not be saved';
$string['currentgrade'] = 'Current Grade';
$string['dailyattendancelink'] = 'Daily attendance link';
$string['dailyattendancelinkdesc'] = 'Display daily attendance links? (Note: This setting applies to Colleague clients only.)';
$string['datatelwebserviceendpoints'] = 'Ellucian Web Service Endpoints';
$string['dateformat'] = 'Date format';
$string['dateformatdesc'] = 'Date format for date entry boxes. YYYY represents the four digit year, MM represents the two digit month and DD represents the two digit day.';
$string['deletedactivity'] = 'Deleted {$a->modname} by {$a->fullname}';
$string['expiredate'] = 'Expire Date';
$string['extraletters'] = 'Additional Grade Letters';
$string['extralettersdesc'] = 'Enter additional letter grades, separated by commas, that can be submitted for mid-term or final grades. The grading forms will accept these grades and the grades in the Moodle grade scheme for the course.';
$string['failedtoconvert'] = 'Failed to convert date entered to UNIX timestamp for {$a->date}.  Valid format: {$a->format}';
$string['finalgrade'] = 'Final Grade';
$string['finalgrades'] = 'Final Grades';
$string['finalgradestab'] = 'Final Grades';
$string['finalgrades_help'] = '<p>The Final Grades form allows a user to edit the students\' final grades and last date of attendance or the never attended flag.  Each student\'s current grade (decimal and letter) is displayed after their name.</p>';
$string['gotogrades'] = 'Grader Report';
$string['gradebookapp'] = 'Gradebook Application';
$string['gradebookappdesc'] = 'Application handling the grade reporting';
$string['gradebookapperror'] = 'The Gradebook Application is not set to Moodle.';
$string['gradelock'] = 'Lock Grades';
$string['gradevalidatelocalgradescheme'] = 'Validate grades against Moodle grade scheme?';
$string['gradevalidatelocalgradeschemedesc'] = 'If you select "Yes," midterm and final grade entries will be '.
    'validated against BOTH the course grade scheme specified in Moodle and the course grade scheme specified '.
    'in the SIS. If you select "No," midterm and final grade entries will be validated only against the course '.
    'grade scheme specified in the SIS.';
$string['gradelockdesc'] = 'Allow faculty to modify final grades after submission?';
$string['gradematrixtab'] = 'Midterm/Final Grades';
$string['gradessubmitted'] = 'Grades submitted';
$string['helptextfinalgrades'] = '';
$string['helptextlastattendance'] = '';
$string['helptextmidtermgrades'] = '';
$string['helptextretentionalert'] = '';
$string['ilpgradebook'] = 'ST Gradebook';
$string['ilpst'] = 'SIS';
$string['ilpurl'] = 'Portal Url';
$string['ilpurldesc'] = 'URL to your portal site';
$string['intelligent_learning:edit'] = 'Edit';
$string['intelligent_learning:addinstance'] = 'Add a new ILP Integration block';
$string['invalidday'] = 'The entered date has an invalid day: {$a->date}.  Valid format: {$a->format}';
$string['invalidmonth'] = 'The entered date has an invalid month: {$a->date}.  Valid format: {$a->format}';
$string['invalidyear'] = 'The entered date has an invalid year: {$a->date}.  Valid format: {$a->format}';
$string['lastattendance'] = 'Last Date of Attendance';
$string['lastattendancetab'] = 'Last Date of Attendance';
$string['lastattendancetableheader'] = 'Last Date of Attendance';
$string['lastattendance_help'] = '<p>The Last Date of Attendance form allows a user to edit the students\' last date of attendance.</p>';
$string['ldasubmitted'] = 'LDA Submitted';
$string['incompletefinalgrade'] = 'Incomplete Final Grade';
$string['lettergradetoolong'] = 'The grade letter \"{$a}\" must be less than three characters long.';
$string['midterm'] = 'Midterm {$a}';
$string['midtermgradecolumns'] = 'Midterm Grades';
$string['midtermgradecolumnsdesc'] = 'Number of midterm grades to display';
$string['midtermgrades'] = 'Midterm Grades';
$string['midtermgradestab'] = 'Midterm Grades';
$string['midtermgrades_help'] = '<p>The Midterm Grades form allows a user to edit the students\' midterm grades and last date of attendance.  Each student\'s current grade (decimal and letter) is displayed after their name.</p>';
$string['missingmonthdayoryear'] = 'The entered date is missing either day, month or year: {$a->date}. Valid format: {$a->format}';
$string['moodle'] = 'Moodle';
$string['needsadminsetup'] = 'The ILP Integration block needs to be configured by an administrator';
$string['neverattended'] = 'Never Attended';
$string['neverattenderror'] = 'Both the last date of attendance and the never attend flag cannot be set at the same time';
$string['nocheckboxwarning'] = 'No checkboxes were checked.  Please check the checkbox in the rows that should be saved.';
$string['nogradebookusers'] = 'There are no users in this course with gradebook roles';
$string['nogradebookusersandgroups'] = 'There are no users in this course with gradebook roles and this group assignment';
$string['notavailable'] = 'The grading period has expired.';
$string['notvalidgrade'] = '{$a} is not a valid grade for this class.';
$string['outsideoflimits'] = 'Failed to convert date entered for {$a->date}.  Valid format: {$a->format}';
$string['pluginname'] = 'ILP Integration';
$string['populatefinalgrade'] = '--Select the column to populate--';
$string['populatemidterm'] = '--Select the column to populate--';
$string['populatefinalgradelabel'] = 'Populate final grade from current grade';
$string['populatemidtermlabel'] = 'Populate midterm grade from current grade';
$string['cleargradeslabel'] = 'Clear grades on form';
$string['cleargradesexplanationfinal'] = 'After the values are cleared, you can repopulate final grades from current grades.';
$string['cleargradesexplanationmidterm'] = 'After the values are cleared, you can repopulate midterm grades from current grades.';
$string['cleargradesdescription'] = 'Click "Clear grades on form" to start over';
$string['retentionalert'] = 'Retention Alert';
$string['retentionalertlink'] = 'Retention alert link';
$string['retentionalertlinkdesc'] = 'Display retention alert links? (Note: This setting applies to Colleague clients only.)';
$string['retentionalertlinkerror'] = 'Retention alert has not been enabled';
$string['retentionalertpid'] = 'Retention alert process ID';
$string['retentionalertpiddesc'] = 'Retention alert process ID. (Note: This setting applies to Colleague clients only.)';
$string['retentionalerttab'] = 'Retention Alert';
$string['retentionalert_help'] = '<p>Click the Retention Alert link next to a student\'s name to enter retention information for that student in WebAdvisor. Clicking the link will open a new browser window with the appropriate Retention Alert page displayed in WebAdvisor in the Colleague Portal.</p>';
$string['showlastattendance'] = 'Show last attendance';
$string['showlastattendancedesc'] = 'Display Last Date of Attendance links?';
$string['showneverattended'] = 'Show Never Attended column in grading forms';
$string['showneverattendeddesc'] = 'Display Never Attended column in grading forms? (Note: this setting applies to ' .
    'Colleague clients only. Banner clients should set this value to No.) Note that you can separately specify whether the Last Date of Attendance '.
    'column is displayed using the "Show last attendance" setting.';
$string['showdefaultincomplete'] = "Show Incomplete Final Grade column in grading forms?";
$string['showdefaultincompletedesc'] = "Show a column for Incomplete Final Grade that can be used by faculty members when entering an incomplete grade? (Note: This setting applies to Banner clients only.)";
$string['stgradebookpid'] = 'ST Gradebook process ID';
$string['stgradebookpiddesc'] = 'ST Gradebook process ID. (Note: This setting applies to Colleague clients only.)';
$string['submitgrades'] = 'Submit Grades';
$string['submitlda'] = 'Submit LDA';
$string['expirelabel'] = 'Final grade expiration/extension date label';
$string['expirelabeldesc'] = 'Select the column heading to be displayed on the final grades form for the expiration '.
    'or extension date for incomplete final grades. The column heading should be consistent with the terminology for that date in your SIS.';
$string['expirelabel_expiredate'] = 'Expire Date';
$string['expirelabel_extensiondate'] = 'Extension Date';
$string['updatedactivity'] = 'Updated {$a->modname} by {$a->fullname}';
$string['webserviceendpoints'] = 'Web Service Endpoints';
$string['webservices_ipaddresses'] = 'IP Addresses';
$string['webservices_ipaddressesdesc'] = 'IP addresses of servers that are permitted to access Moodle through ILP services. IP addresses can be a comma separated list of subnet definitions. Subnet definitions can be in one of the following three formats:<ol><li>xxx.xxx.xxx.xxx/xx</li><li>xxx.xxx</li><li>xxx.xxx.xxx.xxx-xxx (A range)</li></ol>';
$string['webservices_token'] = 'Token';
$string['webservices_tokendesc'] = 'Token that should be passed along with web service requests';
$string['colleaguesection'] = 'Colleague Settings';
$string['bannersection'] = 'Banner Settings';
$string['maxnumberofdays'] = 'Maximum number of days to display classes';
$string['maxnumberofdaysdesc'] = 'Maximum number of days to include classes, from a class start date, in requests by external applications';
$string['metalink_label'] = 'Section enrollment';
$string['livegrades'] = 'Live Grades Synchronization (ILP 4.2 or higher)';
$string['ilpapi_url'] = 'ILP API Url';
$string['ilpapi_urldesc'] = 'IMPORTANT: Only use this setting if you are running ILP 4.2 or higher. Enter the URL of the ILP services website.';
$string['ilpapi_connectionid'] = 'ILP API Connection Id';
$string['ilpapi_connectioniddesc'] = 'IMPORTANT: Only use this setting if you are running ILP 4.2 or higher. Enter the ILP API connection ID.';
$string['ilpapi_connectionpassword'] = 'ILP API Connection Password';
$string['ilpapi_connectionpassworddesc'] = 'IMPORTANT: Only use this setting if you are running ILP 4.2 or higher. Enter the ILP API connection password.';
$string['ilpapi_error_student'] = 'Error updating data for student {$a}';
$string['ilpapi_error'] = 'Some grades could not be updated. Please correct the errors listed above and resubmit.';
$string['ilpapi_service_error'] = 'Error communicating with grades service. Please contact your administrator for assistance.';
$string['ilpapi_generic_error'] = 'Unable to update grades. Please contact your administrator for assistance.';
$string['ilpapi_issslcaauthority'] = 'Is the ILP Services site SSL certificate issued by a Certificate Authority (CA)?';
$string['ilpapi_certpath'] = 'Optional: path to CA Certificate Bundle';
$string['ilpapi_sslcawarning'] = 'IMPORTANT: Only use this setting if you are running ILP 4.2 or higher. Without a certificate issued by a CA, the communications between Moodle and ILP are ' .
        'less secure since Moodle will trust any certificate it receives.';
$string['ilpapi_certexplanation'] = 'IMPORTANT: Only use this setting if you are running ILP 4.2 or higher. If the SSL certificate used by the ILP Services site was issue by a CA, you may use this field to ' .
        'specify the location of the CA certificate bundle in your system. In most cases you will not need to specify ' .
        'the location of a certificate bundle if a default bundle is configured system-wide. However, under ' .
        'certain configurations, a default bundle is not available and the relative path to a certificate bundle must ' .
        'be specified. If your Moodle site is hosted by Moodlerooms, leave this field blank.';
