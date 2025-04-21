<?php
/**
 * Integration tests for Madara theme compatibility
 */
class Test_Ignis_Madara extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Mock WP Manga class for testing
        if (!class_exists('WP_MANGA')) {
            eval("class WP_MANGA {}");
        }
        require_once IGNIS_PLUGIN_DIR . 'core/hooks.php';
        require_once IGNIS_PLUGIN_DIR . 'modules/points/points.php';
    }

    public function test_madara_chapter_read_hook() {
        $user_id = $this->factory()->user->create();
        wp_set_current_user($user_id);
        Ignis_Utilities::update_user_points($user_id, 0);

        // Simulate chapter read
        do_action('wp_manga_after_chapter_read', ['chapter_id' => 1], 123);

        $points = Ignis_Utilities::get_user_points($user_id);
        $this->assertEquals(2, $points, 'Points not awarded for chapter read.');

        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND action = %s",
            $user_id,
            'chapter_read'
        ));

        $this->assertNotNull($log, 'Chapter read log not created.');
        $this->assertEquals(2, $log->amount, 'Chapter read log amount incorrect.');
    }

    public function test_madara_dependency_check() {
        // Temporarily remove WP_MANGA
        $wp_manga = new ReflectionClass('WP_MANGA');
        unset($wp_manga);

        // Reload plugin to trigger dependency check
        ob_start();
        require_once IGNIS_PLUGIN_DIR . 'ignis-plugin.php';
        Ignis_Plugin::get_instance()->init();
        $output = ob_get_clean();

        $this->assertStringContainsString('Ignis Plugin requires WP Manga (Madara-Core) plugin.', $output);
    }
}
?>
