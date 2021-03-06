<?php # $Id$


if (IN_serendipity !== true) {
    die ("Don't hack!");
}

// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';
if (file_exists($probelang)) {
    include $probelang;
}

include dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_event_emoticonchooser extends serendipity_event
{
    var $title = PLUGIN_EVENT_EMOTICONCHOOSER_TITLE;

    function introspect(&$propbag)
    {
        global $serendipity;

        $propbag->add('name',          PLUGIN_EVENT_EMOTICONCHOOSER_TITLE);
        $propbag->add('description',   PLUGIN_EVENT_EMOTICONCHOOSER_DESC);
        $propbag->add('stackable',     false);
        $propbag->add('author',        'Garvin Hicking, Jay Bertrandt');
        $propbag->add('requirements',  array(
            'serendipity' => '0.8',
            'smarty'      => '2.6.7',
            'php'         => '4.1.0'
        ));
        $propbag->add('version',       '1.8');
        $propbag->add('event_hooks',    array(
            'backend_entry_toolbar_extended' => true,
            'backend_entry_toolbar_body' => true,
            'frontend_comment' => true
        ));
        $propbag->add('groups', array('BACKEND_EDITOR'));
        $propbag->add('configuration', array('frontend', 'popup', 'popuptext'));
    }

    function generate_content(&$title) {
        $title = PLUGIN_EVENT_EMOTICONCHOOSER_TITLE;
    }

    function introspect_config_item($name, &$propbag)
    {
        switch($name) {
            case 'frontend':
                $propbag->add('type',        'boolean');
                $propbag->add('name',        PLUGIN_EVENT_EMOTICONCHOOSER_FRONTEND);
                $propbag->add('description', '');
                $propbag->add('default',     false);
                return true;
                break;

            case 'popup':
                $propbag->add('type',        'boolean');
                $propbag->add('name',        PLUGIN_EVENT_EMOTICONCHOOSER_POPUP);
                $propbag->add('description', '');
                $propbag->add('default',     false);
                return true;
                break;

            case 'popuptext':
                $propbag->add('type',        'string');
                $propbag->add('name',        PLUGIN_EVENT_EMOTICONCHOOSER_POPUPTEXT);
                $propbag->add('description', '');
                $propbag->add('default',     PLUGIN_EVENT_EMOTICONCHOOSER_POPUPTEXT_DEFAULT);
                return true;
                break;
        }
        return false;
    }


    function event_hook($event, &$bag, &$eventData, $addData = null) {
        global $serendipity;

        if (!class_exists('serendipity_event_emoticate')) {
            return false;
        }

        $hooks = &$bag->get('event_hooks');
        if (isset($hooks[$event])) {
            switch($event) {
                case 'frontend_comment':
                    if (serendipity_db_bool($this->get_config('frontend', false)) === false) {
                        break;
                    }
                    $txtarea = 'serendipity_commentform_comment';
                    $func    = 'comment';
                    $style   = '';
                    $popcl   = '';

                case 'backend_entry_toolbar_extended':
                    if (!isset($txtarea)) {
                        $txtarea = 'serendipity[extended]';
                        $func    = 'extended';
                    }

                case 'backend_entry_toolbar_body':
                    if (!isset($txtarea)) {
                        if (isset($eventData['backend_entry_toolbar_body:textarea'])) {
                            // event caller has given us the name of the textarea converted
                            // into a wysiwg editor(for example, the staticpages plugin)
                            $txtarea = $eventData['backend_entry_toolbar_body:textarea'];
                        } else {
                            // default value
                            $txtarea = 'serendipity[body]';
                        }
                        if (isset($eventData['backend_entry_toolbar_body:nugget'])) {
                            $func = $eventData['backend_entry_toolbar_body:nugget'];
                        } else{
                            $func    = 'body';
                        }
                    }

                    if (!isset($popcl)) {
                        $popcl = ' serendipityPrettyButton';
                    }

                    if (!isset($style)) {
                        $style = 'text-align: right; margin-top: 5px';
                    }

                    $popupstyle = '';
                    $popuplink  = '';
                    if (serendipity_db_bool($this->get_config('popup', false))) {
                        $popupstyle = '; display: none';
                        $popuplink  = '<a class="serendipity_toggle_emoticon_bar' . $popcl . '" href="#" onclick="toggle_emoticon_bar(); return false">' . $this->get_config('popuptext') . '</a>';
                    }

                    $i = 1;
                    $emoticons = serendipity_event_emoticate::getEmoticons();
                    $unique = array();
                    foreach($emoticons as $key => $value) {
                        if (is_callable(array('serendipity_event_emoticate', 'humanReadableEmoticon'))) {
                            $key = serendipity_event_emoticate::humanReadableEmoticon($key);
                        }
                        $unique[$value] = $key;
                    }
?>
<script type="text/javascript">
<!--
function toggle_emoticon_bar() {
   el = document.getElementById('serendipity_emoticonchooser');
   if (el.style.display == 'none') {
      el.style.display = 'block';
   } else {
      el.style.display = 'none';
   }
}

function use_emoticon_<?php echo $func; ?>(img) {
    if(typeof(FCKeditorAPI) != 'undefined') {
        var oEditor = FCKeditorAPI.GetInstance('<?php echo $txtarea; ?>') ;
        oEditor.InsertHtml(img);
    } else if(typeof(xinha_editors) != 'undefined') {
        if(typeof(xinha_editors['<?php echo $txtarea; ?>']) != 'undefined') {
            // this is good for the two default editors (body & extended)
            xinha_editors['<?php echo $txtarea; ?>'].insertHTML(img);
        } else if(typeof(xinha_editors['<?php echo $func; ?>']) != 'undefined') {
            // this should work in any other cases than previous one
            xinha_editors['<?php echo $func; ?>'].insertHTML(img);
        } else {
            // this is the last chance to retrieve the instance of the editor !
            // editor has not been registered by the name of it's textarea
            // so we must iterate over editors to find the good one
            for (var editorName in xinha_editors) {
                if('<?php echo $txtarea; ?>' == xinha_editors[editorName]._textArea.name) {
                    xinha_editors[editorName].insertHTML(img);
                    return;
                }
            }
            // not found ?!?
        }
    } else if(typeof(HTMLArea) != 'undefined') {
        if(typeof(editor<?php echo $func; ?>) != 'undefined')
            editor<?php echo $func; ?>.insertHTML(img);
        else if(typeof(htmlarea_editors) != 'undefined' && typeof(htmlarea_editors['<?php echo $func; ?>']) != 'undefined')
            htmlarea_editors['<?php echo $func; ?>'].insertHTML(img);
    } else if(typeof(TinyMCE) != 'undefined') {
        //tinyMCE.execCommand('mceInsertContent', false, img);
        tinyMCE.execInstanceCommand('<?php echo $txtarea; ?>', 'mceInsertContent', false, img);
    } else  {
        // default case: no wysiwyg editor
        txtarea = document.getElementById('<?php echo $txtarea; ?>');
        if (txtarea.createTextRange && txtarea.caretPos) {
            caretPos = txtarea.caretPos;
            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + ' ' + img + ' ' : caretPos.text + ' ' + img + ' ';
        } else {
            txtarea.value  += ' ' + img + ' ';
        }

        // alert(obj);
        txtarea.focus();
    }
}
//-->
</script>
<?php
                    echo $popuplink;
                    echo '<div id="serendipity_emoticonchooser" style="' . $style . $popupstyle . '">';
                    foreach($unique as $value => $key) {
                        echo '<a href="javascript:use_emoticon_' . $func . '(\'' . addslashes($key) . '\')" title="' . $key . '"><img src="'. $value .'" style="border: 0px" alt="' . $key . '" /></a>&nbsp;';
                        if ($i++ % 10 == 0) {
                            echo '<br />';
                        }
                    }
                    echo '</div>';

                    return true;
                    break;

                default:
                    return false;
                    break;
            }
        } else {
            return false;
        }
    }
}

/* vim: set sts=4 ts=4 expandtab : */
?>