<?php
/*
Plugin Name: Oto Döviz Dönüştürücü
Plugin URI: https://forumgnl.com
Description: Ürün fiyatlarını Belirlediğiniz bir döviz cinsinden, Türk Lirası Cinsine dönüştürür.
Version: 1.0
Author: Kadir KAYA | ForumGNL.com
Author URI: https://kadirkaya.com.tr
License: GNU
*/

//k2
function TCMB_Converter($from = 'TRY', $to = 'USD', $val = 1)
{
    if (!function_exists('simplexml_load_string') || !function_exists('curl_init')) {
        return 'Simplexml extension missing.';
    }
    $CurrencyData = [
        'from' => 1,
        'to' => 1
    ];
    try {
        $tcmbMirror = 'https://www.tcmb.gov.tr/kurlar/today.xml';
        $curl = curl_init($tcmbMirror);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $tcmbMirror);

        $dataFromtcmb = curl_exec($curl);
    } catch (Exception $e) {
        echo 'Unhandled exception, maybe from cURL' . $e->getMessage();
        return 0;
    }
    $Currencies = simplexml_load_string($dataFromtcmb);
    foreach ($Currencies->Currency as $Currency) {
        if ($from == $Currency['CurrencyCode']) $CurrencyData['from'] = $Currency->BanknoteSelling;
        if ($to == $Currency['CurrencyCode']) $CurrencyData['to'] = $Currency->BanknoteSelling;
    }
    return round(($CurrencyData['to'] / $CurrencyData['from']) * $val, 10);
}


function woocommerce_custom_select_dropdown(){
    $select = woocommerce_wp_select(array(
        'id' => '_doviz_cinsi_k2',
        'label' =>__('Döviz Cinsini Seçin', 'woocommerce'),
        'options' => array(
            'TRY' => __('TÜRK LİRASI','woocommerce'),
            'USD' => __('ABD DOLARI','wooocommerce'),
            'EUR'=> __('EURO','woocommerce'),
			'AUD'=> __('AVUSTRALYA DOLARI','woocommerce'),
			'DKK'=> __('DANİMARKA KRONU','woocommerce'),
			'GBP'=> __('İNGİLİZ STERLİNİ','woocommerce'),
			'CHF'=> __('İSVİÇRE FRANGI','woocommerce'),
			'SEK'=> __('İSVEÇ KRONU','woocommerce'),
			'CAD'=> __('KANADA DOLARI','woocommerce'),
			'KWD'=> __('KUVEYT DİNARI','woocommerce'),
			'NOK'=> __('NORVEÇ KRONU','woocommerce'),
			'SAR'=> __('SUUDİ ARABİSTAN RİYALİ','woocommerce')
        ),
        
    ));
}
add_action('woocommerce_product_options_general_product_data', 'woocommerce_custom_select_dropdown');



add_filter( 'woocommerce_get_price_html', 'custom_price_message' );
function custom_price_message( $price ) {
	global $product;
	global $post;
    $product = wc_get_product($post->ID);
    $dovizcinsi = $product->get_meta('_doviz_cinsi_k2');
	$vergilifiyat = wc_get_price_including_tax( $product );
	$vergisizfiyat = wc_get_price_excluding_tax( $product );
	$vergitutari = $vergilifiyat - $vergisizfiyat;
	
	$urunfiyati = $product->get_price() + $vergitutari;
	if($dovizcinsi == "TRY" OR $dovizcinsi == ""){
		$new_price = '₺'.number_format($urunfiyati, 2, ',', '.');
	}else{
		$kur = TCMB_Converter('TRY', $dovizcinsi, 1);
		$new_price = '₺'.number_format($urunfiyati*$kur, 2, ',', '.');
	}
return $new_price;
}

function woocommerce_product_custom_fields_save($post_id)
{
    $woocommerce_select_color_field = $_POST['_doviz_cinsi_k2'];
    if (!empty($woocommerce_select_color_field)) {
        update_post_meta($post_id, '_doviz_cinsi_k2', esc_attr($woocommerce_select_color_field));
    }
}
add_action('woocommerce_process_product_meta', 'woocommerce_product_custom_fields_save');
//k2
?>