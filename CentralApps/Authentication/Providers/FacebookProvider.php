<?php
namespace CentralApps\Authentication\Providers;

class TwitterProvider implements OAuthProviderInterface
{
    protected $request;
    protected $userFactory;
    protected $userGateway;
    protected $get = array();
    protected $session = array();
    
    protected $externalId;
    protected $externalUsername = null;
    protected $externalEmail = null;
    
    protected $appId;
    protected $appSecret;
    
    protected $callbackPage;
    protected $justSet = false;
    
    protected $facebookClient = null;
    
    
    public function __construct(array $request, \CentralApps\Authentication\UserFactoryInterface $user_factory, \CentralApps\Authentication\UserGateway $user_gateway)
    {
        $this->request = $request;
        $this->userFactory = $user_factory;
        $this->userGateway = $user_gateway;
        
        if(is_array($request) && array_key_exists('get', $request) && is_array($request['get'])) {
            $this->get = $request['get'];
        }
        if(is_array($request) && array_key_exists('session', $request) && is_array($request['session'])) {
            $this->session = $request['session'];
        }
        
        $this->lookupPersistantOAuthTokenDetails();
    }
    
    public function setAppId($app_id)
    {
        $this->appId = $app_id;
    }
    
    public function getAppId()
    {
        return $this->appId;
    }
    
    public function setAppSecret($app_secret)
    {
        $this->appSecret = $app_secret;
    }
    
    public function getAppSecret()
    {
        return $this->appSecret;
    }
    
    public function getFacebookClient()
    {
        
    }
    
    
    
    public function hasAttemptedToLoginWithProvider()
    {
        return ($this->getPersistantData() == 'login');
    }
    
    public function isAttemptingToAttach()
    {
        return ($this->getPersistantData() == 'attach');
    }
    
    public function isAttemptingToRegister()
    {
        return ($this->getPersistantData() == 'register');
    }
    
    
    
    // TODO: use this to remove the repetition from below
    public function verifyTokens()
    {
        
    }
    
    public function getExternalUsername()
    {
        return $this->externalUsername;
    }
    
    public function handleAttach()
    {
        return false;
    }
    
    public function handleRegister()
    {
        return false;
    }
    
    public function processLoginAttempt()
    {       
        return null;
    }
    
    protected function handleRequest()
    {
        
    }
    
    public function logout()
    {
        return true;
    }
    
    public function userWantsToBeRemembered()
    {
        return false;
    }
    
    public function shouldPersist()
    {
        return true;
    }
    public function getProviderName()
    {
        return 'facebook';   
    }
    
    public function getTokens()
    {
        return array('oauth_token' => $this->getFacebookClient()->getAccessToken());
    }
    
    public function getExternalId()
    {
        return $this->externalId;
    }
    
    public function getLoginUrl()
    {
        return $this->buildRedirectUrlAndSaveState('login');  
    }
    
    public function getRegisterUrl()
    {
        return $this->buildRedirectUrlAndSaveState('register');   
    }
    
    public function getAttachUrl()
    {
        return $this->buildRedirectUrlAndSaveState('attach'); 
    }
    
    protected function buildRedirectUrlAndSaveState($state)
    {
        $this->setPersistantData($state);
        return $this->callbackPage;
    }
    
    public function setPersistantData($state)
    {
        $_SESSION['fb_callback_action'] = $state;
        $this->justSet = true;
    }
    
    public function getPersistantData()
    {
        return (isset($_SESSION['fb_callback_action'])) ? $_SESSION['fb_callback_action'] : null;
    }
    
    public function setCallbackPage($callback_page)
    {
        $this->callbackPage = $callback_page;
    }
    
    public function __destruct()
    {
        if(!$this->justSet) {
            unset($_SESSION['fb_callback_action']);
        }
    }

}
