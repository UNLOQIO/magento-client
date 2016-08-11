<?php
class UnloqApiResult {
    var $error = false;
    var $message = "";
    var $code = null;
    var $data = null;

    public function __construct($message=null, $code = null) {
        if(isset($message)) {
            $this->message = $message;
            $this->error = true;
            if(isset($code)) {
                $this->code = $code;
            }
        }
    }


    function error($code = "SERVER_ERROR", $message = 'An unexpected error occurred. Please try again later.', $data = null) {
        $this->error = true;
        if(is_array($code)) {
            if(isset($code['code'])) {
                $this->code = $code['code'];
            }
            if(isset($code['message'])) {
                $this->message = $code['message'];
            }
            if(isset($code['data'])) {
                $this->data = $code['data'];
            }
            return $this;
        }
        if(is_string($code)) {
            $this->code = $code;
        }
        if(is_string($message)) {
            $this->message = $message;
        }
        $this->data = data;
    }

    function success($data = null, $message = null) {
        if(is_array($data)) {
            if(isset($data['message'])) {
                $this->message = $data['message'];
            }
            if(isset($data['data'])) {
                $this->data = $data['data'];
            }
            return $this;
        }
        $this->error = false;
        if(is_string($message)) {
            $this->message = $message;
        }
        $this->data = $data;
    }
}
?>