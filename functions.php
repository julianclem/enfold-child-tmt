<?php

/*
 * Add your own functions here. You can also copy some of the theme functions into this file. 
 * Wordpress will use those functions instead of the original functions then.
 */

// START TikTok
function avia_add_custom_icons($icons)
{
  $icons['tiktok'] = array('font' => 'tiktok-fontello', 'icon' => 'ue800', 'display_name' => 'TikTok');
  $icons['tiktok_inverted'] = array('font' => 'tiktok-fontello', 'icon' => 'ue801', 'display_name' => 'TikTok');
  return $icons;
}

add_filter('avf_default_icons', 'avia_add_custom_icons', 10, 1);

function avia_add_custom_social_icons($icons)
{
  $icons['TikTok'] = 'tiktok';
  return $icons;
}

add_filter('avf_social_icons_options', 'avia_add_custom_social_icons', 10, 1);
// END TikTok

// START banner image admin
add_filter('big_image_size_threshold', '__return_false');
add_filter('avf_option_page_data_init', 'banner_image_admin_options', 10, 1);
function banner_image_admin_options($avia_elements)
{
  $new_elements[] = array(
    "slug" => "header",
    "name" => __("Banner image", 'avia_framework'),
    "desc" => __("Specify a banner image for your site. The image will be shown between the logo/menu area and the title/breadcrumb area. The image will be centered, and will extend beyond the page container if it is wide enough.", 'avia_framework') . '<br /><br />' .
      __("Be sure to select the full size version of the image!", 'avia_framework'),
    "id" => "banner_image",
    "type" => "upload",
    "label" => __("Use Image as Banner image", 'avia_framework')
  );

  $pixel_sizes = array();
  for ($x = 50; $x <= 300; $x++) {
    $pixel_sizes[$x . 'px'] = $x;
  }

  $new_elements[] = array(
    "slug" => "header",
    "name" => __("Banner image height", 'avia_framework'),
    "desc" => __("The height (in pixels) to display the banner image for your site. The image selected above should be at least this height to avoid pixellation.", 'avia_framework'),
    "id" => "banner_image_height",
    "type" => "select",
    "std" => "150",
    "subtype" => $pixel_sizes
  );

  $new_elements[] = array(
    "slug" => "header",
    "name" => __("Banner image mobile height", 'avia_framework'),
    "desc" => __("The height (in pixels) to display the banner image for your site on mobile devices.", 'avia_framework'),
    "id" => "banner_image_mobile_height",
    "type" => "select",
    "std" => "100",
    "subtype" => $pixel_sizes
  );

  // Try to find header style element, and add after that. Otherwise, add at end of header options.
  $found = false;
  $index = 0;
  $search = 'header_style';

  foreach ($avia_elements as $key => $element) {
    $index++;
    if (isset($element['id']) && ($element['id'] == $search)) {
      $found = true;
      break;
    }
  }

  if ($found) {
    $avia_elements = array_merge(array_slice($avia_elements, 0, $index), $new_elements, array_slice($avia_elements, $index));
  } else {
    $avia_elements = array_merge($avia_elements, $new_elements);
  }

  return $avia_elements;
}
// END banner image admin

// START banner image front end
add_action('wp_head', 'banner_image_stylesheet', 30, 1);
function banner_image_stylesheet()
{
  global $avia_config;
  $height = avia_get_option('banner_image_height');
  $mobile_height = avia_get_option('banner_image_mobile_height');
  $html = '';
  $html .= '<style id="banner-image-inline-css" type="text/css" media="screen">';
  $html .= '.banner_image{';
  $html .= 'background-color:#000;';
  $html .= 'background-position:center;';
  $html .= 'background-size:auto 100%;';
  $html .= 'background-repeat:no-repeat;';
  $html .= 'height:' . $height . 'px;';
  $html .= 'margin-top:1px;';
  $html .= '}';
  $html .= '@media (max-width: 767px){';
  $html .= '.banner_image{';
  $html .= 'height:' . $mobile_height . 'px;';
  $html .= '}';
  $html .= '}';
  $html .= '</style>';
  echo $html;
}

add_action('ava_after_main_container', 'banner_image', 11);
function banner_image()
{
  global $avia_config;
  $image = avia_get_option('banner_image');
  if (!$image)
    return;

  echo '<div class="banner_image" style="background-image: url(\'' . $image . '\');"></div>';
}
// END banner image front end

// START override title bar - no link in H1 for a page
function avia_title($args = false, $id = false)
{
  global $avia_config;

  if (!$id)
    $id = avia_get_the_id();

  $header_settings = avia_header_setting();
  if ($header_settings['header_title_bar'] == 'hidden_title_bar')
    return "";

  $defaults = array(

    'title' => get_the_title($id),
    'subtitle' => "", //avia_post_meta($id, 'subtitle'),
    'link' => get_permalink($id),
    'html' => "<div class='{class} title_container'><div class='container'>{heading_html}{additions}</div></div>",
    'heading_html' => "<{heading} class='main-title entry-title {heading_class}'>{title}</{heading}>",
    'class' => 'stretch_full container_wrap alternate_color ' . avia_is_dark_bg('alternate_color', true),
    'breadcrumb' => true,
    'additions' => "",
    'heading' => 'h1', //headings are set based on this article: http://yoast.com/blog-headings-structure/
    'heading_class' => ''
  );

  if (is_tax() || is_category() || is_tag()) {
    global $wp_query;

    $term = $wp_query->get_queried_object();
    $defaults['link'] = get_term_link($term);
  } else if (is_archive() || is_page()) {
    $defaults['link'] = "";
  }


  // Parse incomming $args into an array and merge it with $defaults
  $args = wp_parse_args($args, $defaults);

  /**
   * @used_by		config-woocommerce\config.php avia_title_args_woopage()				10
   * @since < 4.0
   * @return array
   */
  $args = apply_filters('avf_title_args', $args, $id);

  //disable breadcrumb if requested
  if ($header_settings['header_title_bar'] == 'title_bar')
    $args['breadcrumb'] = false;

  //disable title if requested
  if ($header_settings['header_title_bar'] == 'breadcrumbs_only')
    $args['title'] = '';


  // OPTIONAL: Declare each item in $args as its own variable i.e. $type, $before.
  extract($args, EXTR_SKIP);

  if (empty($title))
    $class .= " empty_title ";
  $markup = avia_markup_helper(array('context' => 'avia_title', 'echo' => false));
  if (!empty($link) && !empty($title))
    $title = "<a href='" . $link . "' rel='bookmark' title='" . __('Permanent Link:', 'avia_framework') . " " . esc_attr($title) . "' $markup>" . $title . "</a>";
  if (!empty($subtitle))
    $additions .= "<div class='title_meta meta-color'>" . wpautop($subtitle) . "</div>";
  if ($breadcrumb)
    $additions .= avia_breadcrumbs(array('separator' => '/', 'richsnippet' => true, 'before' => '', 'front_page' => false));


  if (!$title)
    $heading_html = "";
  $html = str_replace('{heading_html}', $heading_html, $html);


  $html = str_replace('{class}', $class, $html);
  $html = str_replace('{title}', $title, $html);
  $html = str_replace('{additions}', $additions, $html);
  $html = str_replace('{heading}', $heading, $html);
  $html = str_replace('{heading_class}', $heading_class, $html);

  if (!empty($avia_config['slide_output']) && !avia_is_dynamic_template($id) && !avia_is_overview()) {
    $avia_config['small_title'] = $title;
  } else {
    return $html;
  }
}
// END override title bar - no link in H1 for a page

// START TMT color scheme
add_filter('avf_skin_options', function ($styles = " ") {
  $white = '#ffffff';

  $ghost_50 = '#faf8fa';
  $ghost_100 = '#f4f3f4';
  $ghost_200 = '#ebe8ec';
  $ghost_300 = '#dad6dc';
  $ghost_800 = '#706572';
  $ghost_900 = '#5d545e';
  $ghost_950 = '#3c373e';

  $studio_700 = '#82319a';

  $maroon_700 = '#8e0b6b';
  $maroon_800 = '#5f0748';

  $teal_800 = '#0c575a';
  $teal_850 = '#094143';

  $blue_800 = '#07485f';
  $blue_850 = '#053648';

  $styles["TMT 2024"] = array (
    'style' => "background-color: $maroon_700",
    'default_font' => 'Lato:300,400,700',
    'google_webfont' => 'Lato:300,400,700',
    'color_scheme' => 'TMT 2024',

    // header
    'colorset-header_color-bg' => $white,
    'colorset-header_color-bg2' => $ghost_50,
    'colorset-header_color-primary' => $studio_700,
    'colorset-header_color-secondary' => '#444444',
    'colorset-header_color-color' => $studio_700,
    'colorset-header_color-border' => '',
    'colorset-header_color-img' => '',
    'colorset-header_color-customimage' => '',
    'colorset-header_color-pos' => 'center center',
    'colorset-header_color-repeat' => 'repeat',
    'colorset-header_color-attach' => 'scroll',
    'colorset-header_color-heading' => $ghost_900,
    'colorset-header_color-meta' => $ghost_800,

    // main
    'colorset-main_color-bg' => $ghost_50,
    'colorset-main_color-bg2' => $white,
    'colorset-main_color-primary' => $maroon_700,
    'colorset-main_color-secondary' => $maroon_800,
    'colorset-main_color-color' => $ghost_950,
    'colorset-main_color-border' => $ghost_50,
    'colorset-main_color-img' => '',
    'colorset-main_color-customimage' => '',
    'colorset-main_color-pos' => 'top center',
    'colorset-main_color-repeat' => 'repeat',
    'colorset-main_color-attach' => 'scroll',
    'colorset-main_color-heading' => $ghost_900,
    'colorset-main_color-meta' => $ghost_800,

    // alternate
    'colorset-alternate_color-bg' => $teal_800,
    'colorset-alternate_color-bg2' => $teal_850,
    'colorset-alternate_color-primary' => $ghost_300,
    'colorset-alternate_color-secondary' => $white,
    'colorset-alternate_color-color' => $ghost_50,
    'colorset-alternate_color-border' => $teal_800,
    'colorset-alternate_color-img' => '',
    'colorset-alternate_color-customimage' => '',
    'colorset-alternate_color-pos' => 'top center',
    'colorset-alternate_color-repeat' => 'repeat',
    'colorset-alternate_color-attach' => 'scroll',
    'colorset-alternate_color-heading' => $ghost_100,
    'colorset-alternate_color-meta' => $ghost_200,

    // footer
    'colorset-footer_color-bg' => $blue_800,
    'colorset-footer_color-bg2' => $blue_850,
    'colorset-footer_color-primary' => $ghost_300,
    'colorset-footer_color-secondary' => $white,
    'colorset-footer_color-color' => $ghost_100,
    'colorset-footer_color-border' => $blue_800,
    'colorset-footer_color-img' => '',
    'colorset-footer_color-customimage' => '',
    'colorset-footer_color-pos' => 'top center',
    'colorset-footer_color-repeat' => 'repeat',
    'colorset-footer_color-attach' => 'scroll',
    'colorset-footer_color-heading' => $ghost_300,
    'colorset-footer_color-meta' => $ghost_200,

    // socket
    'colorset-socket_color-bg' => $blue_850,
    'colorset-socket_color-bg2' => $blue_800,
    'colorset-socket_color-primary' => $ghost_300,
    'colorset-socket_color-secondary' => $white,
    'colorset-socket_color-color' => $ghost_100,
    'colorset-socket_color-border' => $blue_850,
    'colorset-socket_color-img' => '',
    'colorset-socket_color-customimage' => '',
    'colorset-socket_color-pos' => 'top center',
    'colorset-socket_color-repeat' => 'repeat',
    'colorset-socket_color-attach' => 'scroll',
    'colorset-socket_color-heading' => $ghost_300,
    'colorset-socket_color-meta' => $ghost_200,

    // body bg
    'color-body_style' => 'stretched',
    // unused below here because 'stretched' is selected
    'color-body_color' => '#333333', // bg
    'color-body_fontcolor' => '#ffffff', // font
    'color-body_attach' => 'scroll',
    'color-body_repeat' => 'repeat',
    'color-body_pos' => 'center center',
    'color-body_img' => '',
    'color-body_customimage' => '',
  );

  return $styles;
});
// END TMT color scheme