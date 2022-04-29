<?php
/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';

if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */

function wpt_register_js() {
    wp_register_script('jquery.bootstrap.min', get_template_directory_uri() . 'assets/js/bootstrap.min.js', 'jquery');
    wp_enqueue_script('jquery.bootstrap.min');

}
add_action( 'init', 'wpt_register_js' );
function wpt_register_css() {
    wp_register_style( 'bootstrap.min', get_template_directory_uri() . 'assets/css/bootstrap.min.css' );
    wp_enqueue_style( 'bootstrap.min' );
}
add_action( 'wp_enqueue_scripts', 'wpt_register_css' );
// include custom jQuery
function shapeSpace_include_custom_jquery() {
    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'shapeSpace_include_custom_jquery');

add_action( 'admin_enqueue_scripts', 'true_include_myuploadscript' );

function true_include_myuploadscript( $hook ) {
    // у вас в админке уже должен быть подключен jQuery, если нет - раскомментируйте следующую строку:
    // wp_enqueue_script('jquery');
    // дальше у нас идут скрипты и стили загрузчика изображений WordPress
    if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    // само собой - меняем admin.js на название своего файла
    wp_enqueue_script( 'myuploadscript', get_template_directory_uri() . '/assets/js/admin.js', array('jquery'), null, false );

}

function true_image_uploader_field( $args ) {
    // следующая строчка нужна только для использования на страницах настроек
    $value = get_option( $args[ 'name' ] );
    // следующая строчка нужна только для использования в мета боксах
    $value = $args[ 'value' ];
    $default = get_template_directory_uri() . '/assets/images/customizer/controls/2cr.png';

    if( $value && ( $image_attributes = wp_get_attachment_image_src( $value, array( 150, 110 ) ) ) ) {
        $src = $image_attributes[0];
    } else {
        $src = $default;
    }
    echo '
	<div>
		<img data-src="' . $default . '" src="' . $src . '" width="150" />
		<div>
			<input type="hidden" name="' . $args[ 'name' ] . '" id="' . $args[ 'name' ] . '" value="' . $value . '" />
			<button type="submit" class="upload_image_button button">Загрузить</button>
			<button type="submit" class="remove_image_button button">×</button>
		</div>
	</div>
	';
}

/*
 * Добавляем метабокс
 */
add_action( 'add_meta_boxes', 'true_meta_boxes_u' );

function true_meta_boxes_u() {
    add_meta_box( 'truediv', 'Загрузить изображение', 'true_print_box_u', 'post', 'normal', 'high' );
}

/*
 * Заполняем метабокс
 */
function true_print_box_u( $post ) {
    if( function_exists( 'true_image_uploader_field' ) ) {
        true_image_uploader_field( array(
            'name' => 'uploader_custom',
            'value' => get_post_meta( $post->ID, 'uploader_custom', true ),
        ) );
    }
}

/*
 * Сохраняем данные произвольного поля
 */
add_action('save_post', 'true_save_box_data_u');
function true_save_box_data_u( $post_id ) {

    // ... тут различные проверки на права пользователя, на тип поста, на то, что не автосохранение и т д

    if( isset( $_POST[ 'uploader_custom' ] ) ) {
        update_post_meta( $post_id, 'uploader_custom', absint( $_POST[ 'uploader_custom' ] ) );
    }
    return $post_id;

}




add_filter('woocommerce_product_data_tabs', 'special_new_tabs', 10, 1);
function special_new_tabs($tabs){


    $tabs['custom'] = array(
            'label'    => 'Специальная',
            'target'   => 'custom_product_data',
            'class'    => array( 'show_if_simple' ),
            'priority' => 5,

    );
    return $tabs;
}


add_action('admin_footer', function(){
    ?>
    <style>

        #woocommerce-coupon-data ul.wc-tabs li.custom_options a::before,
        #woocommerce-product-data ul.wc-tabs li.custom_options a::before,
        .woocommerce ul.wc-tabs li.custom_options a::before{
            font-family: WooCommerce;
            content: "\e006";
        }

    </style>

    <?php
});


add_action('woocommerce_product_data_panels', 'info_add_to_tabs_panel');
function info_add_to_tabs_panel($post){
    ?>
    <div id="custom_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <?php
            $arg =  array(
                'id'                => '_text_field',
                'label'             => __( 'Название продукта', 'woocommerce' ),
                'placeholder'       => 'Введите название продукта',
                'type'              => 'text',

            );
            woocommerce_wp_text_input($arg);

            $arg1 =  array(
                'id'                => 'special_price',
                'label'             => __( 'Специальная цена', 'woocommerce' ),
                'placeholder'       => 'Укажите значение специальной цены',
                'desc_tip'          => true,
                'type'              => 'price',

            );
            woocommerce_wp_text_input($arg1);
            true_image_uploader_field( array(
                'name'    => 'uploader_custom',
                'value' => get_post_meta( $post->ID, 'uploader_custom', true ),
            ) );


//            woocommerce_wp_text_input( array(
//                'id'                => '_file_field',
//                'label'             => __( 'Добавьте изображение', 'woocommerce' ),
//                'placeholder'       => 'Добавьте изображение',
//                'type'              => 'image',
//                'post_mime_type'    => 'application/png, application/jpg',
//                'orderby'           => 'menu_order',
//                'order'             => 'desc',
//
//            ) );



            woocommerce_wp_text_input( array(
                'id'                => '_number_field',
                'label'             => __( 'Выберите дату создания продукта', 'woocommerce' ),
                'placeholder'       => 'Укажите дату в формате ДД.ММ.ГГГГ',
                'description'       => __( 'Вводятся только числа', 'woocommerce' ),
                'type'              => 'date',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '00.00.0000',
                ),
            ) );
            woocommerce_wp_select( array(
                'id'      => '_select',
                'label'   => 'Выберите тип продукта',
                'options' => array(
                    'one'   => __( 'rare', 'woocommerce' ),
                    'two'   => __( 'frequent', 'woocommerce' ),
                    'three' => __( 'unusual', 'woocommerce' ),
                ),
            ) );


            ?>




        </div>

    </div>

    <?php
}






//Сохраняем все поля
add_action('woocommerce_process_product_meta', function ($post_id){
    $text_field = isset($_POST['_text_field'])? sanitize_text_field($_POST['_text_field']) : '';
    update_post_meta($post_id, '_text_field', $text_field);

},10,1     );

add_action('woocommerce_process_product_meta', function ($post_id){
    $text_field = isset($_POST['special_price'])? sanitize_text_field($_POST['special_price']) : '';
    update_post_meta($post_id, 'special_price', $text_field);
},10,1     );

add_action('woocommerce_process_product_meta', 'true_save_box_data_woo');
function true_save_box_data_woo( $post_id ) {

   if( isset( $_POST[ 'uploader_custom' ] ) ) {
        update_post_meta( $post_id, 'uploader_custom', absint( $_POST[ 'uploader_custom' ] ) );
    }
    return $post_id;

}

add_action('woocommerce_process_product_meta', function ($post_id){
    $text_field = isset($_POST['_number_field'])? sanitize_text_field($_POST['_number_field']) : '';
    update_post_meta($post_id, '_number_field', $text_field);
},10,1     );

add_action('woocommerce_process_product_meta', function ($post_id){
    $text_field = isset($_POST['_select'])? sanitize_text_field($_POST['_select']) : '';
    update_post_meta($post_id, '_select', $text_field);
},10,1     );


//Вывод под пост
add_action('woocommerce_product_thumbnails', function (){
    $product = wc_get_product();
    echo get_post_meta($product -> get_id(), '_text_field', true);

});

add_action('woocommerce_single_product_summary', function (){
    $product = wc_get_product();
    echo get_post_meta($product -> get_id(), 'special_price', true);

} );

add_action('woocommerce_before_add_to_cart_form', function (){
    $product = wc_get_product();
    echo get_post_meta($product -> get_id(), '_file_field', true);

} );

add_action('woocommerce_before_variations_form', function (){
    $product = wc_get_product();
    echo get_post_meta($product -> get_id(), '_number_field', true);

} );

add_action('woocommerce_before_single_variation', function (){
    $product = wc_get_product();
    echo get_post_meta($product -> get_id(), '_select', true);

} );



