<?php

use PHPUnit\Framework\TestCase;

class GatewayOrderTest extends TestCase
{
    protected $instaobj;

    public function setUp()
    {
        $this->instaobj = Instamojo\Instamojo::init('app',[
            "client_id" => $_ENV["CLIENT_ID"],
            "client_secret" => $_ENV["CLIENT_SECRET"]
        ],true);
    }

    public function test_create_a_gateway_order()
    {
        $transaction_id = "TEST_".time();
        
        $gateway_order = $this->instaobj->createGatewayOrder([
            "name" => "XYZ",
            "email" => "xyz@squareboat.com",
            "phone" => "9999999988",
            "amount" => "200",
            "transaction_id" => $transaction_id,
            "currency" => "INR"
        ]);

        $this->assertArrayHasKey('order',$gateway_order);

        $this->assertArrayHasKey('payment_options',$gateway_order);

        $this->assertEquals($transaction_id,$gateway_order['order']['transaction_id']);

    }

    public function test_create_a_gateway_order_for_payment_request()
    {
        
            $gateway_order = $this->instaobj->createGatewayOrderForPaymentRequest(
                $_ENV['PAYMENT_REQUEST_ID'],
                [
                    "name" => "XYZ",
                    "email" => "xyz@foo.com",
                    "phone" => "9999999988",
                ]
            );
            $this->assertArrayHasKey('order_id',$gateway_order);

    }


    public function test_throw_exception_on_invalid_parameter_on_create_a_gateway_order()
    {
        $this->expectException(\Instamojo\Exceptions\ApiException::class);
        $transaction_id = "TEST_".time();
        
        $gateway_order = $this->instaobj->createGatewayOrder([
            
            "amount" => "200",
            "transaction_id" => $transaction_id,
            "currency" => "INR"
        ]);

    }


    public function test_get_gateway_orders()
    {
        $gateway_orders = $this->instaobj->getGatewayOrders();

        $this->assertTrue(is_array($gateway_orders));

    }

    public function test_get_gateway_orders_with_limit_parameter()
    {
        $gateway_orders = $this->instaobj->getGatewayOrders(10,1);

        $this->assertTrue(is_array($gateway_orders));
        $this->assertTrue( (sizeOf($gateway_orders) <= 10 ) );

    }

    public function test_get_gateway_order_detail()
    {
        $gateway_orders = $this->instaobj->getGatewayOrders();

        $this->assertTrue(is_array($gateway_orders));

        if(sizeof($gateway_orders) > 0) {

            $this->assertArrayHasKey('id',$gateway_orders[0]);

            $gateway_order_detail = $this->instaobj->getGatewayOrder($gateway_orders[0]['id']);

            $this->assertArrayHasKey('id',$gateway_order_detail);
            $this->assertArrayHasKey('transaction_id',$gateway_order_detail);
            $this->assertArrayHasKey('amount',$gateway_order_detail);
            $this->assertArrayHasKey('currency',$gateway_order_detail);

        }
    }

    
}
