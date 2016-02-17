<?php
    use TYPO3\CMS\Core\Utility\GeneralUtility;

class user_dmailsubscribe
{
    /*
     * Instance of tslib_content
     *
     * @var TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /*
     * Local Language content
     *
     * @var array
     */
    public $LOCAL_LANG = array();

    /*
     * Local Language content charset for individual labels (overriding)
     *
     * @var array
     */
    public $LOCAL_LANG_charset = array();

    /*
     * Flag that tells if the locallang file has been fetch
     * (or tried to be fetched) already.
     *
     * @var int
     */
    public $LOCAL_LANG_loaded = 0;

    /*
     * Pointer to the language to use.
     *
     * @var string
     */
    public $LLkey = 'default';

    /*
     * Pointer to alternative fall-back language to use.
     *
     * @var string
     */
    public $altLLkey = '';

    /*
     * You can set this during development to some value that
     * makes it easy for you to spot all labels
     * that are delivered by the getLL function.
     *
     * @var string
     */
    public $LLtestPrefix = '';

    /*
     * Save as LLtestPrefix, but additional prefix for
     * the alternative value in getLL() function calls
     *
     * @var string
     */
    public $LLtestPrefixAlt = '';

    public $scriptRelPath = 'pi/class.dmailsubscribe.php';

    public $extKey = 'direct_mail_subscription';

    /**
     * Constructor.
     */
    public function user_dmailsubscribe()
    {
        $this->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
        $this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['feadmin.']['dmailsubscription.'];
        /*
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
     * Userfunc called per TS to create categories check boxes.
     *
     * @param string $content The content
     * @param array  $conf    TS conf array
     *
     * @return string The check boxes HTML
     */
    public function makeCheckboxes($content, $conf)
    {
        $content = '';
        $pid = $this->cObj->stdWrap($conf['pid'], $conf['pid.']);

        if (($addressUid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('rU'))) {
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                '*',
                'sys_dmail_ttaddress_category_mm',
                'uid_local='.intval($addressUid)
            );

            $subscribedTo = array();
            while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
                $subscribedTo[] = $row['uid_foreign'];
            }
            $subscribedToList = implode(',', $subscribedTo);
        }

        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            'sys_dmail_category',
            'l18n_parent=0 AND pid='.intval($pid).
            $this->cObj->enableFields('sys_dmail_category')
        );

        $i = 1;
        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($res))) {
            $checked = GeneralUtility::inList($subscribedToList, $row['uid']);

            $theRow = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                'sys_dmail_category',
                $row,
                $GLOBALS['TSFE']->sys_language_uid,
                $conf['hideNonTranslatedCategories'] ? 'hideNonTranslated' : ''
            );

            if ($theRow) {
                $content .= '<label for="option-'.$i.'">'.htmlspecialchars($theRow['category']).'</label>'.
                '<input id="option-'.$i.'" type="checkbox" '.($checked ? 'checked' : '').
                        ' name="FE[tt_address][module_sys_dmail_category]['.$row['uid'].']" value="1" />'.
                '<div class="clearall"></div>';
            }
            ++$i;
        }

        return $content;
    }

    /**
     * UserFunc on save of the record.
     *
     * @param array $conf The data
     */
    public function saveRecord(array $conf)
    {
        //check loaded LL
        if (!$this->LOCAL_LANG_loaded) {
            $this->user_dmailsubscribe();
        }

        if (intval($conf['rec']['uid'])) {
            $fe = GeneralUtility::_GP('FE');
            $newFieldsArr = $fe['tt_address']['module_sys_dmail_category'];

            $count = 0;
            $this->getDatabaseConnection()->exec_DELETEquery(
                'sys_dmail_ttaddress_category_mm',
                'uid_local=' . $conf['rec']['uid']
            );

            if (is_array($newFieldsArr)) {
                foreach (array_keys($newFieldsArr) as $uid) {
                    if (is_numeric($uid)) {
                        // set the new categories to the mm table
                        ++$count;
                        $this->getDatabaseConnection()->exec_INSERTquery(
                            'sys_dmail_ttaddress_category_mm',
                            array(
                                'uid_local' => intval($conf['rec']['uid']),
                                'uid_foreign' => intval($uid),
                                'sorting' => $count,
                            )
                        );
                    }
                }

                // update the tt_address record with the amount of assigned categories
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    'tt_address',
                    'uid='.intval($conf['rec']['uid']),
                    array(
                        'module_sys_dmail_category' => $count,
                    )
                );
            }

            /*
             * IK: 27.04.09
             * localized title in own field
             */
            if (GeneralUtility::inList('m,f', $conf['rec']['gender'])) {
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    'tt_address',
                    'uid='.intval($conf['rec']['uid']),
                    array(
                        'tx_directmailsubscription_localgender' => $this->pi_getLL('tt_addressGender'.strtoupper($conf['rec']['gender'])),
                    )
                );
            }
        }

        return;
    }

    /**
     * LOCALLANG copied from pibase.
     */

    /**
     * Returns the localized label  of the LOCAL_LANG key, $key
     * Notice that for debugging purposes prefixes for the output values
     * can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix.
     *
     * @param string $key              The key from the LOCAL_LANG array for which to return the value.
     * @param string $alternativeLabel Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
     * @param bool   $hsc              If TRUE, the output label is passed through htmlspecialchars()
     *
     * @return string The value from LOCAL_LANG.
     */
    public function pi_getLL($key, $alternativeLabel = '', $hsc = false)
    {
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
            $word = (isset($this->LLtestPrefixAlt)) ? $this->LLtestPrefixAlt.$alternativeLabel : $alternativeLabel;
        }

        $output = (isset($this->LLtestPrefix)) ? $this->LLtestPrefix.$word : $word;

        if ($hsc) {
            $output = htmlspecialchars($output);
        }

        return $output;
    }

    /**
     * Loads local-language values by looking for a "locallang.php" file
     * in the plugin class directory ($this->scriptRelPath) and if found includes it.
     * Also locallang values set in the TypoScript property "_LOCAL_LANG"
     * are merged onto the values found in the "locallang.php" file.
     */
    public function pi_loadLL()
    {
        if (!$this->LOCAL_LANG_loaded && $this->scriptRelPath) {
            $basePath = 'EXT:'.$this->extKey.'/'.dirname($this->scriptRelPath).'/locallang.xml';

            $languageFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LocalizationFactory::class);

                // Read the strings in the required charset (since TYPO3 4.2)
            $this->LOCAL_LANG = $languageFactory->getParsedData($basePath, $this->LLkey, $GLOBALS['TSFE']->renderCharset);
            if ($this->altLLkey) {
                $this->LOCAL_LANG = $languageFactory->getParsedData($basePath, $this->altLLkey);
            }

            // Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
            $typoscriptLocallang = $this->conf['_LOCAL_LANG.'];
            if (is_array($typoscriptLocallang)) {
                foreach ($typoscriptLocallang as $languageKey => $languageArray) {
                    // Don't process label if the langue is not loaded
                    $languageKey = substr($languageKey, 0, -1);
                    if (is_array($languageArray) && is_array($this->LOCAL_LANG[$languageKey])) {
                        // Remove the dot after the language key
                        foreach ($languageArray as $labelKey => $labelValue) {
                            if (!is_array($labelValue)) {
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

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
