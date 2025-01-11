<?php

use PHPUnit\Framework\TestCase;

class RefundsTest extends TestCase
{
    protected $instaobj;

    public function setUp()
    {
        $this->instaobj = Instamojo\Instamojo::init('app',[
            "client_id" => $_ENV["CLIENT_ID"],
            "client_secret" => $_ENV["CLIENT_SECRET"],
            "scope" => "refunds:read"
        ],true);
    }

    public function test_list_of_refunds()
    {
        $refunds = $this->instaobj->getRefunds();

        $this->assertTrue(is_array($refunds));
    }

    public function test_list_of_refunds_with_limit_paramter()
    {
        $refunds = $this->instaobj->getRefunds(10);

        $this->assertTrue(is_array($refunds));
        $this->assertTrue( (sizeOf($refunds) <= 10 ) );
    }

    public function test_get_refund_details()
    {
        $refunds = $this->instaobj->getRefunds();

        if(sizeof($refunds) > 0) {

            $this->assertArrayHasKey('id',$refunds[0]);
            $refund_detail = $this->instaobj->getRefundDetails($refunds[0]['id']);
            
            $this->assertArrayHasKey('id',$refund_detail);
            $this->assertArrayHasKey('payment',$refund_detail);
            $this->assertArrayHasKey('amount',$refund_detail);
            $this->assertArrayHasKey('buyer',$refund_detail);
        }
    }

    public function test_create_refund_for_payment_throw_exception_on_invalid_parameters()
    {
        $this->expectException(\Instamojo\Exceptions\ApiException::class);
        $this->instaobj->createRefundForPayment(NULL,[]);
    }

}