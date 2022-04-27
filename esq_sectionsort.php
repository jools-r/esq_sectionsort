<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'esq_sectionsort';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '2.04';
$plugin['author'] = '';
$plugin['author_uri'] = 'http://textpattern.org';
$plugin['description'] = 'Custom sorting of sections and categories';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '3';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (txpinterface == "admin") {
    register_callback("esq_sectionsort_section_js", "section_ui", "multi_edit_options");
    register_callback("esq_sectionsort_category_js", "category_ui", "multi_edit_options");
    register_callback("esq_sectionsort_prefs", "plugin_prefs.esq_sectionsort");
    register_callback("esq_sectionsort_setup", "plugin_lifecycle.esq_sectionsort");
    add_privs("plugin_prefs.esq_sectionsort", "1,2,3,4,5,6");
}

function esq_sectionsort_section_js()
{
    echo <<<EOB
<script src="index.php?event=plugin_prefs.esq_sectionsort&step=get_js&js=sort_elements"></script>
<script src="index.php?event=plugin_prefs.esq_sectionsort&step=get_js&js=section"></script>
<style>
#section_form tbody tr td:first-child {
    cursor: move;
}
</style>
EOB;
}

function esq_sectionsort_category_js()
{
    if (defined("esq_sectionsort_section_js_run")) {
        return;
    }
    define("esq_sectionsort_section_js_run", 1);
    echo <<<EOB
<script src="index.php?event=plugin_prefs.esq_sectionsort&step=get_js&js=sort_elements"></script>
<script src="index.php?event=plugin_prefs.esq_sectionsort&step=get_js&js=category"></script>
<style>
td.categories p:first-child {
    border-top: 0;
}
#category_article_form > .categorysort:first-child,
#category_image_form > .categorysort:first-child,
#category_file_form > .categorysort:first-child,
#category_link_form > .categorysort:first-child {
    border-top: 1px solid #ddd;
}
#category_article_form p .handle,
#category_image_form p .handle,
#category_file_form p .handle,
#category_link_form p .handle {
    cursor: move;
}
</style>
EOB;
}

function esq_sectionsort_prefs($event = "", $step = "", $message = "")
{
    if ($step == "revertDB") {
        global $txp_user;
        if (safe_field("privs", "txp_users", 'name=\'' . doSlash($txp_user) . '\'') != "1") {
            exit(pageTop("Restricted") . '<p style="margin-top:3em;text-align:center">' . gTxt("restricted_area") . "</p>");
        }
    }
    switch ($step) {
        case "get_js":
            esq_sectionsort_js(gps("js"));
            break;
        case "put":
            esq_sectionsort_js(gps("type"));
            break;
        default:
            $step = "default";
        case "revertDB":
            $step = "esq_sectionsort_" . $step;
            $step_result = $step();
            pagetop("esq_sectionsort Options", $step_result);
            break;
    }
    echo '<div style="text-align: center;"><div style="text-align: left; margin: 0 auto; /*width: 900px;*/">';
    if (esq_sectionsort_checkDB() == true) {
        echo form(
            '<input type="hidden" name="event" value="plugin_prefs.esq_sectionsort" />' .
                '<input type="hidden" name="step" value="revertDB" />' .
                fInput("submit", "submit", "Revert DB & Disable", "publish") .
                "<p>This will undo changes made by <strong>esq_sectionsort</strong> to your Textpattern database, and disable the plugin.</p>"
        );
        echo '<p>This should only be done before uninstalling <strong>esq_sectionsort</strong>, as it removes all sort preferences. This will cause errors in your Pages and Forms where you have used <code>sort="sectionsort"</code> or <code>sort="categorysort"</code>.</p>';
        echo '<p>Reverting the database is not necessary before uninstalling the plugin - it has just been included for those who are meticulous about their DB structure, and don\'t want superfluous columns lying around.</p>';
        echo "<p>If you accidentally revert the database, you can re-enable the plugin to set the database up again for use with <strong>esq_sectionsort</strong>, but your sort preferences will have been lost.</p>";
        echo '<p>Note: Reverting the database effectively runs the MySQL command<br><code>ALTER TABLE `txp_section` DROP `sectionsort`; ALTER TABLE `txp_category` DROP `categorysort`;</code>, which removes the columns \'sectionsort\' and \'categorysort\' from the tables \'txp_section\' and \'txp_category\' (respectively) in your Textpattern database.';
        echo "This plugin was designed for Textpattern 4.5.0 and tested up to 4.5.4; although unlikely, future versions may actually make use of columns with this name. As such, if you are uninstalling this plugin because it has been made redundant by a feature in a new Textpattern release, please be careful.</p>";
    } else {
        if ($step == "esq_sectionsort_revertDB" && !is_array($step_result)) {
            echo "<p>Your Textpattern database has been reverted, and <strong>esq_sectionsort</strong> has (hopefully) been disabled. You may now delete the plugin.</p>";
            echo "<p>If you have done this by accident, enabling <strong>esq_sectionsort</strong> will restore the required column in your Textpattern database. Your sort preferences have been lost however.</p>";
        } else {
            echo "<p>Looks like something went wrong. This is probably because you hit the refresh button in your browser - please avoid doing so.</p>";
            echo "<p>Alternatively your Textpattern database may not have been set up properly during the <strong>esq_sectionsort</strong> installation process. Try checking MySQL user permissions, then disable and re-enable the plugin.</p>";
        }
    }
    echo "</div></div>";
}

function esq_sectionsort_setup($event, $step)
{
    switch ($step) {
        case "installed":
        case "enabled":
            if (esq_sectionsort_checkDB()) {
                if (!esq_sectionsort_checkDB_update()) {
                    return esq_sectionsort_setupDB_update();
                }
            } else {
                return esq_sectionsort_setupDB(ucwords($step));
            }
            break;
    }
    return "";
}

function esq_sectionsort_default()
{
    return "";
}

function esq_sectionsort_checkDB()
{
    $columns = getRows("SHOW COLUMNS FROM " . safe_pfx("txp_section"));
    foreach ($columns as $column => $columnData) {
        $columns[$columnData["Field"]] = "";
        unset($columns[$column]);
    }
    return isset($columns["sectionsort"]);
}

function esq_sectionsort_checkDB_update()
{
    $columns = getRows("SHOW COLUMNS FROM " . safe_pfx("txp_category"));
    foreach ($columns as $column => $columnData) {
        $columns[$columnData["Field"]] = $columnData["Type"];
        unset($columns[$column]);
    }
    return isset($columns["categorysort"]) && preg_match("/^tinyint/", $columns["categorysort"]);
}

function esq_sectionsort_setupDB($lifecycle)
{
    if (safe_alter("txp_section", "ADD `sectionsort` TINYINT NULL") && safe_alter("txp_category", "ADD `categorysort` TINYINT NULL")) {
        return $lifecycle . " <strong>esq_sectionsort</strong> and DB setup OK.";
    } else {
        return [$lifecycle . " <strong>esq_sectionsort</strong>. DB setup failed.", E_ERROR];
    }
}

function sectionsort_convert($a, $b)
{
    $a = $a["sectionsort"];
    $b = $b["sectionsort"];
    if ($a === $b) {
        return 0;
    }
    return $a < $b ? -1 : 1;
}

function esq_sectionsort_setupDB_update()
{
    $current_sectionsort = safe_rows("name, sectionsort", "txp_section", "1=1");
    usort($current_sectionsort, "sectionsort_convert");
    foreach ($current_sectionsort as $sort => $section_data) {
        safe_update("txp_section", 'sectionsort = \'' . $sort . '\'', 'name = \'' . $section_data["name"] . '\'');
    }
    if (safe_alter("txp_section", "MODIFY `sectionsort` TINYINT NULL") && safe_alter("txp_category", "ADD `categorysort` TINYINT NULL")) {
        return "<strong>esq_sectionsort</strong> and DB updated OK.";
    } else {
        return ['<strong>esq_sectionsort</strong> updated, but DB update failed. Try adjusting a section\'s order, then disable and enable the plugin again.', E_ERROR];
    }
}

function esq_sectionsort_revertDB()
{
    if (safe_alter("txp_section", "DROP `sectionsort`") && safe_alter("txp_category", "DROP `categorysort`")) {
        if (safe_update("txp_plugin", "status = 0", 'name = \'esq_sectionsort\'')) {
            return "DB reverted and <strong>esq_sectionsort</strong> disabled OK.";
        } else {
            return ["DB reverted OK. Failed to disabled <strong>esq_sectionsort</strong>.", E_ERROR];
        }
    } else {
        return ["DB revert failed. No status change.", E_ERROR];
    }
}

function esq_sectionsort_js($js)
{
    header("Content-Type: text/javascript");
    switch ($js) {
        case "section":
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                header("Content-Type: application/json");
                $success = true;
                foreach ($_POST as $section => $sort) {
                    if (!safe_update("txp_section", 'sectionsort=\'' . doSlash($sort) . '\'', 'name=\'' . doSlash($section) . '\'')) {
                        $success = false;
                    }
                }
                echo json_encode(["success" => $success]);
            } else {
                echo "var sectionsort = [];" . "\n";
                foreach (safe_rows("name, sectionsort", "txp_section", "1=1") as $row) {
                    $section = $row["name"];
                    $sort = $row["sectionsort"];
                    if (!isset($sort)) {
                        $sort = 0;
                    }
                    echo 'sectionsort[\'txp_section_' . addslashes($section) . '\'] = \'' . addslashes($sort) . '\';' . "\n";
                }
                echo <<<EOB
$(function () {
    $(".txp-list th").removeClass("asc").removeClass("desc");
    $("#section_container thead tr")
        .prepend('<th style="width: 30px;">Sort</th>')
        .find("th")
        .each(function () {
            var th = $(this);
            th.html(th.text());
        });
    $("#section_container table")
        .find("tbody tr")
        .prepend("<td></td>")
        .not("#txp_section_default")
        .appendTo("#section_container tbody")
        .sortElements(function (a, b) {
            var a_sort = sectionsort[$(a).attr("id")];
            var b_sort = sectionsort[$(b).attr("id")];
            if (a_sort == b_sort) {
                return 0;
            }
            return a_sort > b_sort ? 1 : -1;
        })
        .parent()
        .sortable({
            items: "tr:not(:first-child)",
            handle: "td:first-child",
            start: function (event, ui) {
                if (!$("#cb_toggle_section_detail").is(":checked")) {
                    ui.placeholder.find(":nth-child(n+7)").hide();
                }
            },
            stop: function () {
                var sectionsort = {};
                $(this)
                    .find("tr")
                    .each(function () {
                        var tr = $(this);
                        sectionsort[tr.attr("id").replace("txp_section_", "")] = tr.index();
                    });
                var set_message = function (message, type) {
                    $("#messagepane").html('<span id="message" class="' + type + '">' + message + ' <a href="#close" class="close">&times;</a></span>');
                    $("#message").fadeOut("fast").fadeIn("fast");
                };
                $.ajax("index.php?event=plugin_prefs.esq_sectionsort&step=put&type=section", {
                    type: "POST",
                    data: sectionsort,
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            set_message("Section order saved OK", "success");
                        } else {
                            this.error();
                        }
                    },
                    error: function () {
                        set_message("Section order save failed", "error");
                    },
                });
            },
        })
        .find("tr")
        .not(":first-child")
        .find("td:first-child")
        .html("&uarr;&darr;");
});
EOB;
            }
            break;
        case "category":
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                header("Content-Type: application/json");
                $success = true;
                foreach ($_POST as $category_id => $sort) {
                    if (!safe_update("txp_category", 'categorysort=\'' . doSlash($sort) . '\'', 'id=\'' . doSlash($category_id) . '\'')) {
                        $success = false;
                    }
                }
                echo json_encode(["success" => $success]);
            } else {
                echo "var categorysort = [];" . "\n";
                foreach (safe_rows("id, categorysort", "txp_category", "1=1") as $row) {
                    $category_id = $row["id"];
                    $sort = $row["categorysort"];
                    if (!isset($sort)) {
                        $sort = 0;
                    }
                    echo "categorysort[" . addslashes($category_id) . "] = " . addslashes($sort) . ";" . "\n";
                }
                echo <<<EOB
$(function () {
    var sort_groups = [];
    $("#category_article_form,#category_image_form,#category_file_form,#category_link_form").each(function () {
        $(this)
            .find("p")
            .wrapAll('<div class="categorysort"/>')
            .each(function () {
                var current = $(this);
                var next = current.next("p");
                var group = current;
                var level = Number(current.attr("class").replace("level-", ""));
                var category_id = Number(current.find("input").val());
                current.data({
                    category_id: category_id,
                    categorysort: categorysort[category_id],
                });
                current.prepend('<span class="handle">&uarr;&darr;</span>');
                if (current.data("grouped")) {
                    return;
                }
                while (next.length) {
                    var next_level = Number(next.attr("class").replace("level-", ""));
                    if (next_level <= level) {
                        break;
                    }
                    group = group.add(next);
                    if (next_level == level) {
                        next.data("grouped", true);
                    }
                    next = next.next("p");
                }
                sort_groups.push(group);
            });
    });
    $.each(sort_groups, function () {
        var group = $(this);
        var group_top = group.eq(0);
        group.wrapAll('<div class="categorysort" data-category_id="' + group_top.data("category_id") + '" data-categorysort="' + group_top.data("categorysort") + '" />');
    });
    $(".categorysort > .categorysort")
        .parent()
        .each(function () {
            $(this)
                .sortable({
                    items: "> .categorysort",
                    handle: ".handle",
                    stop: function () {
                        var categorysort = {};
                        var group = $(this).find("> .categorysort");
                        group.each(function () {
                            var el = $(this);
                            categorysort[el.data("category_id")] = el.closest(".category-tree").find(".categorysort").index(el);
                        });
                        var set_message = function (message, type) {
                            $("#messagepane").html('<span id="message" class="' + type + '">' + message + ' <a href="#close" class="close">&times;</a></span>');
                            $("#message").fadeOut("fast").fadeIn("fast");
                        };
                        $.ajax("index.php?event=plugin_prefs.esq_sectionsort&step=put&type=category", {
                            type: "POST",
                            data: categorysort,
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    set_message("Category order saved OK", "success");
                                } else {
                                    this.error();
                                }
                            },
                            error: function () {
                                set_message("Category order save failed", "error");
                            },
                        });
                    },
                })
                .find("> .categorysort")
                .sortElements(function (a, b) {
                    var a_sort = $(a).data("categorysort");
                    var b_sort = $(b).data("categorysort");
                    if (a_sort == b_sort) {
                        return 0;
                    }
                    return a_sort > b_sort ? 1 : -1;
                });
        });
});
EOB;
            }
            break;
        case "sort_elements":
            echo <<<EOB
/**
* jQuery.fn.sortElements
* --------------
* @author James Padolsey (http://james.padolsey.com)
* @version 0.11
* @updated 18-MAR-2010
* --------------
* @param Function comparator:
*   Exactly the same behaviour as [1,2,3].sort(comparator)
*
* @param Function getSortable
*   A function that should return the element that is
*   to be sorted. The comparator will run on the
*   current collection, but you may want the actual
*   resulting sort to occur on a parent or another
*   associated element.
*
*   E.g. $('td').sortElements(comparator, function(){
*      return this.parentNode;
*   })
*
*   The <td>'s parent (<tr>) will be sorted instead
*   of the <td> itself.
*/
jQuery.fn.sortElements = (function(){
var sort = [].sort;
return function(comparator, getSortable) {
getSortable = getSortable || function(){return this;};
var placements = this.map(function(){
var sortElement = getSortable.call(this),
parentNode = sortElement.parentNode,
// Since the element itself will change position, we have
// to have some way of storing it's original position in
// the DOM. The easiest way is to have a 'flag' node:
nextSibling = parentNode.insertBefore(
document.createTextNode(''),
sortElement.nextSibling
);
return function() {
if (parentNode === this) {
throw new Error(
"You can't sort elements if any one is a descendant of another."
);
}
// Insert before flag:
parentNode.insertBefore(this, nextSibling);
// Remove flag:
parentNode.removeChild(nextSibling);
};
});
return sort.call(this, comparator).each(function(i){
placements[i].call(getSortable.call(this));
});
};
})();
EOB;
            break;
    }
    exit();
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
For help or more information on <strong>esq_sectionsort</strong>, please see the <a href="http://forum.textpattern.com/viewtopic.php?id=34637">forum thread</a>.
# --- END PLUGIN HELP ---
-->
<?php
}
?>