<?php

if (IN_serendipity !== true) {
    die ("Don't hack!");
}


// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';
if (file_exists($probelang)) {
    include $probelang;
}

include dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_event_template_editor extends serendipity_event {
    var $title = PLUGIN_EVENT_TEMPLATE_EDITOR_NAME;
    var $pluginPath;

    function introspect(&$propbag) {
        global $serendipity;

        $propbag->add('name',          PLUGIN_EVENT_TEMPLATE_EDITOR_NAME);
        $propbag->add('description',   PLUGIN_EVENT_TEMPLATE_EDITOR_DESC);
        $propbag->add('stackable',     false);
        $propbag->add('author',        'Malte Paskuda');
        $propbag->add('license',       'GPL');
        $propbag->add('version',       '0.5');
        $propbag->add('requirements',  array(
            'serendipity' => '0.8'
        ));
        $propbag->add('event_hooks',   array('backend_templates_configuration_bottom' => true,
                                             'backend_templates_configuration_none' => true,
                                             'external_plugin' => true,
                                             'backend_sidebar_entries_event_display_template_editor' => true));
        $propbag->add('configuration', array('highlight', 'path'));
        $propbag->add('groups', array('BACKEND_FEATURES'));
    }

    function generate_content(&$title) {
        $title = $this->title;
    }


    function introspect_config_item($name, &$propbag) {
        global $serendipity;

        switch($name) {
            case 'path':
                $propbag->add('type', 'string');
                $propbag->add('name', PLUGIN_EVENT_TEMPLATE_EDITOR_PATH);
                $propbag->add('description', PLUGIN_EVENT_TEMPLATE_EDITOR_PATH_DESC);
                $propbag->add('default', $serendipity['serendipityHTTPPath'] . 'plugins/serendipity_event_template_editor/');
                return true;
                break;
            case 'highlight':
                $propbag->add('type', 'boolean');
                $propbag->add('name', PLUGIN_EVENT_TEMPLATE_EDITOR_HIGHLIGHT);
                $propbag->add('default', true);
                return true;
                break;
             default:
            	return false;
			}
		return true;
    }


    function event_hook($event, &$bag, &$eventData, $addData = null) {
        global $serendipity;
    
        $hooks = &$bag->get('event_hooks');
        if (isset($hooks[$event])) {
            switch($event) {
                case 'external_plugin':
                    //template_editor_delete with it's argument is otherwise not detected
                    $eventData = preg_replace('/&.*/', '', $eventData);
                    switch ($eventData) {
                        case 'template_editor_save':
                            if (! (serendipity_checkPermission('siteConfiguration'))) {
                                return;
                            }
                            $path = $_REQUEST['path'];
                            $content = $_REQUEST['content'];
                            if (substr($path, -4) == '.php') {
                                //we need to check .php files for syntax-errors, because
                                //saving them would shutdown the blog and this editor
                                if ($this->checkSyntax($content)) {
                                    file_put_contents($path, $content);
                                } else {
                                    //give user the opportunity to correct the error:
                                    //read from tempfile, show errormessage
                                    $tempFile = $serendipity['serendipityPath'] . 'templates_c/template_editor_temp.php';
                                    $errors = shell_exec("php -l $tempFile");
                                    $msg = urlencode($errors);
                                    $msgtype = 'error';
                                    $redirect = '<meta http-equiv="REFRESH" content="0;url=';
                                    $url = 'serendipity_admin.php?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]='.$path;
                                    $url .= '&amp;serendipity[template_editor_use_temp]';
                                    $url .= '&amp;serendipity['.$msgtype.']='. $msg .'">';
                                    echo $redirect . $url;
                                    return;
                                }
                            } else {
                                file_put_contents($path, $content);
                            }
                            //the user probably wants to edit more of this file
                            echo '<meta http-equiv="REFRESH" content="0;url=serendipity_admin.php?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]='.$path.'">';
                            break;
                          case 'template_editor_create':
                            if (! (serendipity_checkPermission('siteConfiguration') )) {
                                return;
                            }
                            $path = $_REQUEST['path'];
                            $file = $_REQUEST['file'];
                            file_put_contents($path.$file, '');
                            echo '<meta http-equiv="REFRESH" content="0;url=serendipity_admin.php?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]='.$path.$file.'">';
                            break;
                        case 'template_editor_delete':
                            if (! (serendipity_checkPermission('siteConfiguration'))) {
                                return;
                            }
                            $path = $_REQUEST['template_editor_path'];
                            unlink($serendipity['serendipityPath']. $path);
                            $path = dirname($path).'/';
                            echo '<meta http-equiv="REFRESH" content="0;url=serendipity_admin.php?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]='.$path.'">';
                            break;
                        case 'template_editor_rename':
                            if (! (serendipity_checkPermission('siteConfiguration'))) {
                                return;
                            }
                            $old_name = $_REQUEST['file'];
                            $path = $_REQUEST['curDir'];
                            $new_name = $_REQUEST['value'];
                            rename($serendipity['serendipityPath'] . $path . $old_name,
                                    $serendipity['serendipityPath'] . $path . $new_name);
                            echo $new_name;
                            break;
                        case 'template_editor_upload':
                            if (! (serendipity_checkPermission('siteConfiguration'))) {
                                return;
                            }
                            $name = $_FILES['userfile']['name'];
                            $path = $_REQUEST['path'];
                            move_uploaded_file($_FILES['userfile']['tmp_name'], $path.$name);
                            echo '<meta http-equiv="REFRESH" content="0;url=serendipity_admin.php?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]='.$path.'">';
                            break;
                        case 'serendipity_event_template_editor.js':
                            header('Content-Type: text/javascript');
                            echo file_get_contents(dirname(__FILE__). '/serendipity_event_template_editor.js');
                            break;
                        case 'serendipity_event_template_editor.css':
                            header('Content-Type: text/css');
                            echo file_get_contents(dirname(__FILE__). '/serendipity_event_template_editor.css');
                            break;
                        case 'codemirror/codemirror.js':
                            header('Content-Type: text/javascript');
                            echo file_get_contents(dirname(__FILE__). '/codemirror/codemirror.js');
                            break;
                        case 'codemirror/csscolors.css':
                            header('Content-Type: text/css');
                            echo file_get_contents(dirname(__FILE__). '/codemirror/csscolors.css');
                            break;
                        case 'codemirror/parsecss.js':
                            header('Content-Type: text/javascript');
                            echo file_get_contents(dirname(__FILE__). '/codemirror/parsecss.js');
                            break;
                        case 'jquery.jeditable.js':
                            header('Content-Type: text/javascript');
                            echo file_get_contents(dirname(__FILE__). '/jquery.jeditable.js');
                            break;
                    }
                    return true;
                    break;
                case 'backend_templates_configuration_bottom':
                case 'backend_templates_configuration_none':
                    if (! (serendipity_checkPermission('siteConfiguration'))) {
                        return;
                    }
                    echo '<h3>
                            <a href="?&amp;serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor">
                            '. PLUGIN_EVENT_TEMPLATE_EDITOR_START .' <img class="serendipityImageButton" title="Bearbeiten" alt="" src="'.serendipity_getTemplateFile('admin/img/configure.png').'" />
                            </a>
                            </h3>';
                    return true;
                    break;
                case 'backend_sidebar_entries_event_display_template_editor':
                    if (! (serendipity_checkPermission('siteConfiguration'))) {
                        return;
                    }

                    if (isset($serendipity['GET']['message'])) {
                        echo '<p class="serendipityAdminMsgNote">'.htmlspecialchars($serendipity['GET']['message']).'</p>';
                    }
                    if (isset($serendipity['GET']['success'])) {
                        echo '<p class="serendipityAdminMsgSuccess">'.htmlspecialchars($serendipity['GET']['success']).'</p>';
                    }
                    if (isset($serendipity['GET']['error'])) {
                        echo '<p class="serendipityAdminMsgError">'.htmlspecialchars($serendipity['GET']['error']).'</p>';
                    }

                    
                    
                    #only necessary for delivering the javascript and css
                    $pluginPath = $this->pluginPath = $this->get_config('path', '');
                    if (empty($pluginPath) || $pluginPath == 'default' || $pluginPath == 'none' || $pluginPath == 'empty') {
                        $pluginPath = $this->pluginPath = $serendipity['baseURL'] . 'index.php?/plugin/';
                    }
                    
					if (!$serendipity['capabilities']['jquery']) {
	                    echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js" type="text/javascript"></script>';
					}
                    echo '<script src="'.$pluginPath.'jquery.jeditable.js" type="text/javascript"></script>
                    <script src="'.$pluginPath.'serendipity_event_template_editor.js" type="text/javascript"></script>
                    <link rel="stylesheet" type="text/css" href="'.$pluginPath.'serendipity_event_template_editor.css" /> 
                    <script type="text/javascript">pluginPath = "'.$pluginPath.'";</script>';

                        
                    if (empty($serendipity['GET']['template_editor_path'])||is_dir($serendipity['serendipityPath'] . $serendipity['GET']['template_editor_path'])) {
                        #The path is our basic argument for selecting a file or a folder
                        $path = false;
                        if (!empty($serendipity['GET']['template_editor_path'])) {
                            $path = $serendipity['GET']['template_editor_path'];
                        }
                        if ($this->forkTemplate()) {
                            if (isset($serendipity['GET']['template_editor_upload'])) {
                                $this->showUploader($path, true);
                            } else {
                                $this->showUploader($path, false);
                            }
                            $this->showFiles($path);
                            $this->showDirectories($path);
                            $this->showCreator($path);
                        }
                        
                    } else {
                        if ($this->get_config('highlight', true)) {
                            echo '<script src="'.$pluginPath.'codemirror/codemirror.js" type="text/javascript"></script>';
                        }
                        if (isset($serendipity['GET']['template_editor_use_temp'])) {
                            $this->showEditor($serendipity['GET']['template_editor_path'] . $serendipity['GET']['editfile'], true);
                        } else {
                            $this->showEditor($serendipity['GET']['template_editor_path'] . $serendipity['GET']['editfile']);
                        }
                    }
                    break;
                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    function showUploader($path, $form) {
        global $serendipity;
        $template_path = $serendipity['templatePath'];
        $cur_template =  $serendipity['template'];
        if (!$path) {
            $path = $template_path . $cur_template . '/';
        }
        if ($form) {
            echo '<div id="templateEditorUpload"><h3>'.PLUGIN_EVENT_TEMPLATE_EDITOR_UPLOAD.'</h3>
            <form enctype="multipart/form-data" action="'.$serendipity ['baseURL'] . 'index.php?/plugin/template_editor_upload" method="post">
                <input type="hidden" name="path" value="'.$path.'" />
                <input name="userfile" type="file" />
                <input class="serendipityPrettyButton input_button" type="submit" value="'.GO.'" />
            </form></div>';
        } else {
            echo '<div id="templateEditorUpload">
                    <form action="serendipity_admin.php" method="get">
                    <input type="hidden" name="serendipity[adminModule]" value="event_display" />
                    <input type="hidden" name="serendipity[adminAction]" value="template_editor" />
                    <input type="hidden" name="serendipity[template_editor_path]" value="'.$path.'" />
                    <input type="hidden" name="serendipity[template_editor_upload]" value="" />
                <input class="serendipityPrettyButton input_button" type="submit" value="'.PLUGIN_EVENT_TEMPLATE_EDITOR_UPLOAD.'" />
                </form></div>';
        }
        
    }

    function showCreator($path) {
        global $serendipity;
        $template_path = $serendipity['templatePath'];
        $cur_template =  $serendipity['template'];
        if (!$path) {
            $path = $template_path . $cur_template . '/';
        }
        echo '<h3>'.CREATE.'</h3>
        <form action="'.$serendipity ['baseURL'] . 'index.php?/plugin/template_editor_create" method="post">
            <input type="hidden" name="path" value="'.$path.'" />
            <input type="text" name="file" value="" />
            <input class="serendipityPrettyButton input_button" type="submit" value="'.GO.'" />
        </form>';
    }

    function showEditor($path , $temp=false) {
        global $serendipity;

        $file = basename($path);
        if ($temp) {
            $tempFile = $serendipity['serendipityPath'] . 'templates_c/template_editor_temp.php';
            $content = file_get_contents($tempFile);
        } else {
            if (file_exists($serendipity['serendipityPath'] . $path)) {
                //htmlescape $content in the editor to not close the textarea by accident
                $content = file_get_contents($serendipity['serendipityPath'] . $path);
            } else {
                echo ERROR_FILE_NOT_EXISTS;
                return;
            }
        }
        
        $heading = $this->linkify("?&amp;serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]=", $path, $serendipity['template']);

        echo '<h3>'.$heading.'</h3>
        <form action="'.$serendipity ['baseURL'] . 'index.php?/plugin/template_editor_save" method="post">
            <input type="hidden" name="path" value="'.$path.'" />
            <textarea name="content" id="template_editor" cols="80" rows="38">'.htmlspecialchars($content).'</textarea>
            <input class="serendipityPrettyButton input_button" type="submit" value="'.SAVE.'" />
        </form>';
        
    }

    function showFiles($path=false) {
        global $serendipity;
        $template_path = $serendipity['templatePath'];
        $cur_template =  $serendipity['template'];
        if (!$path) {
            $path = $template_path . $cur_template . '/';
        }
        $files = $this->getFiles($path);


        $heading = $this->linkify("?&amp;serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]=", $path, $cur_template);
        
        echo '<h3 id="templateEditorPath">'. $heading.'</h3>';
        if (empty($files)) {
            return;
        }
        echo '<ul id="templateEditorFileList" class="templateEditorList">';
        foreach ($files as $file) {
            if (getimagesize("{$path}{$file}")) {
                #images shouldn't end in the textarea
                echo "<li>
                    <span class=\"templateEditorListItem\">$file</span>
                    <a href=\"{$serendipity['baseURL']}index.php?/plugin/template_editor_delete&template_editor_path={$path}{$file}\">
                        <img class=\"serendipityImageButton templateEditorDelete\" title=\"".DELETE."\" alt=\"".DELETE."\" src=\"".serendipity_getTemplateFile('admin/img/delete.png')."\" />
                    </a>
                </li>";
                $jsDeleteDialogs .= sprintf('var DELETE_SURE_'.str_replace('.', '_', $file).'="'.DELETE_SURE.'";', $file);
            } else {
                echo "<li>
                    <a href=\"?&amp;serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&amp;serendipity[template_editor_path]={$path}{$file}\">
                        <span class=\"templateEditorListItem\">$file</span>
                        <img class=\"serendipityImageButton\" title=\"Datei bearbeiten\" alt=\"Datei bearbeiten\" src=\"".serendipity_getTemplateFile('admin/img/edit.png')."\" />
                    </a>
                    <a href=\"{$serendipity['baseURL']}index.php?/plugin/template_editor_delete&template_editor_path={$path}{$file}\">
                        <img class=\"serendipityImageButton templateEditorDelete\" title=\"".DELETE."\" alt=\"".DELETE."\" src=\"".serendipity_getTemplateFile('admin/img/delete.png')."\" />
                    </a>
                </li>";
                $jsDeleteDialogs .= sprintf('var DELETE_SURE_'.str_replace('.', '_', $file).'="'.DELETE_SURE.'";', $file);
            }
        }
        echo '<script type="text/javascript">'.$jsDeleteDialogs.'
        var curDir = "'. $path .'";</script>';
        echo '</ul>';
    }

    function showDirectories($path=false) {
        global $serendipity;
        $template_path = $serendipity['templatePath'];
        $cur_template =  $serendipity['template'];
        if (!$path) {
            $path = $template_path . $cur_template . '/';
        }
        $dirs = $this->getDirectories($path);
        if (empty($dirs)) {
            return;
        }
        echo '<h3>'.PLUGIN_EVENT_TEMPLATE_SUBFOLDERS.'</h3>';
       
        echo '<ul id="templateEditorFolderList" class="templateEditorList" >';
        foreach ($dirs as $dir) {
            echo "<li>
                    <a class=\"templateEditorListItem\" href=\"?&amp;serendipity[adminModule]=event_display&amp;serendipity[adminAction]=template_editor&serendipity[template_editor_path]={$path}{$dir}/\">
                        $dir
                        <img class=\"serendipityImageButton\" title=\"Ordner\" alt=\"Ordner öffnen\" src=\"".serendipity_getTemplateFile('admin/img/folder.png')."\" />
                    </a>
                </li>";
        }
        echo '</ul>';    

    }

    #make the heading clickable for better subfolder-navigation
    function linkify($base, $path, $start) {
        $unlinked_path = substr($path, 0, strpos($path, "/$start/")+1);
        $path = substr($path, strlen($unlinked_path));

        $pre_path = "";
        $linked_path = "";
        while (!empty($path)) {
            if (strpos($path, '/') === false) {
                #catch the last situation when pointing to /file instead of /dir/
                $part = $path;
                $linked_part = "<a href=\"{$base}{$unlinked_path}{$pre_path}{$part}\">$part</a>";
            } else {
                $part = substr($path, 0, strpos($path, '/'));
                $linked_part = "<a href=\"{$base}{$unlinked_path}{$pre_path}{$part}/\">$part</a>/";
            }
            $pre_path .= "$part/";
            $linked_path .= "$linked_part";
            $path = substr($path, strlen($part)+1);
        }
        return $unlinked_path . $linked_path;
    }

    function getFiles($path) {
        global $serendipity;
        $path = $serendipity['serendipityPath'] . $path;
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && ! is_dir($path . $file)) {
                    $files[] = "$file";
                }
            }
            closedir($handle);
        }
        return $files;
    }

    function getDirectories($path) {
        global $serendipity;
        $path = $serendipity['serendipityPath'] . $path;
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && is_dir($path . $file)) {
                    $files[] = "$file";
                }
            }
            closedir($handle);
        }
        return $files;
    }

    function forkTemplate() {
        global $serendipity;
        $template_path = $serendipity['serendipity_path'] . $serendipity['templatePath'];
        $cur_template =  $serendipity['template'];
       

        //fork only if not already forked
        $info_txt = file_get_contents($template_path . $cur_template . '/info.txt');
        $forked = false;
        if (strpos($info_txt, 'Fork_of:') !== false) {
            $forked = true;
        }
        
        if (! $forked) {
            $fork_template = $cur_template . '_fork';
            if (is_writable($template_path)) {
                if ( ! is_dir($template_path . $fork_template)) {
                    $this->copy_directory($template_path . $cur_template, $template_path . $fork_template);
               
                    $info_txt = preg_replace('/Name: (.*)/', 'Name: ${1}_fork', $info_txt);
                    $info_txt = $info_txt . "\nFork_of: $cur_template";
                    file_put_contents($template_path . $fork_template . '/info.txt', $info_txt);
                }
                
                //Now that the fork is created we need to set it instantly
                //but only if copying succeeded
                if (is_dir($template_path . $fork_template)) {
                    $themeInfo = serendipity_fetchTemplateInfo(htmlspecialchars($fork_template));
                    serendipity_set_config_var('template', htmlspecialchars($fork_template));
                    serendipity_set_config_var('template_engine', isset($themeInfo['engine']) ? $themeInfo['engine'] : 'default');
                }
            } else {
                echo 'Error: Template Directory not writeable';
                return false;
            }
        }
        return true;
    }

    //Check code from $param1 for php-errors
    //return: true if no error found, false otherwise
    function checkSyntax($code) {
        global $serendipity;
        $tempFile = $serendipity['serendipityPath'] . 'templates_c/template_editor_temp.php';
        file_put_contents($tempFile, $code);
        $errors = shell_exec("php -l $tempFile");
        if (strpos($errors, 'Errors parsing') === false) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * http://codestips.com/php-copy-directory-from-source-to-destination/
     * */
    function copy_directory($source, $destination ) {
        if ( is_dir( $source ) ) {
            @mkdir( $destination );
            $directory = dir( $source );
            while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
                if ( $readdirectory == '.' || $readdirectory == '..' ) {
                    continue;
                }
                $PathDir = $source . '/' . $readdirectory; 
                if ( is_dir( $PathDir ) ) {
                    $this->copy_directory( $PathDir, $destination . '/' . $readdirectory );
                    continue;
                }
                copy( $PathDir, $destination . '/' . $readdirectory );
            }
     
            $directory->close();
        } else {
            copy( $source, $destination );
        }
    }

    function debugMsg($msg) {
		global $serendipity;
		
		$this->debug_fp = @fopen ( $serendipity ['serendipityPath'] . 'templates_c/template_editor.log', 'a' );
		if (! $this->debug_fp) {
			return false;
		}
		
		if (empty ( $msg )) {
			fwrite ( $this->debug_fp, "failure \n" );
		} else {
			fwrite ( $this->debug_fp, print_r ( $msg, true ) );
		}
		fclose ( $this->debug_fp );
	}

}

/* vim: set sts=4 ts=4 expandtab : */
?>