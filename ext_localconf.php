<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
  'mod {
    wizards.newContentElement.wizardItems.plugins {
      elements {
	  directmailsubscription_pi1 {
	  title = LLL:EXT:pt_team/Resources/Private/Language/locallang_db.xlf:tx_pt_team_domain_model_feteam
	  description = LLL:EXT:pt_team/Resources/Private/Language/locallang_db.xlf:tx_pt_team_domain_model_feteam.description
	  tt_content_defValues {
	    CType = directmailsubscription_pi1
	  }
	}
      }
    show = *
    }
  }'
);