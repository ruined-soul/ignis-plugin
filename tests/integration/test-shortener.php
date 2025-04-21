<?php
/**
 * Integration tests for Shortener Link Module
 */
class Test_Ignis_Shortener extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        require_once IGNIS_PLUGIN_DIR . 'core/api.php';
        require_once IGNIS_PLUGIN_DIR . 'modules/shortener/shortener.php';
    }

    public function test_shortener_api_endpoint() {
        $options = get_option('ignis_general_settings', ['shortener_key' => 'test_key']);
        update_option('ignis_general_settings', array_merge($options, ['shortener_key' => 'test_key']));

        // Mock shortener service response
        add_filter('ignis_shortener_response', function() {
            return 'https://short.url/abc123';
        });

        $request = new WP_REST_Request('POST', '/ignis/v1/shortener');
        $request->set_header('X-Ignis-Shortener-Key', 'test_key');
        $request->set_body(json_encode(['url' => 'https://example.com']));

        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status(), 'Shortener API should return 200.');
        $this->assertTrue($response->get_data()['success'], 'Shortener API should succeed.');
        $this->assertEquals('https://short.url/abc123', $response->get_data()['short_url'], 'Shortener URL incorrect.');
    }

    public function test_shortener_invalid_key() {
        $request = new WP_REST_Request('POST', '/ignis/v1/shortener');
        $request->set_header('X-Ignis-Shortener-Key', 'wrong_key');
        $request->set_body(json_encode(['url' => 'https://example.com']));

        $response = rest_do_request($request);

        $this->assertEquals(401, $response->get_status(), 'Shortener API should return 401 for invalid key.');
        $this->assertFalse($response->get_data()['success'], 'Shortener API should fail with invalid key.');
    }

    public function test_shortener_invalid_url() {
        $options = get_option('ignis_general_settings', ['shortener_key' => 'test_key']);
        update_option('ignis_general_settings', array_merge($options, ['shortener_key' => 'test_key']));

        $request = new WP_REST_Request('POST', '/ignis/v1/shortener');
        $request->set_header('X-Ignis-Shortener-Key', 'test_key');
        $request->set_body(json_encode(['url' => 'invalid_url']));

        $response = rest_do_request($request);

        $this->assertEquals(400, $response->get_status(), 'Shortener API should return 400 for invalid URL.');
        $this->assertFalse($response->get_data()['success'], 'Shortener API should fail with invalid URL.');
    }

    public function test_shortener_module_enabled() {
        $options = get_option('ignis_general_settings', ['enable_shortener' => 1]);
        update_option('ignis_general_settings', array_merge($options, ['enable_shortener' => 1]));

        // Assume Ignis_Shortener::create_short_link exists
        $shortener = new Ignis_Shortener();
        add_filter('ignis_shortener_response', function() {
            return 'https://short.url/test123';
        });

        $result = $shortener->create_short_link('https://example.com');

        $this->assertEquals('https://short.url/test123', $result, 'Shortener module should create short link.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE type = 'shortener' AND action = %s",
            'create_link'
        ));

        $this->assertNotNull($log, 'Shortener log not created.');
    }

    public function test_shortener_module_disabled() {
        $options = get_option('ignis_general_settings', ['enable_shortener' => 0]);
        update_option('ignis_general_settings', array_merge($options, ['enable_shortener' => 0]));

        $shortener = new Ignis_Shortener();
        $result = $shortener->create_short_link('https://example.com');

        $this->assertFalse($result, 'Shortener module should return false when disabled.');
    }
}
?>
