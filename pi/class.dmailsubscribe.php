<?php
class user_dmailsubscribe {
    var $cObj; //Instance of tslib_content
	/**
	 * Constructor
	 */
	function user_dmailsubscribe()	{
        $this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}
	/**
	 *
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

        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $checked = t3lib_div::inList($subscribed_to_list,$row['uid']);
            //$content .= $row['category'].'<input type="checkbox" '.($checked?'checked':'').' name="FE[tt_address][module_sys_dmail_category][]" value="'.$row['uid'].'" /><br />';

                //Stanislas way of doing localization is different, alsways subscred to the original uid, and not the translated overlay records.

            if($theRow = $GLOBALS['TSFE']->sys_page->getRecordOverlay('sys_dmail_category',$row,$GLOBALS['TSFE']->sys_language_uid,$conf['hideNonTranslatedCategories']?'hideNonTranslated':'')) {
                $content .= $theRow['category'].'<input type="checkbox" '.($checked?'checked':'').' name="FE[tt_address][module_sys_dmail_category]['.$row['uid'].']" value="1" /><br />';
            }

        }
        return $content;
	}
    /**
    *
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
			if(is_array($newFieldsArr)) {
				foreach(array_keys($newFieldsArr) as $uid) {
					$count++;
                    $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_dmail_ttaddress_category_mm',array('uid_local'=>$conf['rec']['uid'],'uid_foreign'=>$uid,'sorting'=>$count));
				}
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_address','uid='.intval($conf['rec']['uid']),array('module_sys_dmail_category'=>$count));
			}
	    }
        return;
    }
}
?>
