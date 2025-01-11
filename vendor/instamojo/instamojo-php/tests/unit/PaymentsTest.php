<?php

use PHPUnit\Framework\TestCase;

class PaymentsTest extends TestCase
{
    protected $instaobj;

    public function setUp()
    {   
        $this->instaobj = Instamojo\Instamojo::init('app',[
            "client_id" => $_ENV["CLIENT_ID"],
            "client_secret" => $_ENV["CLIENT_SECRET"]
        ],true);

    }

   
    public function test_list_of_payments()
    {
        $payments = $this->instaobj->getPayments();

        $this->assertTrue(is_array($payments));
    }

    public function test_list_of_payments_limit()
    {
        $payments = $this->instaobj->getPayments(10);

        $this->assertTrue(is_array($payments));
        $this->assertTrue( (sizeOf($payments) <= 10 ) );
    }

   
    public function test_single_payment_detail()
    {
        $payments = $this->instaobj->getPayments();

        if(sizeof($payments) > 0) {

            $this->assertArrayHasKey('id',$payments[0]);

            $payment_detail = $this->instaobj->getPaymentDetails($payments[0]['id']);

            $this->assertArrayHasKey('id',$payment_detail);
            $this->assertArrayHasKey('title',$payment_detail);
            $this->assertArrayHasKey('amount',$payment_detail);

        }
    }

    public function test_create_a_payment_request()
    {
        $payment_request = $this->instaobj->createPaymentRequest([
            'amount'=>10,
            'purpose'=>"Test script"
            ]);

        $this->assertArrayHasKey('id',$payment_request);
        $this->assertArrayHasKey('user',$payment_request);
        $this->assertArrayHasKey('longurl',$payment_request);

    }

    public function test_throw_exception_on_invalid_parameters_on_create_payment_request()
    {
        $this->expectException(\Instamojo\Exceptions\ApiException::class);

        $payment_request = $this->instaobj->createPaymentRequest([
            
            'purpose'=>"Test script"
            ]);
    }

    public function test_get_payment_requests()
    {
        $payment_requests = $this->instaobj->getPaymentRequests();

        $this->assertTrue(is_array($payment_requests));
    } 
    
    public function test_get_payment_requests_with_limit_paramter()
    {
        $payment_requests = $this->instaobj->getPaymentRequests(10,1);

        $this->assertTrue(is_array($payment_requests));
        $this->assertTrue( (sizeOf($payment_requests) <= 10 ) );
    }
    
    public function test_get_payment_request_details()
    {
        $payment_requests = $this->instaobj->getPaymentRequests();

        if(sizeof($payment_requests) > 0) {

            $this->assertArrayHasKey('id',$payment_requests[0]);
            $payment_request_detail = $this->instaobj->getPaymentRequestDetails($payment_requests[0]['id']);
            
            $this->assertArrayHasKey('id',$payment_request_detail);
            $this->assertArrayHasKey('user',$payment_request_detail);
            $this->assertArrayHasKey('amount',$payment_request_detail);
            $this->assertArrayHasKey('longurl',$payment_request_detail);
        }
    }
    

}