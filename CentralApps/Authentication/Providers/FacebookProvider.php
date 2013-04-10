<?php
namespace CentralApps\Authentication\Providers;

class FacebookProvider implements OAuthProviderInterface
{
    protected $request;
    protected $userFactory;
    protected $userGateway;
    protected $get = array();
    protected $session = array();
    
    protected $externalId;
    protected $externalUsername = null;
    protected $externalEmail = null;
    protected $externalDisplayName = null;
    
    protected $appId;
    protected $appSecret;
    protected $scope;
    
    protected $callbackPage;
    protected $justSet = false;
    
    protected $token = null;
    
    protected $facebookClient = null;
    
    
    public function __construct(array $request, \CentralApps\Authentication\UserFactoryInterface $user_factory, \CentralApps\Authentication\UserGateway $user_gateway)
    {
        $this->request = $request;
        $this->userFactory = $user_factory;
        $this->userGateway = $user_gateway;
        $this->scope = '';
        
        if(is_array($request) && array_key_exists('get', $request) && is_array($request['get'])) {
            $this->get = $request['get'];
        }
        if(is_array($request) && array_key_exists('session', $request) && is_array($request['session'])) {
            $this->session = $request['session'];
        }
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }
    
    public function getToken()
    {
        return $this->token;
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

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }
    
    public function getFacebookClient()
    {
        return new \Facebook(array(
          'appId'  => $this->appId,
          'secret' => $this->appSecret,
        ));
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
        return (is_null($this->handleRequest())) ? false : true;
    }
    
    public function getExternalUsername()
    {
        return $this->externalUsername;
    }
    
    public function getExternalDisplayName()
    {
        return $this->externalDisplayName;
    }
    
    public function handleAttach()
    {
        if(!is_null($this->userGateway->user)) {
            $user_data = $this->handleRequest();
            if(!is_null($user_data)) {
                try {
                 $this->userGateway->attachTokensFromProvider($this);
                 return true;
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }
    
    public function handleRegister()
    {
        if(is_null($this->userGateway->user)) {
            $user_data = $this->handleRequest();
            if(!is_null($user_data)) {
                $this->userGateway->registerUserFromProvider($this);
            }
        }
        return false;
    }
    
    public function processLoginAttempt()
    {
        if(is_null($this->userGateway->user)) {    
            $user_data = $this->handleRequest();
            if(!is_null($user_data)) {
                try {
                     return $this->userFactory->getFromProvider($this);
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
        return false;
    }
    
    public function handleRequest()
    {
        $facebook = $this->getFacebookClient();
        if(!is_null($this->token)) {
            $facebook->setAccessToken($this->token);
        }
        $user = $facebook->getUser();
        if($user) {
            try {
                $data = $facebook->api('/me');
                $this->externalId = $data['id'];
                $this->externalUsername = (array_key_exists('username', $data)) ? $data['username'] : null;
                $this->externalEmail = (array_key_exists('email', $data)) ? $data['email'] : null;
                $this->externalDisplayName = (array_key_exists('name', $data)) ? $data['name'] : null;
                return $data;
            } catch(\Exception $e) {
                return null;
            }
        }
        return null;
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
        return $this->getFacebookClient()->getLoginUrl(array('scope' => $this->scope));
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
