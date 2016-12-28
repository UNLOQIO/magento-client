<?php
require_once(Mage::getBaseDir('lib') . '/Unloq/UnloqApiResult.php');

class UnloqApi
{

    const PLUGIN_URL = "https://plugin.unloq.io/login.js";
    const API_URL = 'https://api.unloq.io';
    const API_VERSION = '1';

    const HOOK_LOGIN = "/unloq/uauth/login";
    const HOOK_LOGOUT = "/unloq/uauth/logout";

    private $key;
    private $secret;

    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    /*
     * Returns the logout hook in relation to the board url.
     * */
    public function getHook($type, $includeDomain) {
        $base = Mage::getBaseUrl();
        if($includeDomain) {
            $path = $base;
        } else {
            $tmp = explode("://", $base);
            $tmp = $tmp[1];
            $pathIdx = strpos($tmp, "/", 0);
            $path = substr($tmp, $pathIdx);
            if($path[strlen($path)-1] == "/") {
                $path = substr($path, 0, strlen($path)-1);
            }
        }
        switch($type) {
            case "login":
                return $path . self::HOOK_LOGIN;
            case "logout":
                return $path . self::HOOK_LOGOUT;
            default:
                return $path;
        }
    }

    /*
     * Helper function, returns the full API path along with the given path
     * */
    private function getPath($path, $withVersion = true) {
        if (!$withVersion) {
            return $this::API_URL . $path;
        }
        $full = $this::API_URL . '/v' . $this::API_VERSION . $path;
        return $full;
    }

    /*
     * Performs an API request using cURL
     * */
    protected function request($method = "GET", $path, $data = null, $includeVersion = true) {
        $url = $this->getPath($path, $includeVersion);
        $headers = array(
            "X-Api-Key: " .$this->key,
            "X-Api-Secret: " .$this->secret
        );
        if($method == "POST") {
            array_push($headers, "Content-Type: application/x-www-form-urlencoded");
        }
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'unloq-ipb',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST => 1
        );
        $ch = curl_init();
        if($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt_array($ch, $curl_options);
        $result = curl_exec($ch);
        curl_close($ch);
        if(!$result) {
            return new UnloqApiResult("Failed to contact UNLOQ servers", "SERVER_ERROR");
        }
        $res = json_decode($result, true);
        $result = new UnloqApiResult();
        if(!is_array($res) || !isset($res['type'])) {
            $result->error();
            return $result;
        }
        if($res['type'] !== 'success') {
            if($res['code'] == 'APPLICATION.NOT_FOUND') {
                $res['message'] = "Invalid API Key or API Secret";
            }
            $result->error($res);
            return $result;
        }
        $result->success($res);
        return $result;
    }

    /*
     * Verifies that the given assoc array's signature.
    * 1. Create a string with the URL PATH(PATH ONLY), including QS and the first/
    * 2. Sort the data alphabetically,
    * 3. Append each KEY,VALUE to the string
    * 4. HMAC-SHA256 with the app's api secret
    * 5. Base64-encode the signature.
     * */
    public function verifySignature($path, $data, $signature = null) {
        if($signature == null) {    // We take it from headers.
            $headers = getallheaders();
            if(!isset($headers['X-Unloq-Signature']) || !isset($headers['X-Requested-With'])) {
                return false;
            }
            $signature = $headers['X-Unloq-Signature'];
        }
        if(!is_string($path) || !is_array($data)) return false;
        if(substr($path, 0, 1) !== "/") { $path = '/' . $path; }
        $sorted = array();
        foreach($data as $key => $value) {
            if($key == "uauth") continue;
            array_push($sorted, $key);
        }
        asort($sorted);
        foreach($sorted as $key) {
            $val = (isset($data[$key]) ? $data[$key] : '');
            if(!is_string($val)) $val = "";
            $path = $path. $key . $val;
        }
        $hash = hash_hmac("sha256", $path, $this->secret, true);
        $finalHash = base64_encode($hash);
        if($finalHash !== $signature) return false;
        return true;
    }

    /*
     * Verifies the credentials and updates the login/logout hook.
     * */
    public function updateHooks() {
        $data = array(
            'login' => $this->getHook('login', true),
            'logout' => $this->getHook('logout', true)
        );
        return $this->request("POST", "/settings/webhooks", $data);
    }

    /*
     * Tries and retrieves attached information of an UAuth access token.
     * */
    public function getLoginToken($token) {
        if (!is_string($token) || strlen($token) < 129) {
            return new UnloqApiResult("The UAuth access token is not valid", "ACCESS_TOKEN");
        }
        $data = array("token" => $token);
        $res = $this->request("POST", "/token", $data);
        if(!$res->error) {
            // We verify data integrity.
            if (!isset($res->data['id']) || !isset($res->data['email'])) {
                return new UnloqApiResult("The UAuth response does not contain login information.", "API_ERROR");
            }
        }
        return $res;
    }

    /*
     * Once a user was logged in, it will send the session id to UNLOQ for remote logout.
     * */
    public function sendTokenSession($token, $sid, $duration = null) {
        $data = array(
            'token'  => $token,
            'sid'    => $sid
        );
        if($duration) {
            $data['duration'] = (int) $duration;
        }
        $res = $this->request('POST', '/token/session', $data);
        return $res;
    }

}