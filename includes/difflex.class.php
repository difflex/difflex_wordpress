<?php

/*  
Copyright Difflex https://difflex.ru
This file is part of the difflex-for-woocommerce plugin created by Difflex.
*/

class Difflex
{
  private static
    $initiated = false,
    $user_id,
    $user_first_name,
    $user_last_name,
    $user_email,
    $user_phone,
    $user_date_of_birth,
    $user_gender
  ;

  public static function init() {
    if (!self::$initiated) {
      self::init_hooks();
      self::$initiated = true;
      load_plugin_textdomain('difflex', false, 'difflex/languages');
    }
  }

  public static function activated_action_handler($plugin) {
    $name = plugin_basename( trim($plugin) );
    if ($name == 'difflex/difflex.php') {
      exit(wp_safe_redirect(admin_url('admin.php?page=difflex')));
    }
  }

  private static function init_hooks() {
    add_action('admin_menu', array('Difflex', 'plgn_add_pages'));

    if (!is_admin()) {
      add_action('wp_head', array('Difflex', 'difflex_main') );
      add_action('woocommerce_before_single_product', array('Difflex', 'product_view') );
    }

    add_action('woocommerce_cart_updated', array('Difflex', 'submit_cart'));
    add_action('woocommerce_checkout_order_processed', array('Difflex', 'submit_order'));
    add_action('woocommerce_order_status_changed', array('Difflex', 'state_order'));
    add_action('wp_trash_post', array('Difflex', 'delete_order'));

    register_uninstall_hook(__FILE__, array('Difflex', 'delete_options'));
  }

  public static function delete_options() {
    delete_option('difflex_plgn_options');
  }

  public static function plgn_add_pages() {
    add_submenu_page(
      'plugins.php',
      __( 'Difflex', 'difflex' ),
      __( 'Difflex', 'difflex' ),
      'manage_options',
      'difflex',
      array('Difflex', 'plgn_settings_page')
    );
    //call register settings function
    add_action('admin_init', array('Difflex', 'plgn_settings'));
  }

  public static function plgn_options_default() {
    return array('app_key' => '');
  }

  public static function plgn_settings() {
    $plgn_options_default = self::plgn_options_default();

    if (!get_option('difflex_plgn_options')) {
      add_option('difflex_plgn_options', $plgn_options_default, '', 'yes');
    }

    $plgn_options = get_option('difflex_plgn_options');
    $plgn_options = array_merge($plgn_options_default, $plgn_options);

    update_option('difflex_plgn_options', $plgn_options);
  }

  public static function plgn_settings_page() {
    $difflex_plgn_options = self::get_params();
    $difflex_plgn_options_default = self::plgn_options_default();
    $message = '';
    $error = '';

    if (isset($_REQUEST['difflex_plgn_form_submit']) && check_admin_referer(plugin_basename(dirname(__DIR__)), 'difflex_plgn_nonce_name')) {
      foreach($difflex_plgn_options_default as $k => $v) {
        $difflex_plgn_options[$k] = trim(self::request($k, $v));
      }

      update_option('difflex_plgn_options', $difflex_plgn_options);

      $message = __('Settings saved', 'difflex');
    }

    $options = array(
      'difflex_plgn_options' => $difflex_plgn_options,
      'message' => $message,
      'error' => $error
    );

    echo self::loadTPL('adminform', $options);
  }

  private static function loadTPL($name, $options) {
    $tmpl = (DIFFLEX_PLUGIN_DIR .'tmpl/' . $name . '.php');

    if (!is_file($tmpl)) return __('Error Load Template', 'difflex');

    extract($options, EXTR_PREFIX_SAME, 'difflex');

    ob_start();

    include $tmpl;

    return ob_get_clean();
  }

  private static function request($name, $default=null) {
    return (isset($_REQUEST[$name])) ? sanitize_text_field($_REQUEST[$name]) : $default;
  }

  public static function difflex_main() {
    $difflex_plgn_options = self::get_params();
    if (!empty($difflex_plgn_options['app_key'])) {
      $difflexSettings = '';
      $current_user = wp_get_current_user();
      $user_id = $current_user->ID;

      if ($user_id > 0) {
        self::update_user_info();

        $info = array('uid' => (string) $user_id);
        if (!empty(self::$user_first_name)) $info['firstName']      = (string) self::$user_first_name;
        if (!empty(self::$user_last_name)) $info['lastName']        = (string) self::$user_last_name;
        if (!empty(self::$user_email)) $info['email']               = (string) self::$user_email;
        if (!empty(self::$user_phone)) $info['phone']               = (string) self::$user_phone;
        if (!empty(self::$user_date_of_birth)) $info['dateOfBirth'] = (string) self::$user_date_of_birth;
        if (!empty(self::$user_gender)) $info['gender']             = (string) self::$user_gender;

        do_action('difflex_visitor', $info);
      }
      ?>
<script type="text/javascript">
(function(w,d,c,h){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var t = d.createElement('script');t.charset = 'utf-8';t.async = true;t.type = 'text/javascript';t.src = h;var s = d.getElementsByTagName('script')[0];s.parentNode.insertBefore(t, s);})(window,document,'difflex','https://cdn.jsdelivr.net/gh/difflex/difflex_js_client@latest/difflex.js');

difflex('appKey', '<?= $difflex_plgn_options['app_key']; ?>');
<?php if ($info): ?>
difflex('setVisitorInfo', <?= json_encode($info) ?>);
<?php endif; ?>
</script>
      <?php
    }
  }

  public static function product_view() {
    $difflex_plgn_options = self::get_params();
    if (!empty($difflex_plgn_options['app_key'])) {
      global $wp_query;
      $uri = get_permalink($wp_query->post);
      $product_id = $wp_query->post->ID;
      $product = wc_get_product( $product_id );
      if ($product->get_type() == 'variable') {
        $available_variations = $product->get_available_variations();
        if (count($available_variations) > 0) {
          $variant = array_shift($available_variations);
          $product_id = $variant['variation_id'];
        }
      }
      ?>
      <script type="text/javascript">
        difflex('track', 'product_view', {
          offer: {
            sku: <?php echo $product_id; ?>,
            name: '<?php echo $wp_query->post->post_title; ?>',
            url: '<?php echo $uri; ?>'
          }
        });
      </script>
      <?php
    }
  }

  /** Submit order state to difflex
   * @param $order_id
   */
  public static function state_order($order_id) {
    $order = self::get_order($order_id);

    if (empty($order)) return;

    if (!($client = self::init_client())) return;

    // Support Woocommerce 2.6.4
    if (property_exists($order, 'order_date') and property_exists($order, 'modified_date')) {
      $date_created = $order->order_date;
      $date_modified = $order->modified_date;
    } else {
      $data = $order->get_data();
      // if order is created in the admin panel
      $format = 'd.m.y H:i:s';
      $date_created = $data['date_created']->date($format);
      $date_modified = $data['date_modified']->date($format);
    }

    if (is_admin_bar_showing() and $date_created == $date_modified) {
      $this->submit_order($order_id);
    } else {
      $client->track('orderUpdate', self::get_order_data($order_id));
    }
  }

  private static function get_order($order_id) {
    return function_exists('wc_get_order') ? wc_get_order( $order_id ) : new WC_Order( $order_id );
  }

  private static function get_order_data($order_id) {
    $order = self::get_order($order_id);
    $offers = array();
    $items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
    $total = $order->get_total();
    $shipping = (method_exists($order, 'get_total_shipping')) ? $order->get_total_shipping() : 0;
    $order_total = $total - $shipping;

    if (is_array($items) && count($items)) {
      foreach ($items as $item_id => $item) {
        $sku = !empty($item['variation_id'])
          ? $item['variation_id'] : $item['product_id'];

        $price = $item['line_subtotal'] / (float) $item['qty'];

        $offer = array(
          'sku' => (string) $sku,
          'qnt' => (float) $item['qty'],
          'price' => (float) $price
        );

        $product = wc_get_product($sku);
        if ($product) {
          $image_url = wp_get_attachment_url($product->get_image_id());
          if ($image_url) $offer['picture'] = (string) $image_url;
          $offer['url'] = (string) $product->get_permalink();
        }

        $offers[] = $offer;
      }
    }

    return array(
      'number' => (string) $order_id,
      'revenue' => (float) $order_total,
      'state' => (string) $order->get_status(),
      'cancelled' => false,
      'paid' => (boolean) $order->is_paid(),
      'offers' => $offers
    );
  }

  public static function delete_order($order_id) {
    $order = self::get_order($order_id);

    if (empty($order)) return;
  
    if (!($client = self::init_client())) return;
    
    $client->track('orderUpdate', array(
      'number' => $order_id,
      'cancelled' => true
    ));
  }

  public static function submit_order($order_id) {
    if (!($client = self::init_client())) return;

    $order = self::get_order($order_id);

    if (is_admin_bar_showing()) {
      self::$user_id = $order->customer_user ? $order->customer_user : false;
    }

    if (function_exists('wc_get_order')) $order = wc_get_order( $order_id );
    else $order = new WC_Order($order_id);

    $visitor = array();
    if (self::$user_id) {
      $visitor['uid'] = self::$user_id;
    }
    $first_name = self::get_value($order->get_billing_first_name(), self::$user_first_name);
    if ($first_name !== false){
      $visitor['firstName'] = $first_name;
    }
    $last_name = self::get_value($order->get_billing_last_name(), self::$user_last_name);
    if ($last_name !== false){
      $visitor['lastName'] = $last_name;
    }
    $email = self::get_value($order->get_billing_email(), self::$user_email);
    if ($email !== false){
      $visitor['email'] = $email;
    }
    $phone = self::get_value($order->get_billing_phone(), self::$user_phone);
    if ($phone !== false){
      $visitor['phone'] = $phone;
    }

    if (!empty(self::$user_date_of_birth)){
      $visitor['dateOfBirth'] = self::$user_date_of_birth;
    }
    if (!empty(self::$user_gender)){
      $visitor['gender'] = self::$user_gender;
    }

    do_action('difflex_visitor', $visitor);
    
    $client->set_visitor($visitor);

    $client->track('orderCreate', self::get_order_data($order_id));
  }

  private static function get_value($value, $default) {
    if (empty($value) && empty($default)) return false;
    return (!empty($value)) ? $value : $default;
  }

  public static function submit_cart() {
    if (function_exists('WC')) $wc = WC();
    else {
      global $woocommerce;
      $wc = $woocommerce;
    }

    if (!($client = self::init_client())) return;

    self::update_user_info();

    $visitor = array();
    if (self::$user_id) {
      $visitor['uid'] = self::$user_id;
    }
    if (!empty(self::$user_first_name)){
      $visitor['firstName'] = self::$user_first_name;
    }
    if (!empty(self::$user_last_name)){
      $visitor['lastName'] = self::$user_last_name;
    }
    if (!empty(self::$user_email)){
      $visitor['email'] = self::$user_email;
    }
    if (!empty(self::$user_phone)){
      $visitor['phone'] = self::$user_phone;
    }
    if (!empty(self::$user_date_of_birth)){
      $visitor['dateOfBirth'] = self::$user_date_of_birth;
    }
    if (!empty(self::$user_gender)){
      $visitor['gender'] = self::$user_gender;
    }

    do_action('difflex_visitor', $visitor);

    $client->set_visitor($visitor);

    $cart = $wc->cart->get_cart();

    $sessionCartValue = unserialize($wc->session->get('difflex_cart_value', ''));
    
    $offers = array();

    if (count($cart)) {
      foreach ($cart as $k => $v) {
        $sku = !empty($v['variation_id']) ? $v['variation_id'] : $v['product_id'];

        $offer = array(
          'sku' => $sku,
          'qnt' => $v['quantity'],
          'price' => $v['data']->get_price(),
        );
        $product = wc_get_product($sku);
        if ($product) {
          $image_url = wp_get_attachment_url($product->get_image_id());
          if ($image_url) $offer['picture'] = (string) $image_url;
          $offer['url'] = (string) $product->get_permalink();
        }

        $offers[] = $offer;
      }
    }

    if ($sessionCartValue != $offers) {
      $client->track('cartUpdate', array(
        'offers' => $offers
      ));
      $wc->session->set('difflex_cart_value', serialize($offers));
    }
  }

  private static function update_user_info() {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_data = get_user_meta( $user_id );

    self::$user_id = $user_id;

    if (!empty($user_data['first_name'][0]))
      self::$user_first_name = $user_data['first_name'][0];
    if (!empty($user_data['last_name'][0]))
      self::$user_last_name = $user_data['last_name'][0];
    if (!empty($current_user->data->user_email))
      self::$user_email = $current_user->data->user_email;
    if (!empty($user_data['billing_phone'][0]))
      self::$user_phone = $user_data['billing_phone'][0];
  }

  private static function get_params() {
    static $params;
    if (empty($params)) {
      $params = get_option('difflex_plgn_options');
    }
    return $params;
  }

  private static function init_client() {
    $difflex_plgn_options = self::get_params();
    if (empty($difflex_plgn_options['app_key'])) return false;

    $app_key = $difflex_plgn_options['app_key'];

    require_once(DIFFLEX_PLUGIN_DIR . 'lib/DifflexClient.php');
    $client = new DifflexClient($app_key);
    return $client;
  }
}
