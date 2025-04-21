<?php
/**
 * Unit tests for Currency Module
 */
class Test_Ignis_Currency extends WP_UnitTestCase {
    private $currency;

    public function setUp(): void {
        parent::setUp();
        require_once IGNIS_PLUGIN_DIR . 'modules/currency/currency.php';
        require_once IGNIS_PLUGIN_DIR . 'core/utilities.php';
        $this->currency = new Ignis_Currency();
    }

    public function test_currency_initialization() {
        $this->assertInstanceOf('Ignis_Currency', $this->currency);
    }

    public function test_award_currency() {
        $user_id = $this->factory()->user->create();
        Ignis_Utilities::update_user_currency($user_id, 0);

        // Assume Ignis_Currency::award_currency exists
        $this->currency->award_currency($user_id, 50, 'manual_grant');
        $currency = Ignis_Utilities::get_user_currency($user_id);

        $this->assertEquals(50, $currency, 'Currency was not awarded correctly.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND type = 'currency' AND action = %s",
            $user_id,
            'manual_grant'
        ));

        $this->assertNotNull($log, 'Currency log entry was not created.');
        $this->assertEquals(50, $log->amount, 'Log amount is incorrect.');
    }

    public function test_points_to_currency_conversion() {
        $user_id = $this->factory()->user->create();
        Ignis_Utilities::update_user_points($user_id, 1000);
        Ignis_Utilities::update_user_currency($user_id, 0);

        $options = get_option('ignis_points_settings', ['points_to_gold_ratio' => 100]);
        $points_to_convert = 500;

        // Assume Ignis_Currency::convert_points exists
        $this->currency->convert_points($user_id, $points_to_convert);

        $expected_currency = $points_to_convert / $options['points_to_gold_ratio'];
        $currency = Ignis_Utilities::get_user_currency($user_id);
        $points = Ignis_Utilities::get_user_points($user_id);

        $this->assertEquals(5, $currency, 'Currency conversion incorrect.');
        $this->assertEquals(500, $points, 'Points deduction incorrect.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND type = 'currency' AND action = %s",
            $user_id,
            'points_conversion'
        ));

        $this->assertNotNull($log, 'Conversion log not created.');
        $this->assertEquals(5, $log->amount, 'Conversion log amount incorrect.');
    }

    public function test_currency_deduction() {
        $user_id = $this->factory()->user->create();
        Ignis_Utilities::update_user_currency($user_id, 100);

        // Assume Ignis_Currency::deduct_currency exists
        $result = $this->currency->deduct_currency($user_id, 30, 'store_purchase');

        $this->assertTrue($result, 'Currency deduction failed.');
        $currency = Ignis_Utilities::get_user_currency($user_id);
        $this->assertEquals(70, $currency, 'Currency not deducted correctly.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND type = 'currency' AND action = %s",
            $user_id,
            'store_purchase'
        ));

        $this->assertNotNull($log, 'Deduction log not created.');
        $this->assertEquals(-30, $log->amount, 'Deduction log amount incorrect.');
    }

    public function test_insufficient_currency() {
        $user_id = $this->factory()->user->create();
        Ignis_Utilities::update_user_currency($user_id, 10);

        // Attempt to deduct more than available
        $result = $this->currency->deduct_currency($user_id, 20, 'store_purchase');

        $this->assertFalse($result, 'Deduction should fail for insufficient currency.');
        $currency = Ignis_Utilities::get_user_currency($user_id);
        $this->assertEquals(10, $currency, 'Currency should not change.');
    }
}
?>
