<?php


namespace gruelt\RenaterMail;
use GuzzleHttp\Client;
use SimpleXMLElement;

class RenaterApi
{
    var $url;
    var $preauth_token;
    var $domain;
    var $token;
    var $key;
    var $timestamp;
    const TOKEN_MAX_AGE=290;

    /**Initializes object from config
     * RenaterMail constructor.
     */
    function __construct()
    {
        $this->url=config('renatermail.RENATER_URL');   // https://api.partage.renater.fr/service/domain/
        $this->key=config('renatermail.RENATER_KEY');   //
        $this->domain=config('renatermail.RENATER_DOMAIN'); //test.emse.fr
        $this->timestamp=0;

        $this->account_id=null;
        $this->account=array();
        $this->status=null;
        $this->message=null;


    }

    /**Makes the preauth corresponding to BSS Documentation chapter 3.3
     * @return string preauth token
     */
    public function makePreauth()
    {
        $this->preauth_token=hash_hmac("sha1",$this->domain.'|'.$this->timestamp,$this->key);

        return $this->preauth_token;
    }



    /** Authenticates with preauth key
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth()
    {
        //of token is too old ( > 5 minutes )
        if(time() - $this->timestamp > self::TOKEN_MAX_AGE) {

            $this->timestamp=time();

            $this->makePreauth();

            $client = new Client();

            $res = $client->get($this->url . "Auth?" . "domain=test.emse.fr&preauth=" . $this->preauth_token . "&timestamp=" . $this->timestamp);

            //dd($res->getBody());
            $response = new SimpleXMLElement($res->getBody()->getContents());

            $this->token = (string)$response->token;

            return $response;
        }else{
            return true;
        }
    }



    /** Gets all mail adresses
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getall()
    {
        $client = new Client();

        $this->auth();

        $res = $client->get( $this->url."GetAllAccounts/".$this->token);



        return $res->getBody()->getContents();
    }


    /** Gets One account  by its mail identifier
     * @param $mail
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($mail)
    {
        $client = new Client();

        $this->auth();

        $res = $client->get( $this->url."GetAccount/".$this->token."?name=".$mail);  //



        $json = json_encode(new SimpleXMLElement($res->getBody()->getContents()));
        $array = json_decode($json,true);


        $this->account= $array['account'];
        $this->message= $array['message'];
        $this->status = $array['status'];


        $this->account_name= (String) $this->account['name'];

        return $this->account;
    }

    /** Create an account ( no check if existing)
     * @param $mail
     * @param $name
     * @param string $password
     * @param int $quota
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create($mail,$name,$password="testmail",$quota=5)
    {
        $client = new Client();

        $this->auth();

        $quotagig = $quota *1024 * 1024 * 1024;

        $res = $client->get( $this->url."/CreateAccount/".$this->token."?name=".$mail."&password=".$password."&givenName=".$name."&zimbraMailQuota=".$quotagig);  //


        return $res->getBody()->getContents();
    }


    /** Deletes the account
     * @param $mail
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($mail)
    {
        $client = new Client();

        $this->auth();

        $res = $client->get( $this->url."/DeleteAccount/".$this->token."?name=".$mail);  //


        return $res->getBody()->getContents();
    }


    /** Modify an account
     * @param String $mail identifier
     * @param array $modifications array of modifications
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function modify(String $mail,Array $modifications)
    {
        $client = new Client();

        $this->auth();

        $flatmodifications=http_build_query($modifications);
        //dd($flatmodifications);

        $res = $client->get( $this->url."/ModifyAccount/".$this->token."?name=".$mail."&".$flatmodifications);  //


        return $res->getBody()->getContents();
    }



    /** Sub usage of above basis Functions */

    /** Locks the account (still receving mails , but user can't connect).
     * @param $mail
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function lock($mail)
    {
        $client = new Client();

        $this->auth();

        $modifications=['zimbraAccountStatus'=>'locked'];

        $out = $this->modify($mail,$modifications);


        return $out;
    }

    /** Unlock the account
     * @param $mail
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unlock($mail)
    {
        $client = new Client();

        $this->auth();

        $modifications=['zimbraAccountStatus'=>'active'];

        $out = $this->modify($mail,$modifications);


        return $out;
    }

}
