<?php
global $db;

$check = $db->getRow('SELECT `use_workphone` from `nethcqr_details` ');
if(DB::IsError($check)) {
    $check = $db->getAll('DESCRIBE nethcqr_details',DB_FETCHMODE_ASSOC);
    if(!DB::IsError($check)){
        $db->query('ALTER TABLE `nethcqr_details` ADD COLUMN `use_workphone` BOOLEAN default TRUE AFTER manual_code');
    }
}

