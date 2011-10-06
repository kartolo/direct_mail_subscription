<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$tempColumns = Array (
	 "tx_directmailsubscription_localgender" => Array (        
        "exclude" => 1,        
        "label" => "LLL:EXT:direct_mail_subscription/locallang_db.xml:tt_address.tx_directmailsubscription_localgender",        
        "config" => Array (
            "type" => "input",    
            "size" => "30",    
            "eval" => "trim",
        )
    ),
	
);


t3lib_div::loadTCA("tt_address");
t3lib_extMgm::addTCAcolumns("tt_address",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("tt_address","tx_directmailsubscription_localgender", '', 'after:gender');

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/','Direct Mail subscription');

t3lib_extMgm::addPlugin(Array("LLL:EXT:direct_mail_subscription/locallang.php:pi_dmail_subscr", "21"));
?>