<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "direct_mail_subscription".
 *
 * Auto generated 30-10-2014 16:15
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Direct Mail Subscription',
    'description' => 'Adds a plugin for subscription to direct mail newsletters (collecting subscriptions in the tt_address table)',
    'category' => 'plugin',
    'shy' => 0,
    'version' => '2.0.4',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 1,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Ivan Kartolo',
    'author_email' => 'ivan.kartolo@gmail.com',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => array(
        'depends' => array(
            'tt_address' => '',
            'typo3' => '7.0.0-8.99.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
    'autoload' => array(
     'psr-4' => array('DirectMailTeam\\DirectMailSubscription\\' => 'Classes'),
      'classmap' => array(
        'pi/class.dmailsubscribe.php',
      )
    ),
    '_md5_values_when_last_written' => 'a:13:{s:9:"ChangeLog";s:4:"2578";s:12:"ext_icon.gif";s:4:"8d58";s:14:"ext_tables.php";s:4:"8517";s:14:"ext_tables.sql";s:4:"1e8e";s:15:"fe_adminLib.inc";s:4:"715e";s:13:"locallang.php";s:4:"1ab3";s:16:"locallang_db.xml";s:4:"f6d4";s:10:"README.txt";s:4:"e4af";s:27:"pi/class.dmailsubscribe.php";s:4:"44f7";s:30:"pi/fe_admin_dmailsubscrip.tmpl";s:4:"a4dd";s:16:"pi/locallang.xml";s:4:"c842";s:20:"static/constants.txt";s:4:"3daf";s:16:"static/setup.txt";s:4:"e9a2";}',
);
