<?php 
namespace fky;
require __DIR__.'/fstring.class.php';
require_once(__DIR__.'/../inc/OAuth/autoload.php');
use OAuth2\Server;
use OAuth2\Storage\Pdo;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\ResponseType\AccessToken;
/**
* $database 数组，有四个元素，服务器host，数据库名dbname，用户名username，密码password
*/
class Oauth2 { 
    protected $storage;
    protected $response;
    protected $server;
    public function __construct(array $database)
    {
        $conn = mysql_connect($database['host'],$database['username'],$database['password']);
        mysql_query('CREATE DATABASE  IF NOT EXISTS `'.$database['dbname'].'` Character Set UTF8',$conn);
        mysql_close($conn);
        $pdo = new \PDO('mysql:host='.$database['host'].';dbname='.$database['dbname'], $database['username'], $database['password']);
        $this->storage = new Pdo($pdo);
        $sql = $this->storage->getBuildSql($database['dbname']);
        $pdo->query($sql);
    }
    public function regClient($client_id, $client_secret = null, $redirect_uri = null, $grant_types = 'authorization_code client_credentials refresh_token password', $scope = null, $user_id = null){
       return $this->storage->setClientDetails($client_id, $client_secret, $redirect_uri, $grant_types, $scope, $user_id);
    }
    public function code($client_id = '', $redirect_uri = '', $auth = true , $send = true){
            if (!$this->server instanceof Server) {
                $this->server = new Server($this->storage);
             }
            $this->server->addGrantType(new AuthorizationCode($this->storage)); 
            $request = Request::createFromGlobals();
            if (!$this->response instanceof Response) {
                $this->response = new Response();
             }
            if (!empty($client_id)) {
                $request->query=array('response_type' => 'code', 'client_id' => $client_id);
                if (!empty($redirect_uri)) {
                    $client = $this->storage->getClientDetails($client_id);
                    if($client){
                        if(empty($client['redirect_uri'])){
                            $request->query['redirect_uri'] = $redirect_uri;
                        } else {
                            $this->storage->setClientDetails($client_id, $client['client_secret'], $redirect_uri, $client['grant_types'], $client['scope'], $client['user_id']);
                        }                             
                    }
                }
                $request->request['authorized'] = 'yes';
                $request->server['REQUEST_METHOD'] = 'POST';
            }
            $request->query['state'] = 'fky';
            if ($auth) {
                $authorized = true;
            } else {
                if($request->request['authorized'] == 'yes'){
                    $authorized = true;
                } else {
                    $authorized = false;
                }
            }
            $this->server->handleAuthorizeRequest($request, $this->response, $authorized);
            if ($send) {
                $this->response->send();
            } else {
                $code = substr($this->response->getHttpHeader('Location'), strpos($this->response->getHttpHeader('Location'), 'code=')+5, 40);
                return $code;
            }
    }
    public function token($client_id = '', $need_code = true, $lifetime = 7200, $send = true){
        if (!$this->server instanceof Server) {
            $this->server = new Server($this->storage);
         }
        $this->server->addGrantType(new ClientCredentials($this->storage));
        $this->server->setConfig('access_lifetime', $lifetime);
        $request = Request::createFromGlobals();
        if (!$this->response instanceof Response) {
            $this->response = new Response();
         }
        if ($need_code) {
            if (isset($_GET['code']) && $_GET['code'] != '') {
                $authorizetion = $this->storage->getAuthorizationCode($_GET['code']);
                if($authorizetion['expires'] < time()){
                    die('the code is invalid');
                }
            } else {
                die('code is missing');
            }
            $this->storage->expireAuthorizationCode($_GET['code']);            
        }
        if (!empty($client_id)) {
            $client = $this->storage->getClientDetails($client_id);
            if($client){
                $request->request = array('grant_type' => 'client_credentials','client_id' => $client_id, 'client_secret' => $client['client_secret']);
                $request->server['REQUEST_METHOD'] = 'POST';                
            }
        }
        $this->server->handleTokenRequest($request, $this->response);
        $Pparams=$this->response->getParameters();
        if (!empty($Pparams['access_token'])) {
            $Fstring = new Fstring;
            $refresh_token = $Fstring->randString(40,'','23456789234567892345678923456789');
            $this->storage->setRefreshToken($refresh_token, $request->request['client_id'], null, time()+7200,  null);
            $this->response->setParameter('refresh_token', $refresh_token);
        }
        // $this->storage->getRefreshToken($refresh_token);
        // $this->response->addParameters(array('refresh_token' => $refresh_token));
        if ($send) {
            $this->response->send();
        } else {
            return $Pparams;
        }

    }
    public function refresh_token($client_id = '', $send = true){
        if (!$this->server instanceof Server) {
            $this->server = new Server($this->storage);
         }
        $this->server->addGrantType(new ClientCredentials($this->storage));
        $request = Request::createFromGlobals();
        if (!$this->response instanceof Response) {
            $this->response = new Response();
         }
        if (!empty($client_id)) {
            $client = $this->storage->getClientDetails($client_id);
            if($client){
                $Fstring = new Fstring;
                $refresh_token = $Fstring->randString(40,'','23456789234567892345678923456789');
                $request->request=array('grant_type' => 'refresh_token','client_id' => $client_id,'client_secret' => $client['client_secret'], 'refresh_token' => $refresh_token);
                $request->server['REQUEST_METHOD'] = 'POST';                
            }
        }
        $this->server->handleTokenRequest($request, $this->response);
        $Pparams = $this->response->getParameters();
        if (!empty($Pparams['access_token'])) {
            $this->storage->setAccessToken($Pparams['access_token'], $request->request['client_id'], null, time()+1296000, null);
            $this->response->setParameter('expires_in',1296000);
        }
        if ($send) {
            $this->response->send();
        } else {
            return $Pparams;
        }

    }
    public function resource($access_token = '', $send = true){
        if (!$this->server instanceof Server) {
            $this->server = new Server($this->storage);
         }
        $this->server->addGrantType(new ClientCredentials($this->storage));
        $request = Request::createFromGlobals();
        if (!$this->response instanceof Response) {
            $this->response = new Response();
         }
        if(!empty($access_token)){
            $request->query = array('access_token' => $access_token);
            $_POST['access_token'] = $access_token;
        }
        if (!isset($_POST['access_token']) || $_POST['access_token'] == '') {
            die('The request method must be POST when requesting an resource');
        } else {
            $request->query = array('access_token' => $_POST['access_token']);
        }
        //验证access_token，返回资源
        if($this->server->verifyResourceRequest($request, $this->response)){
           return true;
        } else {
            if ($send) {
                $this->server->getResponse()->send();
                die;
            } else {
                return $this->response->getParameters();
            }
            // die('the access_token is missing or invalid');
        }
    }
    public function send($format = 'json'){
       return $this->response->send($format);
    }
    public function server(array $GrantType = array(), $access_lifetime = 7200, $refresh_token_lifetime = 7200){
        if (!$this->server instanceof Server) {
            $this->server = new Server($this->storage);
        }
        if (empty($GrantType)) {
            $this->server->addGrantType(new AuthorizationCode($this->storage)); 
            $this->server->addGrantType(new ClientCredentials($this->storage));
            $this->server->addGrantType(new RefreshToken($this->storage));
            // $this->server->addGrantType(new RefreshToken($this->storage, array('unset_refresh_token_after_use' => false,'always_issue_new_refresh_token',true)));
            $this->server->addGrantType(new UserCredentials($this->storage));
            $this->server->addGrantType(new GrantTypeInterface($this->storage));
            $this->$server->addGrantType(new JwtBearer($this->storage));
        } else {
            foreach ($GrantType as $Grk => $Grv) {
                $this->server->addGrantType(new $Grv($this->storage)); 
            }
        }
        $this->server->setConfig('refresh_token_lifetime', $refresh_token_lifetime);
        $this->server->setConfig('access_lifetime', $access_lifetime);
        return $this->server;
    }
    public function request(){
        $request = Request::createFromGlobals();
        return $request;
    }
    public function response(){
         if (!$this->response instanceof Response) {
            $this->response = new Response();
         }
        return $this->response;
    }
}

//相关方法
// $storage->setUser('testname','123456');
// $storage->getUser('testname');
// $storage->getUserDetails('testname');
// $storage->getUserClaims('testname','address');
// $storage->checkUserCredentials('testname','123456');

// $storage->checkClientCredentials('testclient','test123456');
// $storage->isPublicClient('testclient');
// $storage->setClientDetails('testclient','test123456','http://myoauth/gettoken.php','authorization_code client_credentials refresh_token password');
// $storage->getClientDetails('testclient2');

// $storage->checkRestrictedGrantType('testclient','authorization_code');

// $storage->setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null);
// $storage->getAccessToken($access_token);
// $storage->unsetAccessToken($access_token);

// $storage->setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null);
// $storage->getAuthorizationCode($code);
// $storage->setAuthorizationCodeWithIdToken($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null);
// $storage->expireAuthorizationCode($code);

// $storage->setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null);
// $storage->getRefreshToken($refresh_token);
// $storage->unsetRefreshToken($refresh_token);

// $storage->scopeExists($scope);
// $storage->getDefaultScope($client_id = null);
// $storage->getClientKey($client_id, $subject);
// $storage->getClientScope($client_id);
// $storage->setJti($client_id, $subject, $audience, $expires, $jti);
// $storage->getJti($client_id, $subject, $audience, $expires, $jti);
// $storage->getPublicKey($client_id = null);

// $storage->getEncryptionAlgorithm($client_id = null);
 ?>