<?php
function nethcqr_configpageinit($pagename) {
	//executed 
        global $currentcomponent;
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $id = isset($_REQUEST['id_cqr']) ? $_REQUEST['id_cqr'] : '';

        if($pagename == 'nethcqr'){
                $currentcomponent->addprocessfunc('nethcqr_configprocess');

                //dont show page if there is no action set
                if ($action && $action != 'delete' || $id) {
                        $currentcomponent->addguifunc('nethcqr_configpageload');
                }

    	return true;
	}
}
function nethcqr_configprocess(){
        if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'nethcqr'){
                global $db;
                //get variables
                $get_var = array('id_cqr', 'name', 'announcement', 'description', 'use_code',
                                                'manual_code', 'code_length', 'code_retry',
                                                'invalid_destination', 'db_type',
                                                'db_url','db_name', 'db_user', 'db_pass','query');
                foreach($get_var as $var){
                        $vars[$var] = isset($_REQUEST[$var])    ? $_REQUEST[$var]               : '';
                }
                $action         = isset($_REQUEST['action'])    ? $_REQUEST['action']   : '';
                $entries        = isset($_REQUEST['entries'])   ? $_REQUEST['entries']  : '';
                switch ($action) {
                        case 'save':
                                //get real dest
                                $_REQUEST['id_cqr'] = $vars['id_cqr'] = nethcqr_save_details($vars);
//				nethcqr_my_debug($_REQUEST['id_cqr']."asd");
                               	nethcqr_save_entries($vars['id_cqr'], $entries);
                                needreload();
				$_REQUEST['action'] = 'edit';
                                redirect_standard_continue('id_cqr');
                        break;
                        case 'delete':
                                nethcqr_delete($vars['id_cqr']);
                                needreload();
                                redirect_standard_continue();
                        break;
                }
        }
}

function nethcqr_configpageload(){
//disegna la pagina. Viene chiamato da nethcqr_configpageinit
	global $currentcomponent, $display;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$id_cqr = isset($_REQUEST['id_cqr']) ? $_REQUEST['id_cqr'] : null;

	if ($action  == 'add') {
		$currentcomponent->addguielem('_top', new gui_pageheading('title', _('Add CQR')), 0); //Titolo pagina
		$deet = array('id_cqr', 'name', 'announcement', 'description', 'use_code',
       	                                         'manual_code', 'code_length', 'code_retry',
       	                                         'invalid_destination', 'db_type',
       	                                         'db_url','db_name', 'db_user', 'db_pass','query');
		//setta le variabili di default del nuovo cqr
		foreach ($deet as $d) {
			switch ($d){
				case 'db_url': 
					$cqr[$d] = 'localhost';
					break;
				case 'use_code': 
					$cqr[$d] = 1;
       		        		break;
				case 'announcement':
                                        $cqr[$d] = 0;
                                        break;
				case 'manual_code': 
					$cqr[$d] = 1;
       	                         	break;
				case 'code_length': 
					$cqr[$d] = 5;
       	                         	break;
				case 'code_retry': 
					$cqr[$d] = 3;
                                	break;
				case 'db_type': 
					$cqr[$d] = 'mysql';
                                	break;
				default: 
					$cqr[$d] = '';
                                	break;
                        }
		}
	} else { //$action != 'add'
		$cqr = nethcqr_get_details($id_cqr);
		$cqr = $cqr[0]; //nethcqr_get_details($id_cqr) return an array of cqrs (only one if $id_cqr != '')
		$label = sprintf(_("Edit CQR: %s"), $cqr['name'] ? $cqr['name'] : 'ID '.$cqr['id_cqr']);
		$currentcomponent->addguielem('_top', new gui_pageheading('title', $label), 0);
		//display usage
		//TODO

		//display delete link
		$label = sprintf(_("Delete CQR: %s"), $cqr['name'] ? $cqr['name'] : 'ID '.$cqr['id_cqr']);
		$del =	'<span><img width="16" height="16" border="0" title="
			'.$label.'" alt="" src="images/core_delete.png"/>&nbsp;'.$label.'</span>';
		$currentcomponent->addguielem('_top',
			new gui_link('del', $del, $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete', true, false), 0);
	}
	//general options
	$gen_section = _('CQR General Options');
        $currentcomponent->addguielem($gen_section,
                new gui_textbox('name', stripslashes($cqr['name']), _('CQR Name'), _('Name of this CQR.')));
        $currentcomponent->addguielem($gen_section,
                new gui_textbox('description', stripslashes($cqr['description']), _('CQR Description'), _('Description of this cqr.')));
	//cqr options
	$section = _('CQR Options');

		//build select list for code_length and code_retry BUT DO NOT DISPLAY THEM
		//code_length
		$currentcomponent->addoptlist('code_length', false);
        	for($i=0; $i <13; $i++)
                	$currentcomponent->addoptlistitem('code_length', $i, $i);
		//code_retry
		$currentcomponent->addoptlist('code_retry', false);
                for($i=0; $i <11; $i++)
                        $currentcomponent->addoptlistitem('code_retry', $i, $i);
	//build recordings select list
        $currentcomponent->addoptlistitem('recordings', '', _('None'));
        foreach(recordings_list() as $r)
                $currentcomponent->addoptlistitem('recordings', $r['id'], $r['displayname']);

	$currentcomponent->setoptlistopts('recordings', 'sort', false);
	//add recording to gui
        $currentcomponent->addguielem($section,
                new gui_selectbox('announcement', $currentcomponent->getoptlist('recordings'),
                        $cqr['announcement'], _('Announcement'), _('Greeting to be played on entry to the Ivr.'), false));
	//use_code
	$currentcomponent->addguielem($section,
        	new gui_checkbox('use_code', $cqr['use_code'], _('Use Code'), _('If checked, extract user code from caller ID. If Manual Code is checked too, client code can be dialed by caller if ID is not recognized')));
	//manual_code
	$currentcomponent->addguielem($section,
                new gui_checkbox('manual_code', $cqr['manual_code'], _('Manual Code'), _('If checked client code can be dialed by caller if ID is not recognized')));
	//code_length
	$currentcomponent->addguielem($section,
                new gui_selectbox('code_length', $currentcomponent->getoptlist('code_length'),
                $cqr['code_length'], _('Code Length'), _('Length of client code inserted manualy by caller'), false));
	//code_retry
	$currentcomponent->addguielem($section,
                new gui_selectbox('code_retry', $currentcomponent->getoptlist('code_retry'),
                $cqr['code_retry'], _('Code Retry'), _('Number of time code can be redialed'), false));
	//db_type
	$currentcomponent->addoptlist('db_type', false);
		$currentcomponent->addoptlistitem('db_type', 'mysql', 'MySQL');
		$currentcomponent->addoptlistitem('db_type', 'mssql', 'MSSQL');
	$currentcomponent->addguielem($section,
                new gui_selectbox('db_type', $currentcomponent->getoptlist('db_type'),
                $cqr['db_type'], _('Database Type'), _('Select one of supported database type'), false));
	//db_url
	$currentcomponent->addguielem($section,
                new gui_textbox('db_url', stripslashes($cqr['db_url']), _('Database URL'), _('URL of database')));
	//db_name
	$currentcomponent->addguielem($section,
                new gui_textbox('db_name', stripslashes($cqr['db_name']), _('Database Name'), _('Name of database')));
	//db_user
	$currentcomponent->addguielem($section,
                new gui_textbox('db_user', stripslashes($cqr['db_user']), _('Database Username'), _('Username that will be used accessing database')));
	//db_pass
	$currentcomponent->addguielem($section,
                new gui_password('db_pass', stripslashes($cqr['db_pass']), _('Database Password'), _('Password for database')));
	//query
	$currentcomponent->addguielem($section,
		new gui_textarea('query', stripslashes($cqr['query']), _('Query'), _('Query')));
	$currentcomponent->addguielem($section, new gui_hidden('id_cqr', $cqr['id_cqr']));
        $currentcomponent->addguielem($section, new gui_hidden('action', 'save'));
	
	//Section for destinations
	$section = _('NethCQR Entries');
	//draw the entries part of the table. A bit hacky perhaps, but hey - it works!
        $currentcomponent->addguielem($section, new guielement('rawhtml', nethcqr_draw_entries($cqr['id_cqr']), ''), 6);

}

function nethcqr_delete($id_cqr){
	global $db;
	$id_cqr=mysql_real_escape_string($id_cqr);
	$sql = "DELETE FROM `nethcqr_details` WHERE `id_cqr`=".$id_cqr;
	$results =& $db->query($sql);
	if (DB::isError($results)) {
	        freepbx_debug(__FUNCTION__."QUERY: $sql");
		return false;
	}
	$sql = "DELETE FROM `nethcqr_entries` WHERE `id_cqr`=".$id_cqr;
	$results =& $db->query($sql);
	if (DB::isError($results)) {
		freepbx_debug(__FUNCTION__."QUERY: $sql");
	        return false;
	}
return true;
}

function nethcqr_get_details($id_cqr=''){
	global $db;
	$id_cqr = mysql_real_escape_string($id_cqr);
	$sql = "SELECT * FROM `nethcqr_details`";
	if ($id_cqr) $sql .=" WHERE `id_cqr` = $id_cqr";
	$results =& $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if (DB::isError($results)) {
		freepbx_debug(__FUNCTION__."QUERY: $sql");	
        	return false;
	}
	return $results;
}

//draw cqr entries table header
function nethcqr_draw_entries_table_header() {
        return  array(_('Position'),_('Condition'), _('Destination'), _('Delete'));
}

function nethcqr_get_entries($id_cqr){
	global $db;
	if ($id_cqr==='') return false;
	$sql = "SELECT `id_cqr`,`id_dest`,`position`,`condition`,`destination` FROM `nethcqr_entries` WHERE `id_cqr`=$id_cqr ORDER BY `position` ASC";
	$results =& $db->getAll($sql,DB_FETCHMODE_ASSOC);
	if (DB::isError($results)) {
	        freepbx_debug(__FUNCTION__."QUERY: $sql");
		return false;
	}
	$ret = array();
	foreach ($results as $r)
		$ret[$r['position']] = $r;
	return $ret;
}

//draw destinations actually setted
function nethcqr_draw_entries($id_cqr){
	$headers = nethcqr_draw_entries_table_header();
	$cqr_entries = nethcqr_get_entries($id_cqr);
//	nethcqr_my_debug($cqr_entries);
	if ($cqr_entries)
		foreach ($cqr_entries as $k => $v) {
                        $entries[$k]= $v;
                        $array = array('id_cqr' => $id_cqr, 'position' => $v['position']);
               //         $entries[$k]['hooks'] = nethcqr_draw_entries($array);
                }
	$headers = array ('cqr' => $headers);
	//nethcqr_my_debug($entries);
	return load_view(dirname(__FILE__) . '/views/entries.php',
		array(
                	'headers' => $headers,
			'entries' => $entries
		)
	); 
}

function nethcqr_my_debug($msg){
$openfile = fopen ("/tmp/fpbx_debug.log","a");
$msg = var_export($msg,true);
fwrite ($openfile,"$msg\n\n");
fclose ($openfile);
}

//save cqr settings
function nethcqr_save_details($vals){
        global $db, $amp_conf;
//	nethcqr_my_debug($vals);
        foreach($vals as $key => $value) {
                $vals[$key] = $db->escapeSimple($value);
        }
	$id_cqr=$vals['id_cqr'];
        $name=$vals['name'];
        $description=$vals['description'];
        $announcement=(int)$vals['announcement'];
        $use_code=(int)$vals['use_code'];
        $manual_code=(int)$vals['manual_code'];
        $code_length=(int)$vals['code_length'];
        $code_retry=(int)$vals['code_retry'];
        $db_type=$vals['db_type'];
        $db_url=$vals['db_url'];
        $db_name=$vals['db_name'];
        $db_user=$vals['db_user'];
        $db_pass=$vals['db_pass'];
        $query=$vals['query'];
        if ($vals['id_cqr']) {
		$sql = "UPDATE `nethcqr_details` SET `name`='$name', `description`='$description', `announcement`=$announcement, `use_code`=$use_code, `manual_code`=$manual_code, `code_length`=$code_length, `code_retry`=$code_retry, `db_type`='$db_type', `db_url`='$db_url', `db_name`='$db_name', `db_user`='$db_user', `db_pass`='$db_pass', `query`='$query' WHERE `id_cqr` = $id_cqr";
                $foo = $db->query($sql);
                if($db->IsError($foo)) {
                        die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
                }
        } else {
                unset($vals['id_cqr']);
		$sql = "INSERT INTO `nethcqr_details` SET `name`='$name', `description`='$description', `announcement`=$announcement, `use_code`=$use_code, `manual_code`=$manual_code, `code_length`=$code_length, `code_retry`=$code_retry, `db_type`='$db_type', `db_url`='$db_url', `db_name`='$db_name', `db_user`='$db_user', `db_pass`='$db_pass', `query`='$query' ";
                $foo = $db->query($sql);
                if($db->IsError($foo)) {
                        die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
                }
                $sql = ( ($amp_conf["AMPDBENGINE"]=="sqlite3") ? 'SELECT last_insert_rowid()' : 'SELECT LAST_INSERT_ID()');
                $vals['id_cqr'] = $db->getOne($sql);
                if ($db->IsError($foo)){
                        die_freepbx($foo->getDebugInfo());
                }
        }

        return $vals['id_cqr'];
}

function nethcqr_save_entries($id_cqr, $entries){
        global $db;
        $id_cqr = $db->escapeSimple($id_cqr);
        sql('DELETE FROM nethcqr_entries WHERE id_cqr = "' . $id_cqr . '"');
	if ($entries)
		for ($i=0;$i < count($entries['position']); $i++){
			$position = mysql_real_escape_string($entries['position'][$i]);
			$condition = mysql_real_escape_string($entries['condition'][$i]);
			$destination = mysql_real_escape_string($entries['goto'][$i]);
			$sql = "INSERT INTO `nethcqr_entries` SET `id_cqr`='$id_cqr', `position`='$position', `condition`='$condition', `destination`='$destination'";
			nethcqr_my_debug($sql);
			$db->query($sql);
		}
return true;
}

function nethcqr_check_destinations($dest=true) {
global $active_modules;

        $destlist = array();
        if (is_array($dest) && empty($dest)) {
                return $destlist;
        }
        $sql = "SELECT `destination`,`name`,`position`,`condition`,`a`.`id_cqr`,`id_dest` FROM nethcqr_details a INNER JOIN nethcqr_entries d ON a.id_cqr = d.id_cqr  ";
	
        if ($dest !== true) {
                $sql .= "WHERE destination in ('".implode("','",$dest)."')";
        }
        $sql .= "ORDER BY name";
        $results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

        foreach ($results as $result) {
                $thisdest = $result['destination'];
                $thisid   = $result['id_cqr'];
                $name = $result['name'] ? $result['name'] : 'CQR ' . $thisid;
                $destlist[] = array(
                        'destination' => $thisdest,
                        'description' => sprintf(_("CQR: %s / Option: %s"),$name,$result['condition']),
                        'edit_url' => 'config.php?display=ivr&action=edit&id_cqr='.urlencode($thisid),
                );
        }
        return $destlist;
}

function nethcqr_change_destination($old_dest, $new_dest) {
        global $db;
	$sql = "UPDATE nethcqr_entries SET dest = '$new_dest' WHERE dest = '$old_dest'";
        $db->query($sql);
}

function nethcqr_getdest($id_dest) {
	//TODO
//        return array('nethcqr-'.$id_dest.',s,1');
}

function nethcqr_getdestinfo($dest) {
//		TODO
/*        global $active_modules;

        if (substr(trim($dest),0,4) == 'nethcqr-') {
                $exten = explode(',',$dest);
                $exten = substr($exten[0],4);

                $thisexten = nethcqr_get_details($id_dest);
                if (empty($thisexten)) {
                        return array();
                } else {
                        //$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
                        return array('description' => sprintf(_("IVR: %s"), ($thisexten['name'] ? $thisexten['name'] : $thisexten['id'])),
                                     'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($exten),
                                                                  );
                }
        } else {
                return false;
        }*/
}




###################################################################################################################################
function nethcqr_get_cqr_list(){
global $db;
$sql = "SELECT `id_cqr`,`name` FROM `cqr`";
$results =& $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::isError($results)) {
	#ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
	return false;
}

//$ret = array();
//foreach ($results as $row)
//	$ret[$row['id_cqr']] = $row['name'];
//return $ret;
return $results;
}

function nethcqr_get_cqr($id_cqr){ //OBSOLETE
global $db;
$id_cqr = mysql_real_escape_string($id_cqr);
$sql = "SELECT * FROM `cqr` WHERE `id_cqr` = $id_cqr";

$results =& $db->getRow($sql,DB_FETCHMODE_ASSOC);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return $results;
}

function nethcqr_edit_cqr($values){
global $db;
if (!nethcqr_check_cqr_values($values)) return false;
$sql = "UPDATE `cqr` set ";
$values['id_cqr']=mysql_real_escape_string($values['id_cqr']);
$values['name']=mysql_real_escape_string($values['name']);
$values['description']=mysql_real_escape_string($values['description']);
$values['use_code']=mysql_real_escape_string($values['use_code']);
$values['manual_code']=mysql_real_escape_string($values['manual_code']);
$values['code_length']=mysql_real_escape_string($values['code_length']);
$values['code_retry']=mysql_real_escape_string($values['code_retry']);
$values['db_type']=mysql_real_escape_string($values['db_type']);
$values['db_url']=mysql_real_escape_string($values['db_url']);
$values['db_name']=mysql_real_escape_string($values['db_name']);
$values['db_user']=mysql_real_escape_string($values['db_user']);
$values['db_pass']=mysql_real_escape_string($values['db_pass']);
$values['query']=mysql_real_escape_string($values['query']);
$sql .= "`name`='".$values['name']."', ";
$sql .= "`description`='".$values['description']."', ";
$sql .= "`use_code`=".$values['use_code'].", ";
$sql .= "`manual_code`=".$values['manual_code'].", ";
$sql .= "`code_length`=".$values['code_length'].", ";
$sql .= "`code_retry`=".$values['code_retry'].", ";
$sql .= "`db_type`='".$values['db_type']."', ";
$sql .= "`db_url`='".$values['db_url']."', ";
$sql .= "`db_name`='".$values['db_name']."', ";
$sql .= "`db_user`='".$values['db_user']."', ";
$sql .= "`db_pass`='".$values['db_pass']."', ";
$sql .= "`query`='".$values['query']."' ";
$sql .= " WHERE `id_cqr` = ".$values['id_cqr'];
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return true;
}

function nethcqr_del_cqr($id_cqr){
global $db;
$id_cqr=mysql_real_escape_string($id_cqr);
$sql = "DELETE FROM `cqr` WHERE `id_cqr`=".$id_cqr;
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
$sql = "DELETE FROM `cqr_dest_cqr` WHERE `id_cqr`=".$id_cqr;
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return true;
}

function nethcqr_new_cqr($values){ 
global $db;
//CHECK IF VALUES ARE OK
if (!nethcqr_check_cqr_values($values)) return false;
//values ok
$sql = 'INSERT INTO `cqr` SET ';
$values['name']=mysql_real_escape_string($values['name']);
$values['description']=mysql_real_escape_string($values['description']);
$values['use_code']=mysql_real_escape_string($values['use_code']);
$values['manual_code']=mysql_real_escape_string($values['manual_code']);
$values['code_length']=mysql_real_escape_string($values['code_length']);
$values['code_retry']=mysql_real_escape_string($values['code_retry']);
$values['db_type']=mysql_real_escape_string($values['db_type']);
$values['db_url']=mysql_real_escape_string($values['db_url']);
$values['db_name']=mysql_real_escape_string($values['db_name']);
$values['db_user']=mysql_real_escape_string($values['db_user']);
$values['db_pass']=mysql_real_escape_string($values['db_pass']);
$values['query']=mysql_real_escape_string($values['query']);
$sql .= "`name`='".$values['name']."', ";
$sql .= "`description`='".$values['description']."', ";
$sql .= "`use_code`=".$values['use_code'].", ";
$sql .= "`manual_code`=".$values['manual_code'].", ";
$sql .= "`code_length`=".$values['code_length'].", ";
$sql .= "`code_retry`=".$values['code_retry'].", ";
$sql .= "`db_type`='".$values['db_type']."', ";
$sql .= "`db_url`='".$values['db_url']."', ";
$sql .= "`db_name`='".$values['db_name']."', ";
$sql .= "`db_user`='".$values['db_user']."', ";
$sql .= "`db_pass`='".$values['db_pass']."', ";
$sql .= "`query`='".$values['query']."' ";


$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
	return false;
}
$sql = "SELECT `id_cqr` FROM cqr WHERE `name` = '".$values['name']."'";
$results =& $db->getOne($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return $results;
}

function nethcqr_check_cqr_values($values){
global $db;
//TODO
return true;
}

function nethcqr_check_destination_values($values){
global $db;
//TODO
return true;
}

function nethcqr_get_destinations_list($id_cqr){ //OBSOLETE
global $db;
if ($id_cqr==='') return false;
if (preg_match ('/^new/',$id_cqr)) return array();
$id_cqr=mysql_real_escape_string($id_cqr);
//$sql = "SELECT * FROM `cqr_dest_cqr` WHERE `id_cqr` = $id_cqr ";
//need order by position
$sql = "SELECT DISTINCT `cqr_dest_cqr`.`id_cqr`,`cqr_dest_cqr`.`id_dest` FROM `cqr_dest_cqr` JOIN `cqr_destinations` ON `cqr_dest_cqr`.`id_dest`=`cqr_destinations`.`id_dest` WHERE `id_cqr`=$id_cqr ORDER BY `position` ASC";
$results =& $db->getAll($sql,DB_FETCHMODE_ASSOC);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return $results;
}

function nethcqr_get_destination($id_dest){
global $db;
$id_dest = mysql_real_escape_string($id_dest);
$sql = "SELECT * FROM `cqr_destinations` WHERE `id_dest` = $id_dest";

$results =& $db->getRow($sql,DB_FETCHMODE_ASSOC);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return $results;
}

function nethcqr_edit_destination($values){ 
global $db;
if (!nethcqr_check_destination_values($values)) return false;
$sql = "UPDATE `cqr_destinations` set ";
$values['id_dest']=mysql_real_escape_string($values['id_dest']);
$values['position']=mysql_real_escape_string($values['position']);
$values['name']=mysql_real_escape_string($values['name']);
$values['description']=mysql_real_escape_string($values['description']);
$values['condition']=mysql_real_escape_string($values['condition']);
$values['destination']=mysql_real_escape_string($values['destination']);
$sql .= "`position`=".$values['position'].", ";
$sql .= "`name`='".$values['name']."', ";
$sql .= "`description`='".$values['description']."', ";
$sql .= "`condition`='".$values['condition']."', ";
$sql .= "`destination`='".$values['destination']."' ";
$sql .= " WHERE `id_dest` = ".$values['id_dest'];
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return true;

}

function nethcqr_del_destination($id_dest){
global $db;
$id_dest=mysql_real_escape_string($id_dest);
$sql = "DELETE FROM `cqr_destinations` WHERE `id_dest`=".$id_dest;
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
$sql = "DELETE FROM `cqr_dest_cqr` WHERE `id_dest`=".$id_dest;
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return true;
}

function nethcqr_new_destination($id_cqr, $values){
global $db;
if (!nethcqr_check_destination_values($values)) return false;
$sql = "INSERT INTO `cqr_destinations` SET ";
$id_cqr=mysql_real_escape_string($id_cqr);
$values['position']=mysql_real_escape_string($values['position']);
$values['name']=mysql_real_escape_string($values['name']);
$values['description']=mysql_real_escape_string($values['description']);
$values['condition']=mysql_real_escape_string($values['condition']);
$values['destination']=mysql_real_escape_string($values['destination']);
$sql .= "`position`='".$values['position']."', ";
$sql .= "`name`='".$values['name']."', ";
$sql .= "`description`='".$values['description']."', ";
$sql .= "`condition`='".$values['condition']."', ";
$sql .= "`destination`='".$values['destination']."' ";
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
$sql = "SELECT `id_dest` FROM `cqr_destinations` WHERE `name`= '".$values['name']."'";
$results =& $db->getOne($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
$id_dest = $results;
$sql = "INSERT INTO `cqr_dest_cqr` SET `id_cqr`=$id_cqr, `id_dest`= $id_dest" ;
$results =& $db->query($sql);
if (DB::isError($results)) {
        #ERROR
        $errmsg = " ".__FUNCTION__;
        echo '<i style=\'box-shadow: 0 0 1.5px 1px red;\'>'.$results->getMessage().$errmsg.' '.$sql.'</i><br />';
        return false;
}
return true;
}

function nethcqr_destination(){
	return array(
	    array(
	      'destination' => 'app-blackhole,hangup,1', 
	      'description' => 'Hangup',
	    ),
	    array(
	      'destination' => 'app-blackhole,congestion,1', 
	      'description' => 'Congestion'
	    ),
	 );
}

function nethcqr_get_config($engine) {
  global $ext;
  
  switch($engine) {
    case 'asterisk':
      // "blackhole" destinations
      $ext->add('app-blackhole', 'hangup', '', new ext_noop('Blackhole Dest: Hangup'));
      $ext->add('app-blackhole', 'hangup', '', new ext_hangup());

      $ext->add('app-blackhole', 'congestion', '', new ext_noop('Blackhole Dest: Congestion'));
      $ext->add('app-blackhole', 'congestion', '', new ext_answer());
      $ext->add('app-blackhole', 'congestion', '', new ext_playtones('congestion'));
      $ext->add('app-blackhole', 'congestion', '', new ext_congestion());
      $ext->add('app-blackhole', 'congestion', '', new ext_hangup());
    break;
  }
}

function nethcqr_get_max_position($id_cqr){
$positions = array();
foreach (nethcqr_get_destinations_list($id_cqr) as $id_dest => $name){
	$values = nethcqr_get_destination($id_dest);
	$positions[$id_dest] = $values['position'];
	}
return max($positions);
}

/*function nethcqr_draw_destination($id_dest,$id_cqr){ //OBSOLETE
global $db;
if ($id_dest ==='') {
	$values=array('name'=>'', 'description'=>'', 'condition'=>'', 'destination'=>'');
	$values['position'] = nethcqr_get_max_position($id_cqr)+1;
	$id_dest = 'JRAND';
	}
else {
	$values = nethcqr_get_destination($id_dest);
	if ($values === false) return false;
}

$pos_label = _('Destination #').$values['position'];
$out = '';
$out .= '<div id="destinations_div_'.$id_dest.'">';
//$out .='<h3>'.$values['position']."</h3>";
$out .= '<table title="'.$pos_label.'">';
// NAME
$out .= "	<tr>";
$out .= '		<td>'._('Name').'</td><td><input id="dest_'.$id_dest.'_name"';
	if ($values['name']!='') $out .= ' readonly ';
$out .= 'type="text" value="'.$values['name'].'"size="35" name="name-field-'.$id_dest.'">';
$out .= '</td>';
$out .= "	</tr>";
//DESCRIPTION
$out .= "  <tr>";
$out .= '          <td>'._('Description').'</td><td><input id="dest_'.$id_dest.'_description" ';
$out .= 'type="text" value="'.$values['description'].'"size="35" name="description-field-'.$id_dest.'">';
$out .= "</td>";
$out .= "  </tr>";
//POSITION
$out .= "       <tr>";
$out .= '               <td>'._('Position').'</td><td><input id="dest_'.$id_dest.'_position"';
$out .= 'type="text" value="'.$values['position'].'"size="35" name="position-field-'.$id_dest.'">';
$out .= '</td>';
$out .= "       </tr>";
//CONDITION
$out .= "  <tr>";
$out .= '          <td>'._('Condition').'</td><td><input id="dest_'.$id_dest.'_condition" ';
$out .= 'type="text" value="'.$values['condition'].'"size="35" name="condition-field-'.$id_dest.'">';
$out .= "</td>";
$out .= "  </tr>";
//DESTINATION
$out .= "  <tr>";
$out .= '          <td>'._('Destination').'</td><td>';
drawselects($values['destination'],0);
//$out .= ' <input id="dest_'.$id_dest.'_destination" ';
//$out .= 'type="text" value="'.$values['destination'].'"size="35" name="destination-field-'.$id_dest.'">';
$out .= "</td>";
$out .= "  </tr>";
$out .= "</table>";
$out .= '<a id="remove_destination_'.$id_dest.'" >
	<span> 
		<img border="0" height="16" width="16" src="images/core_delete.png" alt="" title="'._('Delete destination').' \''.$values['name'].'\'">
		'._('Delete destination').' "'.$values['name'].'" 
	</span>
</a>';


//$out .= '<script>$(\'#remove_destination_'.$id_dest.'\').click(function() {$(\'#destinations_div_'.$id_dest.'\').remove();});</script>';
$out .= "<br /><br /><br />";
$out .= "</div>";
echo $out;
}
*/
/*function nethcqr_draw_destinations($id_cqr){ //OBSOLETE
global $db;
foreach (nethcqr_get_destinations_list($id_cqr) as $dest){
	nethcqr_draw_destination($dest['id_dest'],$id_cqr);
	}
echo '<br />';
}*/

function drawCheckbox($value,$name){
                if ($value==1) 
                        $checked = 'checked'; 
                else
                        $checked = '';
                $cellhtml = '<input type="hidden" name="'.$name.'" value="off">'; //a little hack to have checkbox value setted af "off" in post field
                $cellhtml .='<input type="checkbox" name="'.$name.'" '.$checked.' >';
                return $cellhtml;
}

function nethcqr_draw_cqr($id_cqr){
$out='';
if ($id_cqr=='')$id_cqr='new'.mktime();
if (preg_match ('/^new/',$id_cqr)){
	$is_new=true;
	$out .= '<table title="New Call Query Routing">';
	}
else {
	$values = nethcqr_get_cqr($id_cqr);
	$out .= '<table title="Call Query Routing '.$values['nome'].' ">';
	}

//name
$out .= '<tr><td>'._('Name').'</td><td><input ';
if ($values['name']!='') $out .= ' readonly ';
$out .= 'type="text" value="'.$values['name'].'"size="35" name="name-cqr-'.$id_cqr.'">';
$out .= '</td></tr>';
//description
$out .= '<tr><td>'._('Description').'</td><td><input ';
$out .= 'type="text" value="'.$values['description'].'"size="35" name="description-cqr-'.$id_cqr.'">';
$out .= "</td></tr>";
//use_code
$out .= '<tr><td>'._('Use Code').'</td><td>';
$out .= drawCheckbox($values['use_code'],'use_code-cqr-'.$id_cqr);
$out .= "</td></tr>";
//manual_code
$out .= '<tr><td>'._('Manual Code').'</td><td>';
$out .= drawCheckbox($values['manual_code'],'manual_code-cqr-'.$id_cqr);
$out .= "</td></tr>";
//code_length
$out .= '<tr><td> '._('Code Length').'</td>';
$out .=  '<td><select name="code_length-cqr-'.$id_cqr.'" >';
                for ($i = 0; $i <= 9; $i++) {
                        $out .= '<option value="'.$i.'"';
                        if ($values['code_length']===$i) $out .= ' selected ';
                        $out .= "> $i </option> ";
                        }
                $out .= '</select></td></tr>';
//code_retry
$out .= '<tr><td> '._('Code Retry').'</td>';
$out .=  '<td><select name="code_retry-cqr-'.$id_cqr.'" >';
                for ($i = 0; $i <= 9; $i++) {
                        $out .= '<option value="'.$i.'"';
                        if ($values['code_retry']===$i) $out .= ' selected ';
                        $out .= "> $i </option> ";
                        }
                $out .= '</select></td></tr>';
//db_type
$db_possible_types = array ('mysql','mssql');
$out .= '<tr><td> '._('Database Type').'</td>';
$out .=  '<td><select name="db_type-cqr-'.$id_cqr.'" >';
                foreach ($db_possible_types as $i) {
                        $out .= '<option value="'.$i.'"';
                        if ($values['db_type']===$i) $out .= ' selected ';
                        $out .= "> $i </option> ";
                        }
                $out .= '</select></td></tr>';
//db_url
$out .= '<tr><td>'._('Database Host URL').'</td>';
$out .= '<td><input type="text" value="'.$values['db_url'].'" size="35" name="db_url-cqr-'.$id_cqr.'">';
$out .= '</td></tr>';
//db_name
$out .= '<tr><td>'._('Database Name').'</td>';
$out .= '<td><input type="text" value="'.$values['db_name'].'" size="35" name="db_name-cqr-'.$id_cqr.'">';
$out .= '</td></tr>';
//db_user
$out .= '<tr><td>'._('Database User').'</td>';
$out .= '<td><input type="text" value="'.$values['db_user'].'" size="35" name="db_user-cqr-'.$id_cqr.'">';
$out .= '</td></tr>';
//db_pass
$out .= '<tr><td>'._('Database Password').'</td>';
$out .= '<td><input type="password" value="'.$values['db_pass'].'" size="35" name="db_pass-cqr-'.$id_cqr.'">';
$out .= '</td></tr>';
//query
$out .= '<tr><td>'._('Query').'</td>';
$out .= '<td><textarea name="query-cqr-'.$id_cqr.'" cols="44" rows="5" >'.$values['query'].'</textarea>';
$out .= '</td></tr>';
//////////////////////

$out .= '</table>';
$out .= '<br /><br />';
return $out;
}




















