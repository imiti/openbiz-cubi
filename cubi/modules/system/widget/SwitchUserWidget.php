<?php
/**
 * Openbiz Cubi Application Platform
 *
 * LICENSE http://code.google.com/p/openbiz-cubi/wiki/CubiLicense
 *
 * @package   cubi.system.widget
 * @copyright Copyright (c) 2005-2011, Openbiz Technology LLC
 * @license   http://code.google.com/p/openbiz-cubi/wiki/CubiLicense
 * @link      http://code.google.com/p/openbiz-cubi/
 * @version   $Id$
 */

class SwitchUserWidget extends EasyForm
{
	public $m_ShowWidget = false;
	public function fetchData()
	{		
		$this->processUserInit();
		
		if(!BizSystem::allowUserAccess('Session.Switch_Session'))
		{
			$this->m_ShowWidget = false;	
			if(!BizSystem::sessionContext()->getVar("_PREV_USER_PROFILE"))
			{
				return ;
			}else{
				$this->m_ShowWidget = true;
			}
		}else{			
			$this->m_ShowWidget = true;
		}		
		$record['username'] = BizSystem::getUserProfile("username");
		return $record;
	}
	
	public function processUserInit()
	{
		$prefService = BizSystem::getService(PREFERENCE_SERVICE);
		$userId = BizSystem::getUserProfile("Id");
		$currentView = $this->getViewObject()->m_Name;
		if($currentView!= 'myaccount.view.ResetPasswordView'){
			if((int)$prefService->getPreference("force_change_passwd")==1)
			{
				BizSystem::clientProxy()->redirectPage(APP_INDEX.'/myaccount/reset_password/force');
				return;
			}
		}
		/*
		if($currentView!= 'myaccount.view.ResetPasswordView'){
			if($prefService->getPreference("force_change_passwd")==1)
			{
				BizSystem::clientProxy()->redirectPage(APP_INDEX.'/myaccount/reset_password/force');
				return;
			}
		}
		*/
	}
	
	public function SwitchSession()
	{
		
		if(!BizSystem::allowUserAccess('Session.Switch_Session'))
		{
			if(!BizSystem::sessionContext()->getVar("_PREV_USER_PROFILE"))
			{
				return ;
			}
		}

		$data = $this->readInputRecord();		
		$username = $data['username']; 
		
		if(!$username)
		{
			return ;
		}		
		
		$serviceObj = BizSystem::getService(PROFILE_SERVICE);

        if (method_exists($serviceObj,'SwitchUserProfile')){
            $serviceObj->SwitchUserProfile($username);
        }        
		BizSystem::clientProxy()->runClientScript("<script>window.location.reload();</script>");        
	}
	
    public function outputAttrs()
    {
    	$output = parent::outputAttrs(); 	
    	$output['show_widget'] = $this->m_ShowWidget;
    	return $output;	    
    } 	
}
?>