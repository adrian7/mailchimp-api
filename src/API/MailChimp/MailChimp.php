<?php
/**
 * SMS Signin Gateway - Mailchimp API integration class
 * @author adrian7 (adrian@studentmoneysaver.co.uk)
 * @version 1.1
 */

namespace DevLib\API\MailChimp;

use MailChimp\MailChimpAPI;
use MailChimp\MailChimpAPIException;

class MailChimp{

    const LIST_MEMBERS_RESOURCE_URI         = '/lists/<list_id>/members/';
    const LIST_SINGLE_MEMBER_RESOURCE_URI   = '/lists/<list_id>/members/<id>';

    const STATUS_SUBSCRIBED     = 'subscribed';
    const STATUS_UNSUBSCRIBED   = 'unsubscribed';

    /**
     * MailChimp api wrapper
     * @var null|MailChimpAPI
     */
    protected $wrapper = NULL;

    /**
     * Default MailChimp list id
     * @var null|string
     */
    protected $default_list_id  = NULL;

    /**
     * MailChimp constructor.
     *
     * @param null $api_key
     * @param null $default_list_id
     */
    public function __construct($api_key, $default_list_id=NULL){

        //TODO improve implementation
        $this->default_list_id = $default_list_id ?: NULL;

        $this->wrapper = new MailChimpAPI($api_key);

    }

    /**
     * Retrieve list member identified by email
     * @param null|string $list_id
     * @param string $email
     *
     * @return mixed
     */
    public function member($email, $list_id=NULL){

        $list_id = $list_id ?: $this->default_list_id;

        $uri = self::getResourceURI(self::LIST_SINGLE_MEMBER_RESOURCE_URI, [
            'list_id' => $list_id,
            'id'      => md5( $email )
        ]);


        return $this->wrapper->get( $uri );

    }

    /**
     * Subscribes/updates member identified by email to list
     *
     * @param $email
     * @param array $merge
     * @param null $list_id
     * @param bool $double_optin
     *
     * @return bool|mixed
     * @throws MailChimpAPIException
     */
    public function subscribe($email, $merge=[], $list_id=NULL, $double_optin=FALSE){

        $list_id = $list_id ?: $this->default_list_id;
        $merge   = empty($merge) ? new \stdClass() : $this->sanitizeMergeFields($merge);

        //update existing member
        try{

            $uri = $this->getResourceURI(self::LIST_SINGLE_MEMBER_RESOURCE_URI, [
                'list_id' => $list_id,
                'id'      => md5( $email )
            ]);

            $member = $this->member($email, $list_id);

            if( $member )
                //member already subscribed, do update
                return $this->wrapper->put($uri, [
                    'status'        => self::STATUS_SUBSCRIBED,
                    'email_address' => $email,
                    'double_optin'  => $double_optin,
                    'merge_fields'  => $merge
                ]);

        }catch(MailChimpAPIException $e){

            if( 404 != $e->getStatusCode() )
                throw $e; //some other error

        }

        //submit a new member
        try{

            $uri = $this->getResourceURI(self::LIST_MEMBERS_RESOURCE_URI, [
                'list_id' => $list_id,
            ]);

            return $this->wrapper->post($uri, [
                'status'        => self::STATUS_SUBSCRIBED,
                'email_address' => $email,
                'double_optin'  => $double_optin,
                'merge_fields'  => $merge
            ]);

        }
        catch (MailChimpAPIException $e){
            throw $e; //could not subscribe member
        }

    }

    public function unsubscribe($email, $list_id=NULL){

        $list_id = empty($list_id) ? $this->default_list_id : NULL;

        try{

            $uri = $this->getResourceURI(self::LIST_SINGLE_MEMBER_RESOURCE_URI, [
                'list_id' => $list_id,
                'id'      => md5( $email )
            ]);

            //try update
            return $this->wrapper->put($uri, [
                'status'        => self::STATUS_UNSUBSCRIBED,
                'email_address' => $email
            ]);


        }
        catch(MailChimpAPIException $e){

            if( 404 != $e->getStatusCode() )
                throw $e;

        }

    }

    /**
     * Updates member
     * @param $email
     * @param array $merge
     * @param null $list_id
     *
     * @return bool|mixed
     */
    public function update($email, $merge=[], $list_id=NULL){

        $list_id = empty($list_id) ? $this->default_list_id : NULL;
        $merge   = empty($merge) ? new \stdClass() : $this->sanitizeMergeFields($merge);

        $uri = $this->getResourceURI(self::LIST_SINGLE_MEMBER_RESOURCE_URI, [
            'list_id' => $list_id,
            'id'      => md5( $email )
        ]);

        //try update
        return $this->wrapper->put($uri, [
            'email_address' => $email,
            'merge_fields'  => $merge
        ]);

    }

    /**
     * Delete list member
     * @param $email
     * @param null $list_id
     *
     * @return mixed
     * @throws MailChimpAPIException
     */
    public function delete($email, $list_id=NULL){

        $list_id = empty($list_id) ? $this->default_list_id : $list_id;

        try{

            $member = $this->member($email, $list_id);

            if( $member ) { //remove

                $uri = $this->getResourceURI(self::LIST_SINGLE_MEMBER_RESOURCE_URI, [
                    'list_id' => $list_id,
                    'id'      => md5( $email )
                ]);

                return $this->wrapper->delete($uri);

            }

        }
        catch(MailChimpAPIException $e){

            if( 404 != $e->getStatusCode() )
                throw $e;

        }

    }

    /**
     * Removes empty values from merge fields
     * @param array $fields
     * @return array
     */
    protected function sanitizeMergeFields($fields){

        return array_filter($fields, function ($value){
            return ! empty($value);
        });

    }

    /**
     * Given a template generates a resource uri
     * @param $template
     * @param array $params
     *
     * @return mixed
     */
    protected function getResourceURI($template, $params=[]){

        if( count($params) )
            foreach($params as $key=>$value)
                $template = str_replace("<{$key}>", $value, $template);

        return $template;
    }
}