<?php

class FUE_Link_Replacement {
    private $email_order_id;
    private $email_id;
    private $user_id;
    private $user_email;
    private $target_page;

    public function __construct( $email_order_id, $email_id, $user_id = 0, $user_email ) {
        $this->email_order_id   = $email_order_id;
        $this->email_id         = $email_id;
        $this->user_id          = $user_id;
        $this->user_email       = $user_email;
    }

    public function replace( $matches ) {

        if ( empty($matches) ) return '';

        $url = $matches[1];

        return FUE::create_email_url( $this->email_order_id, $this->email_id, $this->user_id, $this->user_email, $url );
    }

}
global $fue_key;
$fue_key = str_rot13(strrev(base64_decode(FUE_KEY.'=')));
