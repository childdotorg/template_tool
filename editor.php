<?php
/**
 * editor.php
 *
 * @author jrsouth (GitHub)
 * @package template_tool
 */

// Require authentication (super simple and not particularly secure)
require('settings-core.php');
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $editor_username || $_SERVER['PHP_AUTH_PW'] != $editor_password) {
    header('WWW-Authenticate: Basic realm="Template Editor"');
    header('HTTP/1.0 401 Unauthorized');
    echo('You must be authorised to access this page.');
    exit;
} 
unset($login_username, $login_password);


// Require function and class files, and run process.php to handle input
require 'functions.php';
require 'functions-editor.php';
require 'process.php';

// Output XML header
echo '<?xml version="1.0" encoding="utf-8" ?>';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
	<title>Editor | Child.org template tool</title>
	<link rel="stylesheet" type="text/css" href="style-editor.css" />
	<script src="functions.js" type="text/javascript"> </script>
	<script src="functions-editor.js" type="text/javascript"> </script>
</head>

<body>


<?php require $content_path . 'header.chunk'; ?>


<?php // Put restart link in if template already selected
if (isset($_GET['template_id']) || isset($_POST['template_id'])) {
    echo '<br style="clear:both;"/><a href="'.$_SERVER['PHP_SELF'].'">&lt;&lt; Back to template selection</a>';
}
?>

<hr />


<?php

if (isset($_POST['template_id']) && $_POST['template_id'] != 'new') {



    if (isset($_GET['action']) && $_GET['action'] == 'duplicate') {

	// Duplicate existing template
	//
	// Needs error checking/confirmation of success. 
	// Maybe give option for new name as part of process instead of appending "(copy)"?

	// Get current template setting
    	$template = getTemplate($_POST['template_id']);

	// Get current template fields
	$fields = getTemplateFields($_POST['template_id']);

	// Get current template images
	$images = getTemplateImages($_POST['template_id']);


	// Create new template: "Template Name (copy)"

	$sql = 'INSERT INTO `templates` VALUES (
		NULL,
		"' . $template['name'] . ' (copy)",
		"' . $template['tags'] . '",
		"' . $template['pdf_file'] . '",
		"' . $template['permissions'] . '",
		' . $template['bleed'] . ',
		"' . $template['owner'] . '",
		' . $template['active'] . ',
		' . $template['pagecount'] . ',
		' . $template['height'] . ',
		' . $template['width'] . '
		)'; 
	mysql_query($sql);
	$new_template_id = mysql_insert_id();


        // Add fields to new template
	// XXX Needs to account for parent-child relationships between fields

	foreach ($fields as $field) {

	$sql = 'INSERT INTO `fields` VALUES (
		NULL,
		' . $new_template_id . ',
		"' . $field['type'] . '",
		"' . $field['name'] . '",
		"' . $field['default_text'] . '",
		' . $field['force_uppercase'] . ',
		' . $field['character_limit'] . ',
		' . $field['font_id'] . ',
		' . $field['font_size'] . ',
		' . $field['colour_id'] . ',
		' . $field['x_position'] . ',
		' . $field['y_position'] . ',
		' . $field['wrap_width'] . ',
		' . $field['leading'] . ',
		' . $field['parent'] . ',
		' . $field['page'] . '
		)';

	mysql_query($sql);

	}

        // Add images to new template

	foreach ($images as $image) {

	$sql = 'INSERT INTO `images` VALUES (
		NULL,
		' . $new_template_id . ',
		"' . $image['name'] . '",
		' . $image['x_position'] . ',
		' . $image['y_position'] . ',
		' . $image['width'] . ',
		' . $image['height'] . ',
		"' . $image['alignment'] . '",
		' . $image['page'] . '
		)';

	mysql_query($sql);

	}

	// Make the page display the new template rather than the current one
	$_POST['template_id'] = $new_template_id;

    }


    if (isset($_GET['remove'])) { // Removing a field

        echo '<div class="delete-box"><h3>Delete</h3>';

        $deleteField = getField($_GET['remove']);

        if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {

            // DELETE FIELD
            $delete_sql = 'DELETE FROM `fields` WHERE `id` = '.$_GET['remove'];
            if (mysql_query($delete_sql)) {
                echo 'FIELD DELETED!';
            } else {
                echo 'AN ERROR HAPPENED :(';
            }

        } else { // Get confirmation

            echo 'Are you sure you want to delete <strong>"'.$deleteField['name'].'"</strong> from <strong>"'.$template['name'].'"</strong>?<br />';
            echo '<a href="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&confirm=yes">Delete</a> | <a href="'.$_SERVER['PHP_SELF'].'?template_id='.$template['id'].'">Cancel</a>';

        }

        echo '</div>';

    }


    // Get font and colour values

    $colours = getAllColours();
    $fonts = getAllFonts();
    $fields = getTemplateFields($_POST['template_id']);
    $images = getTemplateImages($_POST['template_id']);
    $template = getTemplate($_POST['template_id']);





    if (isset($_POST['savefield'])) { // If save field button has been pressed

        if (isset($_POST['create']) && $_POST['create'] == 'new') { // If it's a new field

            // Validate entered values - make sure numbers are numbers, etc.
            $validation = validateFieldPostData('new');
            $flag = $validation['flag'];
            $msg = $validation['msg'];
            $sql = $validation['sql'];

            $insert_sql = 'INSERT INTO `fields` SET `id` = NULL, ' . $sql;

            if (!$flag && mysql_query($insert_sql)) {
                echo 'FIELD INSERTED!<br />(Values are kept below in case you need to enter a similar new field)';
                // Update $fields to reflect new insertion
                $fields = getTemplateFields($_POST['template_id']);
            } else {
                debug('<p><strong>AN ERROR OCCURRED :( </strong></p><p>FLAG = '.$flag.'</p><p>$msg: <br />'.$msg.'</p><p>SQL statement:<br />'.$insert_sql.'</p>');
            }

        } else { // If it's an update to existing field(s)

            $update_error = false;

            foreach ($fields as $field) {
                // Validate entered values - make sure numbers are numbers, etc.
                $validation = validateFieldPostData($field['id']);
                $flag = $validation['flag'];
                $msg = $validation['msg'];
                $sql = $validation['sql'];

                $update_sql = 'UPDATE `fields` SET ' . $sql . ' WHERE `id` = ' . $field['id'];

                if ($flag || !mysql_query($update_sql)) {
                    debug('<p><strong>AN ERROR OCCURRED :( </strong></p><p>FLAG = '.$flag.'</p><p>$msg: <br />'.$msg.'</p><p>SQL statement:<br />'.$insert_sql.'</p>');
                    $update_error = true;
                }


            }

            if (!$update_error) {
                echo 'FIELD(S) UPDATED!';
                // Update $fields to reflect new insertions
                $fields = getTemplateFields($_POST['template_id']);
            }

        }

    }












    echo '<div style="float:left;"><img class="thumb" src="tools/createPDF.php?view=thumbnail&template_id='.$template['id'].'" /></div>';
    echo '<div style="float:left;padding-left:10px"><h2 style="border:0;">'.$template['name'].'<br /><sub>(template #'.$template['id'].')</sub></h2></div>';
    echo '<div style="float:right;padding-right:10px;padding-top:10px"><a  href="'.$_SERVER['PHP_SELF'].'?action=duplicate&template_id='.$template['id'].'"><img alt="Duplicate this template" title="Duplicate this template" src="images/duplicate.png" style="height:2em;" /></a></div>';
    echo '<hr />';




    // ------------ top tabs -------------------------------------

    echo '<div class="top-tabs">';

    echo '<div class="top-tab" style="background-color: #666666; color: #FFFFFF;" onclick="hideAllBut(\'section-parent\', \'section-new-field\');makeInverted(this);">New field</div>';
    echo '<div class="top-tab" onclick="hideAllBut(\'section-parent\', \'section-edit-fields\');makeInverted(this);">Edit fields</div>';
    echo '<div class="top-tab" onclick="hideAllBut(\'section-parent\', \'section-edit-images\');makeInverted(this);">Edit images</div>';
    echo '<div class="top-tab" onclick="hideAllBut(\'section-parent\', \'section-edit-template\');makeInverted(this);">Edit template</div>';
    echo '<br class="clear" />';

    echo '</div>';




    echo '<div id="section-parent">';

    // ----------------- CREATE NEW FIELD ---------------------------------------------



    // New field form
    // echo('<h3>Create new field</h3>');

    echo '<div id="section-new-field">';


    echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

    echo '<input type="hidden" name="template_id" value="'.$_POST['template_id'].'" />';
    echo '<input type="hidden" name="create" value="new" />';

    echo '<table class="editor">';

    // echo('<tr class="header"><td colspan="2">New field</td></tr>');


    // Create new field
    $emptyField = array(
        'id' => 'new',
        'template_id' => $template['id'],
        'name' => (isset($_POST['field-new-name'])?$_POST['field-new-name']:''),
        'default_text' => (isset($_POST['field-new-default_text'])?$_POST['field-new-default_text']:''),
        'force_uppercase' => 0,
        'character_limit' => (isset($_POST['field-new-character_limit'])?$_POST['field-new-character_limit']:0),
        'font_id' => (isset($_POST['field-new-font_id'])?$_POST['field-new-font_id']:''),
        'font_size' => (isset($_POST['field-new-font_size'])?$_POST['field-new-font_size']:''),
        'colour_id' => (isset($_POST['field-new-colour_id'])?$_POST['field-new-colour_id']:''),
        'x_position' => (isset($_POST['field-new-x_position'])?$_POST['field-new-x_position']:''),
        'y_position' => (isset($_POST['field-new-y_position'])?$_POST['field-new-y_position']:''),
        'wrap_width' => (isset($_POST['field-new-wrap_width'])?$_POST['field-new-wrap_width']:''),
        'leading' => (isset($_POST['field-new-leading'])?$_POST['field-new-leading']:''),
        'parent' => 0,
        'page' => 1,
    );
    // Show editor

    displayFieldEditor($emptyField);

    echo '</table>';

    echo '<br /><input class="button" type="submit" name="savefield" value="Create new field" />';

    echo '</form>';

    echo '</div>'; // End new field section






    // ----------------- EDIT EXISITING FIELDS ---------------------------------------------

    echo '<div id="section-edit-fields" style="display: none;">';

    echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

    echo '<input type="hidden" name="template_id" value="'.$_POST['template_id'].'" />';

    echo '<input class="button" type="submit" name="savefield" value="Save Changes" />';

    echo '<table class="editor">';

    foreach ($fields as $field) {
        displayFieldEditor($field);
    }

    echo '</table>';

    echo '<br /><input class="button" type="submit" name="savefield" value="Save Changes" />';

    echo '</form>';


    echo '</div>'; // end edit fields section



    // ----------------- EDIT IMAGES ---------------------------------------------

    echo '<div id="section-edit-images" style="display: none;">';

    echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

    echo '<input type="hidden" name="template_id" value="'.$_POST['template_id'].'" />';

    echo '<input class="button" type="submit" name="savefield" value="Save Changes" />';

    echo '<table class="editor">';

    foreach ($images as $image) {
        displayImageEditor($image);
    }

    echo '</table>';

    echo '<br /><input class="button" type="submit" name="savefield" value="Save Changes" />';

    echo '</form>';


    echo '</div>'; // end edit fields section





    // ----------------- TEMPLATE OPTIONS ---------------------------------------------


    // echo('<h3>Template settings</h3>');

    echo '<div id="section-edit-template" style="display: none;">';


    displayTemplateEditor($template['id']);


    echo '</div>'; // End edit template section


    echo '</div>'; // end main content section



} else { // If no template selected yet display available templates and new template form

    $templates = getAllTemplates();


    $templates_active = '';
    $templates_inactive = '';

    // Split list of all templates by `active` value (Datatype BIT, 1 = active, 0 = inactive)
    foreach ($templates as $template) {
        if ($template['active']) {
            $templates_active .= ('<li><a href="'.$_SERVER['PHP_SELF'].'?template_id='.$template['id'].'">'.$template['name'].'</a></li>');
        } else {
            $templates_inactive .= ('<li><a href="'.$_SERVER['PHP_SELF'].'?template_id='.$template['id'].'">'.$template['name'].'</a></li>');
        }
    }

    echo '<div class="list-2-1"><h3>Active templates</h3><ul class="basic-list">'.$templates_active.'</ul></div>';
    echo '<div class="list-2-2"><h3>Inactive templates</h3><ul class="basic-list">'.$templates_inactive.'</ul></div>';
    echo '<br style="clear:both;"/>';
    echo '<h3>Add a new template</h3>';

    displayTemplateEditor('new');


}


?>
<br style="clear:both;" />
</body>

</html>
