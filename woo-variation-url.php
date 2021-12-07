<?php
/*
Plugin Name: WooCommerce Variations URL
Description: Adds support for variation-specific URL's for WooCommerce product variations
Author: Ali
Version: 1.0
Author URI: https://bealinawaz.com
*/

namespace WCVariationsUrl;

final class Init
{
    /**
     * Call this method to get singleton
     *
     * @return Init
     */
    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Init();
        }
        return $inst;
    }

    /**
     * Private ctor so nobody else can instance it
     *
     */
    private function __construct()
    {
        $this->add_actions();
    }

    /**
     * Add actions
     */
    function add_actions() {
        add_filter('woocommerce_dropdown_variation_attribute_options_args',array($this,'variation_dropdown_args'));
        add_action('init', array($this,'add_rewrite_rules'));
        add_action('wp_head', array($this,'add_js_to_head'));

    }

    function variation_dropdown_args($args) {
        // Get the WooCommerce atts
        $attributes =  wc_get_attribute_taxonomies();
        $atts = [];
        foreach ($attributes as $attribute) {
            $atts[] = $attribute->attribute_name;
        }

        // Get the variations part of URL
        $url_string = get_query_var('variation');

        if ($url_string) {
            $array = [];
            preg_replace_callback(
                "/(\w++)(?>-(\w+-?(?(?!" . implode("|", $atts) . ")(?-1))*))/",
                function($matches) use (&$array) {
                    $array[$matches[1]] = rtrim($matches[2], '-');
                },
                $url_string
            );

            if (!empty($array)) {
                $attribute_key = str_replace('pa_','',$args['attribute']);

                if (array_key_exists($attribute_key,$array)) {
                    $args['selected'] = $array[$attribute_key];
                }
            }
        }

        return $args;

    }

    function add_rewrite_rules() {
        add_rewrite_rule('^product/([^/]*)/([^/]*)/?','index.php?product=$matches[1]&variation=$matches[2]','top');
        add_rewrite_tag('%variation%', '([^&]+)');
    }

    function add_js_to_head() {
        if (!function_exists('is_product') || !is_product())
            return;

        global $post;
        ?>



		<script>
			jQuery(function($){
				setTimeout( function(){
					$( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
						//alert( variation.variation_id );
						//console.log( variation.variation_id );
						var url = '<?php echo get_permalink($post->ID);?>';
						//$('input.variation_id').change( function(){
						//console.log('You just selected variation #' + variation.variation_id);
						var attributes = [];
						var allAttributesSet = true;
						$('table.variations select').each(function() {
							var value = $(this).val();
							if (value) {
								attributes.push({
									id: $(this).attr('name'),
									value: value
								});
							} else {
								allAttributesSet = false;
							}
						});
						if (allAttributesSet) {
							$.each(attributes,function(key, val) {
								var attributeSlug = val.id.replace('attribute_pa_','');
								url = url +'?variation_id='+ variation.variation_id +'&'+attributeSlug+'=' + val.value;
							});
							//console.log('Relocating #' + variation.variation_id);
							//window.location.replace(url);
							//window.location.href = url;
							window.history.pushState('', '',url);
						}

					} );
				}, 400 );

			});
		</script>

    <?php }
}

Init::Instance();