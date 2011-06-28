<?php 
/**
 * Openbiz Cubi 
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   user.form
 * @copyright Copyright &copy; 2005-2009, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id$
 */

/**
 * LoginForm class - implement the logic of login form
 *
 * @package user.form
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class LoginForm extends EasyForm
{
    protected $username;
    protected $password;

    protected $m_LastViewedPage;
    
    protected function readMetadata(&$xmlArr)
    {
        $do = BizSystem::getObject("myaccount.do.PreferenceDO");
        $rs = $do->directFetch("[user_id]='0' AND ([section]='Login' OR [section]='Register' )");
      
        if ($rs)
        {
	        	foreach ($rs as $item)
	        	{        		
	        		$preference[$item["name"]] = $item["value"];        	
	        	}	
        }    	
 		$elem_count = count($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"]);
        for($i=0;$i<$elem_count;$i++){                	
        	switch($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]['ATTRIBUTES']['NAME']){
        		case "antispam":
        			if($preference['anti_spam']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        			}
        			break;

        		case "session_timeout":
        			if($preference['keep_cookies']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        			}
        			break;          			
        			
        		case "current_language":
        			if($preference['language_selector']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        			}
        			break;  
        			      			
        		case "current_theme":
        			if($preference['theme_selector']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        				
        			}
        			break;  

        		case "register_new":
        			if($preference['open_register']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        			}
        			break; 

        		case "forget_pass":
        			if($preference['find_password']==0){
        				unset($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"][$i]);
        			}
        			break;         			
        	}
        }
        $result = parent::readMetaData($xmlArr);
        return $result;
    }  

    public function getSessionVars($sessionContext)
    {
        $sessionContext->getObjVar("SYSTEM", "LastViewedPage", $this->m_LastViewedPage);
        parent::getSessionVars($sessionContext);        
    }
    
	public function fetchData()
	{
		
		if(isset($_COOKIE["SYSTEM_SESSION_USERNAME"]) && isset($_COOKIE["SYSTEM_SESSION_PASSWORD"]))
		{
			$this->username = $_COOKIE["SYSTEM_SESSION_USERNAME"];
			$this->password = $_COOKIE["SYSTEM_SESSION_PASSWORD"];
			
			global $g_BizSystem;
			$svcobj 	= BizSystem::getService(AUTH_SERVICE);
			$eventlog 	= BizSystem::getService(EVENTLOG_SERVICE);
			if ($svcobj->authenticateUserByCookies($this->username,$this->password)) 
    		{
                // after authenticate user: 1. init profile
    			$profile = $g_BizSystem->InitUserProfile($this->username);
    			
    			// after authenticate user: 2. insert login event
    			$logComment=array(	$this->username, $_SERVER['REMOTE_ADDR']);
    			$eventlog->log("LOGIN", "MSG_LOGIN_SUCCESSFUL", $logComment);
    			
    			// after authenticate user: 3. update login time in user record
    	   	    if (!$this->UpdateloginTime())
    	   	        return false;
        	   	    
    	   	    
    	   	    if($profile['roleStartpage'][0])
    			{
    				$redirectPage = APP_INDEX.$profile['roleStartpage'][0];
       	        	BizSystem::clientProxy()->ReDirectPage($redirectPage);	
       	        }else{
    		    	parent::processPostAction();
       	        }
       	        return ;
    		}
		}
	}    
    /**
     * login action
     *
     * @return void
     */
    public function Login()
    {
	  	$recArr = $this->readInputRecord();
	  	try
        {
            $this->ValidateForm();
        }
        catch (ValidationException $e)
        {
            $this->processFormObjError($e->m_Errors);
            return;
        }
	  	
	  	// get the username and password
	
		$this->username = BizSystem::ClientProxy()->getFormInputs("username");
		$this->password = BizSystem::ClientProxy()->getFormInputs("password");

		global $g_BizSystem;
		$svcobj 	= BizSystem::getService(AUTH_SERVICE);
		$eventlog 	= BizSystem::getService(EVENTLOG_SERVICE);
		try {
    		if ($svcobj->authenticateUser($this->username,$this->password)) 
    		{
                // after authenticate user: 1. init profile
    			$profile = $g_BizSystem->InitUserProfile($this->username);
    			
    			// after authenticate user: 2. insert login event
    			$logComment=array(	$this->username, $_SERVER['REMOTE_ADDR']);
    			$eventlog->log("LOGIN", "MSG_LOGIN_SUCCESSFUL", $logComment);
    			
    			// after authenticate user: 3. update login time in user record
    	   	    if (!$this->UpdateloginTime())
    	   	        return false;
    	   	        
    	   	    // after authenticate user: 3. update current theme and language
       			$currentLanguage = BizSystem::ClientProxy()->getFormInputs("current_language");
   				if($currentLanguage!=''){
   				   	if($currentLanguage=='user_default'){
		   				$currentTheme = DEFAULT_LANGUAGE;
		   			}else{
       					BizSystem::sessionContext()->setVar("LANG",$currentLanguage );
		   			}
   				}

				$currentTheme = BizSystem::ClientProxy()->getFormInputs("current_theme");
				if($currentTheme!=''){
					if($currentTheme=='user_default'){
		   				$currentTheme = DEFAULT_THEME_NAME;
		   			}else{
   						BizSystem::sessionContext()->setVar("THEME",$currentTheme );
		   			}
				}
   		
    	   	    $redirectPage = APP_INDEX.$profile['roleStartpage'][0];
    	   	                    		
    	   	    $cookies = BizSystem::ClientProxy()->getFormInputs("session_timeout");
    	   	    if($cookies)
    	   	    {
    	   	    	$password = $this->password;    	   	    	
    	   	    	$password = md5(md5($password.$this->username).md5($profile['create_time']));
    	   	    	setcookie("SYSTEM_SESSION_USERNAME",$this->username,time()+(int)$cookies,"/");
    	   	    	setcookie("SYSTEM_SESSION_PASSWORD",$password,time()+(int)$cookies,"/");
    	   	    }
    			
    	   	    if($this->m_LastViewedPage!=""){
    	   	    	BizSystem::clientProxy()->ReDirectPage($this->m_LastViewedPage);
    	   	    }
       	        elseif($profile['roleStartpage'][0]){
       	        	BizSystem::clientProxy()->ReDirectPage($redirectPage);	
       	        }else{
    		    	parent::processPostAction();
       	        }
    		    return true;
    		}
    		else
    		{ 
    			$logComment=array($this->username,
    								$_SERVER['REMOTE_ADDR'],
    								$this->password);
    			$eventlog->log("LOGIN", "MSG_LOGIN_FAILED", $logComment);
    			    			
    			$errorMessage['password'] = $this->getMessage("PASSWORD_INCORRECT");
    			$errorMessage['login_status'] = $this->getMessage("LOGIN_FAILED");
    			$this->processFormObjError($errorMessage);
    		}
    	}
    	catch (Exception $e) {
    	    BizSystem::ClientProxy()->showErrorMessage($e->getMessage());
    	}
    }
   
    /**
     * Update login time
     *
     * @return void
     */
    protected function UpdateloginTime()
    {
        $userObj = BizSystem::getObject('system.do.UserDO');
        try {
            $curRecs = $userObj->directFetch("[username]='".$this->username."'", 1);
            $dataRec = new DataRecord($curRecs[0], $userObj);
            $dataRec['lastlogin'] = date("Y-m-d H:i:s");
            $ok = $dataRec->save();
            if (! $ok) {
                $errorMsg = $userObj->getErrorMessage();
                BizSystem::log(LOG_ERR, "DATAOBJ", "DataObj error = ".$errorMsg);
                BizSystem::ClientProxy()->showErrorMessage($errorMsg);
                return false;
            }
        } 
        catch (BDOException $e) 
        {
            $errorMsg = $e->getMessage();
            BizSystem::log(LOG_ERR, "DATAOBJ", "DataObj error = ".$errorMsg);
            BizSystem::ClientProxy()->showErrorMessage($errorMsg);
            return false;
        }
        return true;
   }
   
   public function ChangeLanguage(){
   		$currentLanguage = BizSystem::ClientProxy()->getFormInputs("current_language");
   		if($currentLanguage!=''){
   		   	if($currentLanguage=='user_default'){
   				$currentTheme = DEFAULT_LANGUAGE;
   			}else{
		   		BizSystem::sessionContext()->setVar("LANG",$currentLanguage );
				$this->m_Notices[] = "<script>window.location.reload()</script>";
		   		$this->UpdateForm();   
   			}				
   		}
   		return;
   }
   
   public function ChangeTheme(){
   		$currentTheme = BizSystem::ClientProxy()->getFormInputs("current_theme");
   		if($currentTheme!=''){
   			if($currentTheme=='user_default'){
   				$currentTheme = DEFAULT_THEME_NAME;
   			}else{
		   		BizSystem::sessionContext()->setVar("THEME",$currentTheme );
		   		$recArr = $this->readInputRecord();
        		$this->setActiveRecord($recArr);
				$this->m_Notices[] = "<script>window.location.reload()</script>";
		   		$this->UpdateForm(); 
   			}
   		} 
   		return;
   }


}  
?>