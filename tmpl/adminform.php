<?php

/*  
Copyright Difflex https://difflex.ru
This file is part of the difflex-for-woocommerce plugin created by Difflex.
*/

?>
<div class="wrap">
  <div class="icon32" id="icon-options-general"></div>
  <h2><?php esc_html_e('Difflex Settings', 'difflex') ?></h2>

  <?php if (!empty($message)): ?>
    <div id="message" class="updated fade">
      <p><?= esc_html($message) ?></p>
    </div>
  <?php endif; ?>

  <div>
    <form name="form1" method="post" action="admin.php?page=difflex" enctype="multipart/form-data">

      <table class="form-table">
        <tr valign="top">
          <th scope="row"><?php esc_html_e('APP Key', 'difflex') ?></th>
          <td>
            <input
              class="regular-text code"
              name="app_key"
              type="text"
              value="<?= esc_attr($difflex_plgn_options['app_key']) ?>"
            />
          </td>
        </tr>
      </table>

      <input type="hidden" name="difflex_plgn_form_submit" value="submit" />
      <input type="submit" class="button-primary" value="<?= _e('Save Changes') ?>" />

      <a class="button-secondary" href="https://difflex.ru?utm_source=wordpress&utm_medium=referral&utm_campaign=wordpress_plugin" target="_blank">
        <?php esc_html_e('Go to difflex account', 'difflex') ?>
      </a>

      <?php wp_nonce_field(plugin_basename(dirname(__DIR__)), 'difflex_plgn_nonce_name'); ?>
    </form>
  </div>
</div>