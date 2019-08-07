<?php

/*
 * Plugin_ACL
 */

class Plugin_ACL extends Zend_Acl
{
	public function __construct(Zend_Auth $auth)
	{
		$this->addRole(new Zend_Acl_Role('guest'))
			->addRole(new Zend_Acl_Role('member'), 'guest')
    		->addRole(new Zend_Acl_Role('admin'));
    		
    		
    	$this->add(new Zend_Acl_Resource('index'));
    	$this->add(new Zend_Acl_Resource('error'));
    	$this->add(new Zend_Acl_Resource('auth'));
    	$this->add(new Zend_Acl_Resource('portfolio'));
    	$this->add(new Zend_Acl_Resource('stocks'));
    	$this->add(new Zend_Acl_Resource('groups'));
    	$this->add(new Zend_Acl_Resource('user'));
    	$this->add(new Zend_Acl_Resource('watchlist'));
    	$this->add(new Zend_Acl_Resource('cron'));
    	$this->add(new Zend_Acl_Resource('admin'));
    	$this->add(new Zend_Acl_Resource('analysis'));
    	

  		//Je nach Systemstatus gelten andere Rechte
  		//ONLINE
  		if(Zend_Registry::get('systemstatus')  == "ONLINE")
  		{
  			//guest
				$this->allow('guest', 'error');
				$this->allow('guest', 'index');
				$this->deny('guest', 'index', 'index-auth');
				$this->allow('guest', 'auth');
				$this->allow('guest', 'cron', null, new ACL_LocalIP());
				$this->allow('guest', 'user', 'activate');
				$this->allow('guest', 'user', 'register');
				$this->allow('guest', 'user', 'reset-password');
				
				$this->allow('guest', 'groups', 'forum-dispatch');
				
				/*
				$this->allow('guest', 'stocks', 'index');
				$this->allow('guest', 'stocks', 'search');
				$this->allow('guest', 'stocks', 'list');
				*/
				
				
				
  			//member
  				$this->allow('member', 'stocks');
  				
  				$this->allow('member', 'analysis', 'create');
  				$this->allow('member', 'analysis', 'edit', new ACL_AnalysisEdit());
  				$this->allow('member', 'analysis', 'set-favourite', new ACL_AuthUserAccess(ACL_AuthUserAccess::USERID));
  				$this->allow('member', 'analysis', 'show', new ACL_AnalysisShow());
  				$this->allow('member', 'analysis', 'help');
  				$this->allow('member', 'analysis', 'latest-analysis');
  				
  				$this->allow('member', 'user', 'edit', new ACL_AuthUserAccess(ACL_AuthUserAccess::USERNAME));
  				$this->allow('member', 'user', 'edit-picture', new ACL_AuthUserAccess(ACL_AuthUserAccess::USERNAME));
  				$this->allow('member', 'user', 'setup', new ACL_AuthUserAccess(ACL_AuthUserAccess::USERNAME));
  				$this->allow('member', 'user', 'profile');
  				$this->allow('member', 'user', 'invite', new ACL_AuthUserAccess(ACL_AuthUserAccess::USERNAME));
  				
  				$this->allow('member', 'watchlist', 'show');
  				$this->allow('member', 'watchlist', 'show-json');
  				$this->allow('member', 'watchlist', 'index');
  				$this->allow('member', 'watchlist', 'most-watched-stocks');
  				$this->allow('member', 'watchlist', 'create', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'watchlist', 'delete', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::WATCHLISTID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'watchlist', 'edit', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::WATCHLISTID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'watchlist', 'add', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'watchlist', 'remove', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::WATCHLISTID, ACL_AuthUserAccess::USERNAME)));
  								
  				$this->allow('member', 'portfolio', 'index');
  				$this->allow('member', 'portfolio', 'show', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'create', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'edit', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'delete', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));				
  				$this->allow('member', 'portfolio', 'performance-monitor', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'add-transaction', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'edit-transaction', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
  				$this->allow('member', 'portfolio', 'delete-transaction', new ACL_AuthUserAccess(array(ACL_AuthUserAccess::PORTFOLIOID, ACL_AuthUserAccess::USERNAME)));
					
  				$this->allow('member', 'groups', 'show-reply');
  				$this->allow('member', 'groups', 'create-reply');
  				//$this->allow("member", 'groups', 'editreply', new ACL_GroupThreadReplyEdit());
  				
  								
  		}
  		//WARTUNG & OFFLINE
  		else
  		{
  			//guest
  				//keine Rechte
			$this->allow('guest', 'cron', null, new ACL_LocalIP()); //Ausnahme CRON
  			//member
  				//keine Rechte
  		
  		}

		$this->allow('admin'); // Admin darf eh immer alles
	}
}