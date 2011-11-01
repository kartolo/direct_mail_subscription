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

	var $scriptRelPath = 'pi/class.dmailsubscribe.php';
	var $extKey = 'direct_mail_subscription';
	
	/**
	 * Constructor
	 */
	function user_dmailsubscribe()	{
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['feadmin.']['dmailsubscription.'];
		/** 
		 * IK 27.04.09 
		 * include Locallang 
		 */ 
		if ($GLOBALS['TSFE']->config['config']['language']) { 
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
		
		//check loaded LL
		if (!$this->LOCAL_LANG_loaded){
			$this->user_dmailsubscribe();
		}

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
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tt_address',
					'uid='.intval($conf['rec']['uid']),
					array(
						'tx_directmailsubscription_localgender' => $this->pi_getLL('tt_addressGender'.strtoupper($conf['rec']['gender']))
					)
				);
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
	* @param	boolean		If TRUE, the output label is passed through htmlspecialchars()
	* @return	string		The value from LOCAL_LANG.
	*/
	public function pi_getLL($key, $alternativeLabel = '', $hsc = FALSE) {
		if (isset($this->LOCAL_LANG[$this->LLkey][$key][0]['target'])) {
	
			// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
			if (isset($this->LOCAL_LANG_charset[$this->LLkey][$key])) {
				$word = $GLOBALS['TSFE']->csConv(
				$this->LOCAL_LANG[$this->LLkey][$key][0]['target'],
				$this->LOCAL_LANG_charset[$this->LLkey][$key]
				);
			} else {
				$word = $this->LOCAL_LANG[$this->LLkey][$key][0]['target'];
			}
		} elseif ($this->altLLkey && isset($this->LOCAL_LANG[$this->altLLkey][$key][0]['target'])) {
	
			// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
			if (isset($this->LOCAL_LANG_charset[$this->altLLkey][$key])) {
				$word = $GLOBALS['TSFE']->csConv(
				$this->LOCAL_LANG[$this->altLLkey][$key][0]['target'],
				$this->LOCAL_LANG_charset[$this->altLLkey][$key]
				);
			} else {
				$word = $this->LOCAL_LANG[$this->altLLkey][$key][0]['target'];
			}
		} elseif (isset($this->LOCAL_LANG['default'][$key][0]['target'])) {
	
			// Get default translation (without charset conversion, english)
			$word = $this->LOCAL_LANG['default'][$key][0]['target'];
		} else {
	
			// Return alternative string or empty
			$word = (isset($this->LLtestPrefixAlt)) ? $this->LLtestPrefixAlt . $alternativeLabel : $alternativeLabel;
		}
	
		$output = (isset($this->LLtestPrefix)) ? $this->LLtestPrefix . $word : $word;
	
		if ($hsc) {
			$output = htmlspecialchars($output);
		}
	
		return $output;
	}
	
	

	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return	void
	 */
	public function pi_loadLL() {
		if (!$this->LOCAL_LANG_loaded && $this->scriptRelPath) {
			$basePath = 'EXT:' . $this->extKey . '/' . dirname($this->scriptRelPath) . '/locallang.xml';

				// Read the strings in the required charset (since TYPO3 4.2)
			$this->LOCAL_LANG = t3lib_div::readLLfile($basePath,$this->LLkey, $GLOBALS['TSFE']->renderCharset);
			if ($this->altLLkey) {
				$this->LOCAL_LANG = t3lib_div::readLLfile($basePath,$this->altLLkey);
			}

			//compatibility to pre 4.6.x locallang handling
			if (!t3lib_div::compat_version('4.6.0')) {
				$tempLocalLang = $this->LOCAL_LANG;
				unset($this->LOCAL_LANG);
				foreach($tempLocalLang as $langKey => $langArr) {
					foreach ($langArr as $labelK => $labelV ) {
						$this->LOCAL_LANG[$langKey][$labelK][0]['target'] = $labelV;
					}
				}
			}

				// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			$confLL = $this->conf['_LOCAL_LANG.'];
			if (is_array($confLL)) {
				foreach ($confLL as $languageKey => $languageArray) {
						// Don't process label if the langue is not loaded
					$languageKey = substr($languageKey,0,-1);
					if (is_array($languageArray) && is_array($this->LOCAL_LANG[$languageKey])) {
							// Remove the dot after the language key
						foreach ($languageArray as $labelKey => $labelValue) {
							if (!is_array($labelValue))	{
								$this->LOCAL_LANG[$languageKey][$labelKey][0]['target'] = $labelValue;

									// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset"
									// and if that is not set, assumed to be that of the individual system languages
								if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
									$this->LOCAL_LANG_charset[$languageKey][$labelKey] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
								} else {
									$this->LOCAL_LANG_charset[$languageKey][$labelKey] = $GLOBALS['TSFE']->csConvObj->charSetArray[$languageKey];
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
