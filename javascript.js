/**
 * Datatel Grades block main view
 *
 * @author Sam Chaffee
 * @package block_intelligent_learning
 **/

M.block_intelligent_learning = M.block_intelligent_learning || {};

M.block_intelligent_learning.init_gradematrix = function(Y, grades) {

    var unsavedData = false;
    var menuNode = Y.one('div.block-ilp-groupselector select');

    if (menuNode) {

        var form = Y.one('div.block-ilp-groupselector form');

        // the following code block altered from M.util.init_autosubmit
        if (form) {
            //purge the old event listeners
            menuNode.purge();

            // Create a function to handle our change event
            var processchange = function(e, lastindex) {

                if (lastindex != menuNode.get('selectedIndex')) {
                    if (unsavedData) {
                        // User has unsaved data - confirm they want to change group and lose grades
                        if (confirm(M.str.block_intelligent_learning['confirmunsaveddata'])) {
                            this.submit();
                        } else {
                            // User canceled the group change - change select box back to old value
                            menuNode.set('selectedIndex', lastindex);
                        }
                    } else {
                        // No unsaved changes - continue to change group
                       this.submit();
                    }
                }
            };
            // Attach the change event to the keypress, blur, and click actions.
            // We don't use the change event because IE fires it on every arrow up/down
            // event.... usability
            Y.on('key', processchange, menuNode, 'press:13', form, menuNode.get('selectedIndex'));
            menuNode.on('blur', processchange, form, menuNode.get('selectedIndex'));
            //little hack for chrome that need onChange event instead of onClick - see MDL-23224
            if (Y.UA.webkit) {
                menuNode.on('change', processchange, form, menuNode.get('selectedIndex'));
            } else {
                menuNode.on('click', processchange, form, menuNode.get('selectedIndex'));
            }
        }
    }

    //add a simple listener to all input
    var inputNodes = Y.all('.block-ilp-td input');
    if (inputNodes) {
        inputNodes.on('change', function() {
            unsavedData = true;
        });
    }

    // Event listener function for onchange of populate grades select menu
    var populateGrades = function (e) {

        var mt = this.get('value');

        if (mt == 0) {
            return;
        }
        var el = document.getElementsByTagName('input');
        for (var i = 0; i < el.length; i++) {
            if (el[i].type == 'text' && el[i].value == "" && !el[i].disabled) {
                var parts = el[i].id.split('_');
                if (parts[0] == mt && grades[parts[1]] != undefined) {
                    el[i].value = grades[parts[1]];
                    unsavedData = true;
                }
            }
        }
    }
    
    if (Y.one('#block-ilp-populategrade') != null) {
        Y.one('#block-ilp-populategrade').on('change', populateGrades);
    }

 // Event listener function for onchange of clear grades link
    var clearGrades = function (e) {

        var gds = this.getAttribute('data-clearfields');

        if (gds === undefined) {
            return;
        }
        var el = document.getElementsByTagName('input');
        for (var i = 0; i < el.length; i++) {
            if (el[i].type == 'text' && el[i].value != "" && !el[i].disabled) {
                var parts = el[i].id.split('_');
                if (gds.indexOf(parts[0]) > -1 && grades[parts[1]] != undefined) {
                    el[i].value = "";
                    unsavedData = true;
                }
            }
        }
        // Reset the "populate" grades dropdown
        document.getElementById('block-ilp-populategrade')[0].selected = true;
    }

    if (Y.one('#block-ilp-cleargrades') != null) {
        Y.one('#block-ilp-cleargrades').on('click', clearGrades);
    }

}