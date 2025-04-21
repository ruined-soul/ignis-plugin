<?php
/**
 * Unit tests for Points Module
 */
class Test_Ignis_Points extends WP_UnitTestCase {
    private $points;

    public function setUp(): void {
        parent::setUp();
        require_once IGNIS_PLUGIN_DIR . 'modules/points/points.php';
        require_once IGNIS_PLUGIN_DIR . 'core/utilities.php';
        $this->points = new Ignis_Points();
    }

    public function test_points_initialization() {
        $this->assertInstanceOf('Ignis_Points', $this->points);
    }

    public function test_award_points() {
        $user_id = $this->factory()->user->create();
        Ignis_Utilities::update_user_points($user_id, 0);

        $this->points->award_points($user_id, 10, 'test_action');
        $points = Ignis_Utilities::get_user_points($user_id);

        $this->assertEquals(10, $points, 'Points were not awarded correctly.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND action = %s",
            $user_id,
            'test_action'
        ));

        $this->assertNotNull($log, 'Log entry was not created.');
        $this->assertEquals(10, $log->amount, 'Log amount is incorrect.');
    }

    public function test_daily_limit() {
        $user_id = $this->factory()->user->create();
        $action = 'test_action';
        $limit = 1;

        // First action
        $this->assertTrue(Ignis_Utilities::check_daily_limit($user_id, $action, $limit));
        Ignis_Utilities::log_action($user_id, 'points', 5, $action);

        // Second action should be blocked
        $this->assertFalse(Ignis_Utilities::check_daily_limit($user_id, $action, $limit));
    }

    public function test_toast_transient() {
        $user_id = $this->factory()->user->create();
        wp_set_current_user($user_id);

        $this->points->show_toast(5, 'test action');
        $toast = get_transient('ignis_toast_' . $user_id);

        $this->assertNotFalse($toast, 'Toast transient was not set.');
        $this->assertEquals('+5 points for test action!', $toast['message'], 'Toast message is incorrect.');
    }
}
?>
