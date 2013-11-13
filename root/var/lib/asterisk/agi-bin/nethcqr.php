#!/usr/bin/php
<?php
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
define("AMPORTAL_CONF", "/etc/amportal.conf");
define("TESTING", true);
$debug=true;
include(AGIBIN_DIR."/phpagi.php");

require_once('DB.php');

$agi = new AGI();
nethcqr_debug("NethCQR AGI script started!");

$amp_conf=parse_amportal_conf(AMPORTAL_CONF);

$id_cqr = $argv[1];

if ($id_cqr == '') {
	nethcqr_debug("ERROR: id_cqr cannot be empty!");
	exit (1);
}

//Setup database connection:
$asterisk_db_user = $amp_conf["AMPDBUSER"];
$asterisk_db_pass = $amp_conf["AMPDBPASS"];
$asterisk_db_url = 'localhost';
$asterisk_db_name = 'asterisk';
$asterisk_db_type = 'mysql';
$datasource = $asterisk_db_type.'://'.$asterisk_db_user.':'.$asterisk_db_pass.'@'.$asterisk_db_url.'/'.$asterisk_db_name;
$asterisk_db =& DB::connect($datasource);
if (PEAR::isError($asterisk_db)|| ($asterisk_db instanceof DB_Error) ) {
	nethcqr_debug("Error conecting to asterisk database, skipped".$asterisk_db->getMessage());//DEBUG
} else {
	nethcqr_debug("Connected to asterisk db");
	$cqr = nethcqr_get_details($id_cqr);
}

$variables = array (
                'DATE' => date("Y-m-d G:i:s"),
                'CID' => $agi->request['agi_callerid'],
                );

$variables['CUSTOMERCODE'] = nethcqr_get_customer_code($cqr,$variables['CID']);

nethcqr_debug(print_r($variables, true));

$entries = nethcqr_get_entries($id_cqr);
$cqr['query'] = nethcqr_evaluate($cqr['query'],$variables);


if (!isset($variables['CUSTOMERCODE']) || $variables['CUSTOMERCODE'] === 0){ 
	//cliente non trovato. 
	//TODO $variables['CUSTOMERCODE']=$variables['CID']	?	
	nethcqr_goto_destination($cqr['default_destination'],2);
} else { //CUSTOMERCODE != 0
	//setup db connection with user defined data
	$datasource = $cqr['db_type'].'://'.$cqr['db_user'].':'.$cqr['db_pass'].'@'.$cqr['db_url'].'/'.$cqr['db_name'];
	$user_defined_db =& DB::connect($datasource);
	nethcqr_debug ($datasource);
	if(PEAR::isError($user_defined_db)|| ($user_defined_db instanceof DB_Error) ) {
		nethcqr_debug(__FUNCTION__." error: ".$user_defined_db->getMessage());
		nethcqr_goto_destination($cqr['default_destination'],1);
	} else { //'USER DB CONNECTED'
		$cqr_query_results = $user_defined_db->getCol($cqr['query']); //we expect one column from our query
		if($user_defined_db->isError($cqr_query_results)) {
                	nethcqr_debug(__FUNCTION__." error: ".$cqr_query_results->getMessage());
			nethcqr_goto_destination($cqr['default_destination'],1);
        	} else {//USER QUERY EXECUTED 
			nethcqr_debug($cqr_query_results);
			foreach ($cqr_query_results as $cqr_query_result) //search for equal condition in $entries
				foreach ($entries as $entrie)
					if ($cqr_query_result === $entrie['condition']) {//WIN
						nethcqr_debug("$cqr_query_result === ".$entrie['condition']." -> ".$entrie['destination']);
						nethcqr_goto_destination($entrie['destination']);
					}
			}
	}  //END 'USER DB CONNECTED'
} //END 'CUSTOMERCODE != 0' 

########################################################################################################################################################

function parse_amportal_conf($filename) {
        $file = file($filename);
        foreach ($file as $line) {
                if (preg_match("/^\s*([a-zA-Z0-9]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) {
                        $conf[ $matches[1] ] = $matches[2];
                }
        }
        return $conf;
}

function agiexit($prio) {
        global $agi;
        $agi->set_priority($prio);
        exit(0);
}

function nethcqr_get_customer_code($cqr,$cid){
	$customer_code = 0;
	//return 0 if (use_code == 0) || (manual_code == 0 and CID isn't present in DB)
	if ($cqr['use_code']==0) return 0;
	//try to get customer code from db
	if ($cid != 0 && $cid != ''){
		$cc_datasource = $cqr['cc_db_type'].'://'.$cqr['cc_db_user'].':'.$cqr['cc_db_pass'].'@'.$cqr['cc_db_url'].'/'.$cqr['cc_db_name'];
		$cc_db =& DB::connect($cc_datasource);
		if (PEAR::isError($cc_db)|| ($cc_db instanceof DB_Error) ) {
        		nethcqr_debug(__FUNCTION__." Error conecting to customer code database, skipped ".$cc_db->getMessage());//DEBUG
			nethcqr_debug("cc_datasource = $cc_datasource");
		} else {
        		nethcqr_debug(__FUNCTION__." Connected to customer code database");
 			//try to find customer code
			$cqr['cc_query'] = nethcqr_evaluate($cqr['cc_query']);
			$customer_code = $cc_db->getOne($cqr['cc_query']);
			if ($cc_db->isError($customer_code)){
        	        	nethcqr_debug(__FUNCTION__." error: ".$customer_code->getMessage());
				$customer_code = 0;
        		}
			if ($customer_code != 0 && $customer_code != '') return $customer_code;
		}
	}
	//$customer_code not found or error getting it from db, call manual code
	if ($cqr['manual_code']==='1'){
		$customer_code = nethcqr_get_manual_customer_code($cqr);
		}
	return $customer_code;
}

function nethcqr_get_manual_customer_code($cqr){
	//TODO
	global $agi;
	$try=1;
	$pinchr='';
	$codcli='';
	nethcqr_debug(__FUNCTION__);
	//$welcome_audio_file = "custom/chiusura";
        $welcome_audio_file = recordings_get_file($cqr["cod_cli_announcement"]);
	if ($cqr['code_retries']==0) $infinite = true;
	else $infinite = false;
	while($try <= $cqr['code_retries']|| $infinite){
        	$pin = $agi->fastpass_stream_file(&$buf,$welcome_audio_file,'1234567890#');
		nethcqr_debug($pin);
		nethcqr_debug($buf);
        	if ($pin['result'] >0)
        		$codcli=chr($pin['result']);
		# ciclo in attesa di numeri (codcli) fino a che non viene messo # o il numero di caratteri è < $cqr['code_length']
		while($pinchr != "#" && strlen($codcli) < $cqr['code_length']) {
        		$pin = $agi->wait_for_digit("6000");
			$pinchr=chr($pin['result']);	
			nethcqr_debug($pin);
			if ($pin['code'] != AGIRES_OK || $pin['result'] <= 0 ) { #non funziona dtmf, vado avanti 
				nethcqr_debug("dtmf isn't working");
                 		return false;
            		} elseif ($pinchr >= "0" and $pinchr <= "9") {
                		$codcli = $codcli.$pinchr;
            		}
        	nethcqr_debug("Codcli: ".$pin['result']."-".$pin['code']."-".$codcli,1);
        	}
	if (nethcqr_check_customer_code($codcli)) return $codcli;
	else { 
               //$agi->stream_file("custom/cod_errato");
               $err_msg = recordings_get_file($cqr["err_announcement"]);
               $agi->stream_file("custom/".$err_msg); # codice errato o inesistente
             }
        $try++;
	}
    return false;
}

function nethcqr_check_customer_code($codcli){
	//TODO
	return true;
}

function get_id($tstamp=0,$tn=0) {
//TODO
/*    global $db_ot;
    if ($tstamp==0)
         $query = "Select max(id) from ticket";
    else
         $query = "Select id from ticket where create_time_unix=$tstamp and tn='$tn'";
    nethcqr_debug("$query $tstamp $tn",1);
    $row = $db_ot->getAll($query);
    if (count($row) == 1) {
        $ticket_id = $row[0][0];
    } else {
        nethcqr_debug("Impossibile ottenere crmid",1);
        exit(0);
    }
    return $ticket_id;*/
}

function nethcqr_get_details($id_cqr=false){
	global $asterisk_db;
        $id_cqr = mysql_real_escape_string($id_cqr);
        $sql = "SELECT * FROM `nethcqr_details`";
        if ($id_cqr) $sql .=" WHERE `id_cqr` = '$id_cqr'";
	$results =& $asterisk_db->getAll($sql,DB_FETCHMODE_ASSOC);
        if ($asterisk_db->isError($results)){
		nethcqr_debug(__FUNCTION__." error: ".$results->getMessage());
                return false;
        }
	nethcqr_debug(__FUNCTION__.': '.print_r($results[0],true));
        return $results[0];
}

function check_pin($codcli) {
//TODO
/*    global $db_ot;
    $query = "Select name,city from customer_company WHERE customer_id=$codcli";
    nethcqr_debug("$query",1);
    $row = $db_ot->getAll($query);
    if (count($row) > 0) {
        return $row[0][0]." (".$row[0][1].")";
    } else {
        return "";
    }*/
}

function check_tel($number) {
//TODO
/*    global $db_ot;
    $query = "Select customer_id from customer_custom WHERE value='$number' limit 1";
    $cidresult=$db_ot->getAll($query);
    if (count($cidresult) > 0) {
        return $cidresult[0][0];
    } else {
        return 0;
    }*/
}

function nethcqr_menu($file,$options,$tries=3) {
    global $agi;
    $choice = NULL;
    $nt=1;
    while(is_null($choice) and $nt <= $tries) {
        $ret = $agi->get_data($file,4000,1);
        $nt++;
        if($ret['code'] != AGIRES_OK || $ret['result'] == -1)
           $choice = -1;
        elseif($ret['result'] != '' and $ret['result']<=$options)
           $choice = $ret['result'];
     }
     nethcqr_debug("neth-menu: ".$choice);
     if ($choice >=1 and $choice<=$options)
             return $choice;
     else
             return -1;
}

function nethcqr_codcli($tries=3) { //OBSOLETE
    global $agi;
    $try=1;
    $pinchr='';
    $codcli='';
    while($try <= $tries) {
    # riproduco il messaggio, mi fermo se sento un numero
    $cod_cli_msg = recordings_get_file($cqr["cod_cli__announcement"]);
//        $pin=$agi->stream_file("custom/benvenutocodice",'1234567890#');
        $pin=$agi->stream_file("custom/".$cod_cli_msg,'1234567890#');
	nethcqr_debug("1");
        if ($pin['result'] >0)
                $codcli=chr($pin['result']);
    # ciclo in attesa di numeri (codcli) fino a che non viene messo #
        while($pinchr != "#") {
		nethcqr_debug("2");
            $pin = $agi->wait_for_digit("6000");
            $pinchr=chr($pin['result']);
            if ($pin['code'] != AGIRES_OK || $pin['result'] <= 0 ) { #non funziona dtmf, vado avanti 
                 return false;
            } elseif ($pinchr >= "0" and $pinchr <= "9") {
                $codcli = $codcli.$pinchr;
            }
	nethcqr_debug("Codcli: ".$pin['result']."-".$pin['code']."-".$codcli,1);
        }

        $cliente = check_pin($codcli);
        if ($cliente == "") { # codice inserito, ma inesistente
            $err_msg = recordings_get_file($cqr["err_announcement"]);
//            $agi->stream_file("custom/cod_errato"); # codice errato o inesistente
            $agi->stream_file("custom/".$err_msg); # codice errato o inesistente
            $pin=0;
            $pinchr='';
            $codcli='';
        } else {
            return $codcli; # codcli OK, esco
        }
        $try++;
    }
    return false;
}

function nethcqr_debug($text) {
    global $agi;
    global $debug;
    if ($debug)
        $agi->verbose($text);
}

function nethcqr_evaluate($msg,$vars=false){
	//$variables = array ('VAR_NAME_IN_MSG' => var_value, ....)
	//VAR_NAME_IN_MSG : NAME, PIPPO,FOOBAR
	//$msg example: "SELECT * FROM '%TABLE%'"
	//var_value : 'pippo'
	//expected return: "SELECT * FROM pippo"
	global $variables;
	if (!$vars) $vars=$variables;
	nethcqr_debug(__FUNCTION__.': 1 -'.$msg);
	foreach ($vars as $variable_name => $variable_value ){
		$msg = preg_replace('/%'.$variable_name.'%/',$variable_value,$msg);
	}
	nethcqr_debug(__FUNCTION__.': 2 - '.$msg);
	return $msg;

}

function nethcqr_query($id_cqr,$variables=false){
	$query = nethcqr_get_query($id_cqr);
	
}

function nethcqr_get_entries($id_cqr){
	global $asterisk_db;
	$sql = "SELECT * FROM nethcqr_entries WHERE `id_cqr`='$id_cqr' ORDER BY `position` ASC";
	$entries = $asterisk_db->getAll($sql, DB_FETCHMODE_ASSOC); 
	if($asterisk_db->IsError($entries)){
        	nethcqr_debug(__FUNCTION__." error ".$entries->getMessage());
		return false;
                }
	
	nethcqr_debug(__FUNCTION__.': '.print_r($entries,true));
        return $entries;
}

function nethcqr_goto_destination($destination,$exit=0){
	global $agi;
	nethcqr_debug(__FUNCTION__.": goto $destination");
	$agi->exec_go_to($destination);	
	exit($exit);
}





