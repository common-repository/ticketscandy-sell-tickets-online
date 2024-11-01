<?php
/*
Plugin Name: TicketsCandy - Sell Tickets Online
Plugin URI: https://ticketscandy.com/merchant/sell-tickets/
Description: The easiest and most robust ticketing system for your event! Sell tickets online directly from your WordPress site.
Author: TicketsCandy
Version: 1.0
Author URI: http://www.ticketscandy.com/
*/


register_activation_hook(__FILE__, 'ticketscandy_activate');
add_action('admin_init', 'ticketscandy_redirect');

function ticketscandy_activate() {
    add_option('ticketscandy_do_activation_redirect', true);
}

function ticketscandy_redirect() {
    if (get_option('ticketscandy_do_activation_redirect', false)) {
        delete_option('ticketscandy_do_activation_redirect');
        if(!isset($_GET['activate-multi']))
        {
            wp_redirect("options-general.php?page=ticket-candy-options-admin");
        }
    }
}

add_shortcode('ticketscandy-event', 'ticketscandy_event_load');
function ticketscandy_event_load($atts) {
    $options = get_option( 'tickets_candy_options' );
    $iframe_id = 'iframe_' . rand(10000,99999);

    if (!empty($options['form_url'])) {
        wp_enqueue_script( 'ticketscandy-widget', '//ticketscandy.com/js/tc_widgets.js', array('jquery'), false, true );
        return '<div id="tc-container-iframe-' . $iframe_id . '"></div>
            <script>
                jQuery(document).ready( function() {
                    TCWidget.init(\'tc-container-iframe-' . $iframe_id . '\', \'' . esc_attr($options['form_url']) . '\');
                });
            </script>';
    } else {
        return '<p>Error: No URL set for TicketsCandy</p>';
    }
}


class TicketsCandySettingsPage
{
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page()
    {
        add_options_page(
            'TicketsCandy Options',
            'TicketsCandy',
            'manage_options',
            'ticket-candy-options-admin',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        $this->options = get_option( 'tickets_candy_options' );
        ?>
        <div class="wrap">
            <h2>TicketsCandy Plugin</h2>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'tickets_candy_group' );
                do_settings_sections( 'ticket-candy-options-admin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init()
    {
        register_setting(
            'tickets_candy_group',
            'tickets_candy_options',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id',
            'Settings',
            array( $this, 'print_section_info' ),
            'ticket-candy-options-admin'
        );

        add_settings_field(
            'form_url',
            'TicketsCany Event URL',
            array( $this, 'form_url_callback' ),
            'ticket-candy-options-admin',
            'setting_section_id'
        );

        add_settings_field(
            'shortcode',
            'Your Shortcode',
            array( $this, 'shortcode_callback' ),
            'ticket-candy-options-admin',
            'setting_section_id'
        );
    }

    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['form_url'] ) )
            $new_input['form_url'] = sanitize_text_field( $input['form_url'] );

        return $new_input;
    }

    public function print_section_info()
    {

    }

    public function form_url_callback()
    {
        printf(
            '<input type="text" id="form_url" name="tickets_candy_options[form_url]" value="%s" />',
            isset( $this->options['form_url'] ) ? esc_attr( $this->options['form_url']) : ''
        );
        echo '<p>Create your event at <a href="https://ticketscandy.com/sell-tickets/">TicketsCandy.com</a> and copy the Event URL from your admin dashboard integrations page.</p>';
    }

    public function shortcode_callback()
    {
        echo '<input type="text" id="shortcode" name="tickets_candy_options[shortcode]" value="[ticketscandy-event]" readonly />';
        echo '<p>Copy this shortcode and paste it anywhere on the page to display the ticketing widget.</p>';
    }
}

if( is_admin() )
    $ticketscandy_settings_page = new TicketsCandySettingsPage();