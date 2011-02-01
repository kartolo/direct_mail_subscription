<?php
class user_dmailsubscribe {
	
	var $cObj; //Instance of tslib_content
	var $LOCAL_LANG = array();			// Local Language content
	var $LOCAL_LANG_charset = array();	// Local Language content charset for individual labels (overriding)
	var $LOCAL_LANG_loaded = 0;			// Flag that tells if the locallang file has been fetch (or tried to be fetched) already.
	var $LLkey = 'default';				// Pointer to the language to use.
	var $altLLkey = '';					// Pointer to alternative fall-back language to use.
	var $LLtestPrefix = '';				// You can set this during development to some value that makes it easy for you to spot all labels that ARe delivered by the getLL function.
	var $LLtestPrefixAlt = '';			// Save as LLtestPrefix, but additional prefix for the alternative value in getLL() function calls


	/**
	 * Constructor
	 */
	function user_dmailsubscribe()	{
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		
		/** 
		 * IK 27.04.09 
		 * include Locallang 
		 */ 
		if ($GLOBALS['TSFE']->config['config']['language'])     { 
			$this->LLkey = $GLOBALS['TSFE']->config['config']['language']; 
			if ($GLOBALS['TSFE']->config['config']['language_alt']) { 
				$this->altLLkey = $GLOBALS['TSFE']->config['config']['language_alt']; 
			} 
		} 
		$this->pi_loadLL(); 
	}

	/**
	 * Userfunc called per TS to create categories check boxes
	 * @param $content 
	 * @param $conf TS conf
	 */
	function makeCheckboxes($content,$conf) {
		$content = '';
		$pid = $this->cObj->stdWrap($conf['pid'],$conf['pid.']);



		if($address_uid = t3lib_div::_GP('rU')) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_dmail_ttaddress_category_mm','uid_local='.intval($address_uid));
			$subscribed_to=array();
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$subscribed_to[] = $row['uid_foreign'];
			}
			$subscribed_to_list = implode(',',$subscribed_to);
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_dmail_category','l18n_parent=0 AND pid='.intval($pid).$this->cObj->enableFields('sys_dmail_category'));

		$i=1;
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$checked = t3lib_div::inList($subscribed_to_list,$row['uid']);
			//$content .= $row['category'].'<input type="checkbox" '.($checked?'checked':'').' name="FE[tt_address][module_sys_dmail_category][]" value="'.$row['uid'].'" /><br />';

			//Stanislas way of doing localization is different, alsways subscred to the original uid, and not the translated overlay records.

			if($theRow = $GLOBALS['TSFE']->sys_page->getRecordOverlay('sys_dmail_category',$row,$GLOBALS['TSFE']->sys_language_uid,$conf['hideNonTranslatedCategories']?'hideNonTranslated':'')) {
				$content .= '<label for="option-'.$i.'">'.htmlspecialchars($theRow['category']).'</label><input id="option-'.$i.'" type="checkbox" '.($checked?'checked':'').' name="FE[tt_address][module_sys_dmail_category]['.$row['uid'].']" value="1" /><div class="clearall"></div>';
			}
			$i++;
		}
		return $content;
	}
	
	/**
	 * userFunc on save of the record
	 * @param $conf
	 */
	function saveRecord($conf)    {
		//print "TEST";
		//t3lib_div::print_array($conf);
		if(intval($conf['rec']['uid'])) {
			$fe = t3lib_div::_GP('FE');
			$newFieldsArr = $fe['tt_address']['module_sys_dmail_category'];

			//$newFields = implode(',',$newFieldsArr);
			//print "NewFields: $newFields<br />";
			$count = 0;
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_dmail_ttaddress_category_mm','uid_local='.$conf['rec']['uid']);
			if(is_array($newFieldsArr)) {
				foreach(array_keys($newFieldsArr) as $uid) {
					if (is_numeric($uid)) {
						$count++;
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_dmail_ttaddress_category_mm', array('uid_local' => intval($conf['rec']['uid']), 'uid_foreign' => intval($uid), 'sorting' => $count));
					}
				}
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_address','uid='.intval($conf['rec']['uid']), array('module_sys_dmail_category' => $count));
			}

			/** 
			 * IK: 27.04.09 
			 * localized title in own field 
			 */ 
			if (t3lib_div::inList('m,f', $conf['rec']['gender'])) { 
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_address','uid='.intval($conf['rec']['uid']),array('tx_directmailsubscription_localgender'=>$this->pi_getLL('tt_address.gender.'.$conf['rec']['gender'])));
			} 
		}
		return;
	}


	/**
	 * LOCALLANG copied from pibase
	 */


	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 *
	 * @param	string		The key from the LOCAL_LANG array for which to return the value.
	 * @param	string		Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
	 * @param	boolean		If true, the output label is passed through htmlspecialchars()
	 * @return	string		The value from LOCAL_LANG.
	 */
	function pi_getLL($key,$alt='',$hsc=FALSE)	{
		if (isset($this->LOCAL_LANG[$this->LLkey][$key]))	{
			$word = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->LLkey][$key], $this->LOCAL_LANG_charset[$this->LLkey][$key]);	// The "from" charset is normally empty and thus it will convert from the charset of the system language, but if it is set (see ->pi_loadLL()) it will be used.
		} elseif ($this->altLLkey && isset($this->LOCAL_LANG[$this->altLLkey][$key]))	{
			$word = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->altLLkey][$key], $this->LOCAL_LANG_charset[$this->altLLkey][$key]);	// The "from" charset is normally empty and thus it will convert from the charset of the system language, but if it is set (see ->pi_loadLL()) it will be used.
		} elseif (isset($this->LOCAL_LANG['default'][$key]))	{
			$word = $this->LOCAL_LANG['default'][$key];	// No charset conversion because default is english and thereby ASCII
		} else {
			$word = $this->LLtestPrefixAlt.$alt;
		}

		$output = $this->LLtestPrefix.$word;
		if ($hsc)	$output = htmlspecialchars($output);

		return $output;
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return	void
	 */
	function pi_loadLL()	{
		if (!$this->LOCAL_LANG_loaded)	{
			$basePath = t3lib_extMgm::extPath('direct_mail_subscription').'pi/locallang.php';

				// php or xml as source: In any case the charset will be that of the system language.
				// However, this function guarantees only return output for default language plus the specified language (which is different from how 3.7.0 dealt with it)
			$this->LOCAL_LANG = t3lib_div::readLLfile($basePath,$this->LLkey);
			if ($this->altLLkey)	{
				$tempLOCAL_LANG = t3lib_div::readLLfile($basePath,$this->altLLkey);
				$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(),$tempLOCAL_LANG);
			}

				// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			if (is_array($this->conf['_LOCAL_LANG.']))	{
				reset($this->conf['_LOCAL_LANG.']);
				while(list($k,$lA)=each($this->conf['_LOCAL_LANG.']))	{
					if (is_array($lA))	{
						$k = substr($k,0,-1);
						foreach($lA as $llK => $llV)	{
							if (!is_array($llV))	{
								$this->LOCAL_LANG[$k][$llK] = $llV;
								if ($k != 'default')	{
									$this->LOCAL_LANG_charset[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];	// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages (thus no conversion)
								}
							}
						}
					}
				}
			}
		}
		$this->LOCAL_LANG_loaded = 1;
	}
}
?>
