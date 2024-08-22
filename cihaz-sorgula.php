<?php
/**
 * Plugin Name: Teknik Servis Cihaz Sorgulama
 * Description: Müşterilere teknik servis kodu ile cihaz durumlarını görmelerini sağlar.
 * Version: 1.0
 * Author: Ömer ATABER (JRodix.Com Internet Hizmetleri)
 */

defined('ABSPATH') or die('Oyle Yok Giris Yasak');

function dsc_activate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'device_status';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        service_code varchar(100) NOT NULL,
        device_name varchar(255) NOT NULL,
        status varchar(50) NOT NULL,
        actions text NOT NULL,
        images text,
        PRIMARY KEY  (id),
        UNIQUE KEY service_code (service_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'dsc_activate_plugin');

function dsc_add_admin_menu() {
    add_menu_page(
        'Teknik Servis',
        'Teknik Servis',
        'manage_options',
        'device-status-checker',
        'dsc_admin_page',
        'dashicons-admin-tools',
        76
    );
}
add_action('admin_menu', 'dsc_add_admin_menu');

function dsc_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'device_status';

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';

    ?>
    <div class="wrap">
        <h1>Teknik Servis Yönetimi</h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=device-status-checker&tab=list" class="nav-tab <?php echo $tab == 'list' ? 'nav-tab-active' : ''; ?>">Cihazlar</a>
            <a href="?page=device-status-checker&tab=add" class="nav-tab <?php echo $tab == 'add' ? 'nav-tab-active' : ''; ?>">Yeni Cihaz Ekle</a>
        </h2>

        <?php
        if ($tab == 'list') {
            dsc_display_device_list($table_name);
        } elseif ($tab == 'add') {
            dsc_display_device_form($table_name);
        }
        ?>
    </div>

    <style>
        .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        .nav-tab {
            padding: 10px 20px;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: inline-block;
            text-decoration: none;
            color: #333;
        }
        .nav-tab-active {
            background: #fff;
            border-bottom-color: transparent;
            font-weight: bold;
        }
        .widefat th, .widefat td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .widefat thead {
            background-color: #f1f1f1;
        }
        .form-table th {
            width: 150px;
        }
        .dsc-device-info {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .dsc-images img {
            max-width: 100px;
            margin: 5px;
        }
    </style>
    <?php
}

function dsc_display_device_list($table_name) {
    global $wpdb;
    $devices = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <h2>Mevcut Servisler</h2>
    <?php
    if ($devices) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>Teknik Servis Kodu</th><th>Cihaz Adı</th><th>Durum</th><th>İşlemler</th></tr></thead>';
        echo '<tbody>';
        foreach ($devices as $device) {
            echo '<tr>';
            echo '<td>' . esc_html($device->service_code) . '</td>';
            echo '<td>' . esc_html($device->device_name) . '</td>';
            echo '<td>' . esc_html($device->status) . '</td>';
            echo '<td><a href="?page=device-status-checker&tab=add&edit=' . esc_attr($device->service_code) . '">Düzenle</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Henüz kayıtlı cihaz yok.</p>';
    }
}

function dsc_display_device_form($table_name) {
    global $wpdb;

    if (isset($_POST['update_status'])) {
        $service_code = sanitize_text_field($_POST['service_code']);
        $device_name = sanitize_text_field($_POST['device_name']);
        $status = sanitize_text_field($_POST['status']);
        $actions = sanitize_textarea_field($_POST['actions']);
        $images = sanitize_text_field($_POST['images']);

        $wpdb->replace($table_name, array(
            'service_code' => $service_code,
            'device_name' => $device_name,
            'status' => $status,
            'actions' => $actions,
            'images' => $images
        ), array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        ));

        echo '<div class="updated notice is-dismissible"><p>Durum güncellendi.</p></div>';
    }

    if (isset($_GET['edit'])) {
        $edit_code = sanitize_text_field($_GET['edit']);
        $device = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE service_code = %s", $edit_code));
    } else {
        $device = null;
    }

    ?>
    <h2><?php echo $device ? 'Cihazı Düzenle' : 'Yeni Cihaz Ekle'; ?></h2>
    <form method="post">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Teknik Servis Kodu</th>
                <td><input type="text" name="service_code" value="<?php echo esc_attr($device->service_code ?? ''); ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Cihaz Adı</th>
                <td><input type="text" name="device_name" value="<?php echo esc_attr($device->device_name ?? ''); ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Durum</th>
                <td>
                    <select name="status">
                 <option value="teslim alindi" <?php selected($device->status, 'teslim alindi'); ?>>Teslim Alındı</option>
                        <option value="sira bekliyor" <?php selected($device->status, 'sira bekliyor'); ?>>Sıra Bekliyor</option>
                        <option value="tespit asamasinda" <?php selected($device->status, 'tespit asamasinda'); ?>>Tespit Aşamasında</option>
                        <option value="inceleniyor" <?php selected($device->status, 'inceleniyor'); ?>>İnceleniyor</option>
                        <option value="ariza bilgisi" <?php selected($device->status, 'ariza bilgisi'); ?>>Arıza Bilgisi</option>
                        <option value="islem yapiliyor" <?php selected($device->status, 'islem yapiliyor'); ?>>İşlem Yapılıyor</option>
                        <option value="iade" <?php selected($device->status, 'iade'); ?>>İade</option>
                        <option value="tamamlandi" <?php selected($device->status, 'tamamlandi'); ?>>Tamamlandı</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">İşlemler</th>
                <td><textarea name="actions" rows="4"><?php echo esc_textarea($device->actions ?? ''); ?></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row">Görseller (URL'leri virgülle ayırın)</th>
                <td><input type="text" name="images" value="<?php echo esc_attr($device->images ?? ''); ?>" /></td>
            </tr>
        </table>
        <input type="submit" name="update_status" value="<?php echo $device ? 'Durumu Güncelle' : 'Cihaz Ekle'; ?>" class="button button-primary"/>
    </form>
    <?php
}

function dsc_device_status_shortcode($atts) {
    ob_start();

    ?>
    <div class="dsc-form-container">
        <h2>Teknik Servis Kodunu Girin</h2>
        <form method="post">
            <label for="service_code">Teknik Servis Kodu:</label>
            <input type="text" id="service_code" name="service_code" required />
            <input type="submit" name="dsc_check_status" value="Durumu Göster" />
        </form>

        <?php
        if (isset($_POST['dsc_check_status'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'device_status';
            $service_code = sanitize_text_field($_POST['service_code']);
            $device = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE service_code = %s", $service_code));

            if ($device) {
                ?>
                <div class="dsc-device-info">
                    <h3>Cihaz Bilgileri</h3>
                    <p><strong>Cihaz Adı:</strong> <?php echo esc_html($device->device_name); ?></p>
                    <p><strong>Durum:</strong> <?php echo esc_html($device->status); ?></p>
                    <p><strong>İşlemler:</strong> <?php echo nl2br(esc_html($device->actions)); ?></p>
                    <div class="dsc-images">
                        <?php
                        $images = explode(',', $device->images);
                        foreach ($images as $image) {
                            echo '<img src="' . esc_url(trim($image)) . '" alt="Cihaz Görseli" />';
                        }
                        ?>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="dsc-status-message error">
                    <p>Bu teknik servis koduyla kayıtlı cihaz bulunamadı.</p>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <style>
        .dsc-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .dsc-device-info {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .dsc-images img {
            max-width: 100px;
            margin: 5px;
        }
        .dsc-status-message {
            padding: 10px;
            border: 1px solid;
            border-radius: 5px;
            margin-top: 20px;
        }
        .dsc-status-message.error {
            border-color: #ff0000;
            color: #ff0000;
        }
        .dsc-status-message.success {
            border-color: #00ff00;
            color: #00ff00;
        }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('device_status_checker', 'dsc_device_status_shortcode');
