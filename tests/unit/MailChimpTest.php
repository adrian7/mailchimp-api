<?php


class MailChimpTest extends \Codeception\Test\Unit {

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var \DevLib\API\MailChimp\MailChimp
     */
    protected $wrapper;

    /**
     * Test list id
     * @var
     */
    protected $list_id;

    /**
     * Test email address
     * @var string
     */
    protected static $randomEmail;

    /**
     * @var string
     */
    protected static $customEmail;

    /**
     * Faker generator
     * @var Faker\Generator
     */
    protected static $generator;

    /**
     * Before each test
     */
    protected function _before() {

        if( empty(self::$generator) )
            //init generator
            self::$generator = Faker\Factory::create();

        if( empty(self::$randomEmail) )
            //generate a test email
            self::$randomEmail = self::$generator->email;

        $apiKey            = getenv('MAILCHIMP_API_KEY');
        $this->list_id     = getenv('MAILCHIMP_TEST_LIST_ID');

        //generate fake email for testing

        if( empty($apiKey) )
            $this->fail("Please provide the MAILCHIMP_API_KEY env variable ... . ");

        if( empty($this->list_id) )
            $this->fail("Please provide the MAILCHIMP_LIST_ID env variable ... . ");

        //init wrapper
        $this->wrapper = new DevLib\API\MailChimp\MailChimp( $apiKey, $this->list_id );

    }

    // tests
    public function testA_subscribe() {

        $resp = $this->wrapper->subscribe( self::$randomEmail );

        $this->assertObjectHasAttribute('id', $resp);
        $this->assertEquals(self::$randomEmail, $resp->email_address);
        $this->assertEquals('subscribed', $resp->status);

    }

    public function testB_member() {

        $resp = $this->wrapper->member( self::$randomEmail );

        $this->assertObjectHasAttribute('id', $resp);
        $this->assertEquals('subscribed', $resp->status);

    }

    public function testC_unsubscribe() {

        $resp = $this->wrapper->unsubscribe( self::$randomEmail );

        $this->assertObjectHasAttribute('id', $resp);
        $this->assertEquals('unsubscribed', $resp->status);

    }

    public function testD_update() {

        //(!) Warning this test may fail if your list does not have the default merge fields FNAME an LNAME

        $fname = self::$generator->firstName();
        $lname = self::$generator->lastName;

        $resp = $this->wrapper->update( self::$randomEmail, [
            'FNAME' => $fname,
            'LNAME' => $lname
        ]);

        $this->assertObjectHasAttribute('id', $resp);
        $this->assertObjectHasAttribute('merge_fields', $resp);

        $updated = $resp->merge_fields;

        $this->assertEquals($lname, $updated->LNAME);

    }

    public function testE_delete() {

        $resp = $this->wrapper->delete(self::$randomEmail);

        $this->assertTrue($resp);

    }

}