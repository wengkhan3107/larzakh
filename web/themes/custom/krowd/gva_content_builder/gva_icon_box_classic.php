<?php
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

if (!class_exists('element_gva_icon_box_classic')):
   class element_gva_icon_box_classic
   {
      public function render_form()
      {
         $option_1 = array(
            '0' => '--Default--',
            '14' => '14',
            '16' => '16',
            '18' => '18',
            '20' => '20',
            '22' => '22',
            '24' => '24',
            '26' => '26',
            '28' => '28',
            '30' => '30',
            '32' => '32',
            '34' => '34',
            '36' => '36',
            '38' => '38',
            '40' => '40',
            '42' => '42',
            '44' => '44',
            '46' => '46',
            '48' => '48',
            '50' => '50',
            '52' => '52',
            '54' => '54',
            '56' => '56',
            '58' => '58',
            '60' => '60',
            '70' => '70',
            '80' => '80',
            '90' => '90',
            '100' => '100'
         );
         $fields = array(
            'type' => 'element_gva_icon_box_classic',
            'title' => ('Icon Box Classic'),
            'fields' => array(
               array(
                  'id' => 'title',
                  'type' => 'text',
                  'title' => t('Title'),
                  'admin' => true
               ),
               array(
                  'id' => 'content',
                  'type' => 'textarea',
                  'title' => t('Content'),
                  'desc' => t('Some Shortcodes and HTML tags allowed'),
               ),
               array(
                  'id' => 'hidden_content',
                  'type' => 'select',
                  'options' => array(
                     '' => t('Always Display'),
                     'hidden-xs hidden-sm' => t('Hidden Small & Extra Small Screen (hidden-sm & hidden-xs)'),
                     'hidden-sm' => t('Hidden Small Screen (hidden-sm)'),
                     'hidden-xs' => t('hidden Extra Small Screen (hidden-xs)'),
                  ),
                  'title' => t('Hidden Content in Small Screen'),
               ),
               array(
                  'id' => 'icon',
                  'type' => 'text',
                  'title' => t('Icon class'),
                  'std' => '',
                  'desc' => t('Use class icon font <a target="_blank" href="http://fontawesome.io/icons/">Icon Awesome</a> or <a target="_blank" href="http://gaviasthemes.com/icons/">Custom icon</a>'),
               ),
               array(
                  'id' => 'image',
                  'type' => 'upload',
                  'title' => t('Image Icon'),
                  'desc' => t('Use image icon instead of icon class'),
               ),
               array(
                  'id' => 'icon_position',
                  'type' => 'select',
                  'options' => array(
                     'top-center' => 'Top Center',
                     'left v1' => 'Left #1',
                     'left v2' => 'Left #2',
                     'left v3' => 'Left #3',
                  ),
                  'title' => t('Icon Position'),
                  'std' => 'top',
               ),
               array(
                  'id' => 'link',
                  'type' => 'text',
                  'title' => t('Link'),
                  'desc' => t('Link for text'),
                  'class' => 'width-1-2'
               ),
               array(
                  'id' => 'target',
                  'type' => 'select',
                  'options' => array('off' => 'No', 'on' => 'Yes'),
                  'title' => t('Open in new window'),
                  'class' => 'width-1-2',
                  'desc' => t('Adds a target="_blank" attribute to the link.'),
               ),
               array(
                  'id' => 'box_background',
                  'type' => 'text',
                  'title' => t('Box Background'),
                  'desc' => t('Box Background, e.g: #f5f5f5')
               ),

               array(
                  'id' => 'info',
                  'type' => 'info',
                  'title' => 'Background, Color Icon'
               ),

               array(
                  'id' => 'icon_bg_available',
                  'type' => 'select',
                  'title' => 'Background Icon Available',
                  'class' => 'width-1-4',
                  'options' => array(
                     '' => '------',
                     'bg-theme' => 'Background Theme',
                     'bg-black' => 'Background Black',
                     'bg-dark' => 'Background Dark',
                     'bg-white' => 'Background White'
                  )
               ),
               array(
                  'id' => 'icon_background',
                  'type' => 'text',
                  'title' => 'Custom Background Icon',
                  'desc' => t('e.g: #f5f5f5'),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'icon_color_available',
                  'type' => 'select',
                  'title' => 'Icon Color Available',
                  'class' => 'width-1-4',
                  'options' => array(
                     '' => '------',
                     'color-theme' => 'Color Theme',
                     'color-black' => 'Color Black',
                     'color-dark' => 'Color Dark',
                     'color-white' => 'Color White'
                  )
               ),
               array(
                  'id' => 'icon_color',
                  'type' => 'text',
                  'title' => t('Custom Icon Color'),
                  'desc' => t('e.g: #f5f5f5'),
                  'class' => 'width-1-4',
               ),

               array(
                  'id' => 'icon_width',
                  'type' => 'select',
                  'title' => t('Icon Width'),
                  'options' => array(
                     'fa-1x' => t('Fa 1x small'),
                     'fa-2x' => t('Fa 2x'),
                     'fa-3x' => t('Fa 3x'),
                     'fa-4x' => t('Fa 4x'),
                     'fa-5x' => t('Fa 5x'),
                     'width-full' => t('Width 100%'),
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'icon_radius',
                  'type' => 'select',
                  'title' => t('Icon Radius'),
                  'options' => array(
                     '' => t('--None--'),
                     'radius-1x' => t('Radius 1x'),
                     'radius-2x' => t('Radius 2x'),
                     'radius-5x' => t('Radius 5x'),
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'icon_border',
                  'type' => 'select',
                  'title' => t('Icon Border'),
                  'options' => array(
                     '' => t('--None--'),
                     'border-1' => t('Border 1px'),
                     'border-2' => t('Border 2px'),
                     'border-3' => t('Border 3px'),
                     'border-4' => t('Border 4px'),
                     'border-5' => t('Border 5px'),
                     'border-s1' => t('Border Padding'),
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'border_color',
                  'type' => 'select',
                  'title' => 'Border Color',
                  'class' => 'width-1-4',
                  'options' => array(
                     '' => '------',
                     'theme' => 'Color Theme',
                     'black' => 'Color Black',
                     'dark' => 'Color Dark',
                     'white' => 'Color White'
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'title_font_size',
                  'type' => 'select',
                  'title' => t('Title Font Size'),
                  'options' => $option_1,
                  'default' => '0',
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'title_line_height',
                  'type' => 'select',
                  'title' => t('Title Line Height'),
                  'options' => $option_1,
                  'default' => '0',
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'title_color',
                  'type' => 'select',
                  'title' => t('Title Color'),
                  'options' => array(
                     'text-black' => 'Black Color',
                     'text-white' => 'White Color',
                     'text-theme' => 'Theme Color'
                  ),
                  'default' => 'text-black',
                  'class' => 'width-1-4'
               ),
               array(
                  'id' => 'desc_color',
                  'type' => 'select',
                  'title' => t('Description Color'),
                  'options' => array(
                     'text-gray' => 'Gray Color',
                     'text-gray-light' => 'Gray Light Color',
                     'text-black' => 'Black Color',
                     'text-white' => 'White Color',
                     'text-theme' => 'Theme Color'
                  ),
                  'default' => 'text-gray',
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'box_shadow',
                  'type' => 'select',
                  'title' => 'Icon Box Shadow',
                  'options' => array(
                     '' => t('Disable Box Shadow'),
                     'icon-shadow' => t('Enable Box Shadow')
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'vertical_align_content',
                  'type' => 'select',
                  'title' => 'Verticle Align content',
                  'options' => array(
                     'top' => t('Top'),
                     'middle' => t('Middle'),
                     'bottom' => t('Bottom')
                  ),
                  'class' => 'width-1-4',
               ),
               array(
                  'id' => 'margin',
                  'type' => 'select',
                  'title' => t('Margin Bottom'),
                  'options' => array(
                     'box-margin-0' => t('Remove Margin Bottom'),
                     'box-margin-small' => t('Margin Bottom Small'),
                     'box-margin-medium' => t('Margin Bottom Medium'),
                     'box-margin-large' => t('Margin Bottom Large'),
                  ),
                  'class' => 'width-1-4',
                  'default' => 'box-margin-small'
               ),

               array(
                  'id' => 'effect',
                  'type' => 'select',
                  'title' => t('Animation'),
                  'options' => array(
                     'effect' => t('None'),
                     'effect-v1' => t('Effect V1'),
                  ),
                  'class' => 'width-1-4',
                  'default' => ''
               ),

               array(
                  'id' => 'animate',
                  'type' => 'select',
                  'title' => t('Animation'),
                  'desc' => t('Entrance animation for element'),
                  'options' => gavias_content_builder_animate(),
                  'class' => 'width-1-3'
               ),
               array(
                  'id' => 'animate_delay',
                  'type' => 'select',
                  'title' => t('Animation Delay'),
                  'options' => gavias_content_builder_delay_wow(),
                  'desc' => '0 = default',
                  'class' => 'width-1-3'
               ),

               array(
                  'id' => 'el_class',
                  'type' => 'text',
                  'title' => t('Extra class name'),
                  'desc' => t('Style particular content element differently - add a class name and refer to it in custom CSS.'),
                  'class' => 'width-1-3'
               ),

            ),
         );
         return $fields;
      }

      /**
       * Generate the hash key for handshake.
       */
      public static function hsr_sso_hash_key($domain, $private_key, $token = NULL)
      {
         $domain = strtolower($domain);

         if (empty($token)) {
            $token = session_id();
         }

         $auth = self::hsr_sso_hmac('md5', $token . $domain, $private_key);
         return $auth;
      }

      /**
       * Generate hash_hmac alternative function.
       */
      public static function hsr_sso_hmac($algo, $data, $key, $raw_output = FALSE)
      {
         $algo = strtolower($algo);
         $pack = 'H' . strlen($algo('test'));
         $size = 64;
         $opad = str_repeat(chr(0x5C), $size);
         $ipad = str_repeat(chr(0x36), $size);

         if (strlen($key) > $size) {
            $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
         } else {
            $key = str_pad($key, $size, chr(0x00));
         }

         for ($i = 0; $i < strlen($key) - 1; $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
         }

         $output = $algo($opad . pack($pack, $algo($ipad . $data)));

         //echo "testing purpose details<br>";
         //echo "DATA:" . $data . "<br> OPAD: " . $opad . "<br> IPAD: " . $ipad . "<br> KEY: " . $key;
         //echo "<br> OUTPUT: " . $output;
         //echo "<br>done testing purpose <br><br>";
         //return;

         return ($raw_output) ? pack($pack, $output) : $output;
      }

      public static function render_content($attr = array(), $content = '')
      {
         global $base_url;
         extract(gavias_merge_atts(array(
            'title' => '',
            'content' => '',
            'hidden_content' => '',
            'icon' => '',
            'image' => '',
            'icon_position' => 'top',
            'box_background' => '',
            'icon_color_available' => '',
            'icon_color' => '',
            'icon_bg_available' => '',
            'icon_background' => '',
            'icon_radius' => '',
            'icon_border' => '',
            'border_color' => '',
            'margin' => 'box-margin-small',
            'icon_width' => 'fa-2x',
            'link' => '',
            'box_shadow' => '',
            'title_font_size' => '0',
            'title_line_height' => '0',
            'title_color' => 'text-dark',
            'desc_color' => 'text-dark',
            'vertical_align_content' => 'top',
            'target' => '',
            'animate' => '',
            'animate_delay' => '',
            'min_height' => '',
            'el_class' => '',
            'effect' => ''
         ), $attr));

         if ($image)
            $image = $base_url . $image;

         // target
         if ($target == 'on') {
            $target = 'target="_blank"';
         } else {
            $target = false;
         }

         $class = array();
         $class[] = $icon_position;
         $class[] = $margin;
         if ($image)
            $class[] = 'icon-image';
         if ($el_class)
            $class[] = $el_class;
         if ($effect)
            $class[] = $effect;

         if ($box_background)
            $class[] = 'box-background';
         if ($icon_border)
            $class[] = 'icon-border';
         if ($icon_background)
            $class[] = 'icon-background';

         if ($icon_border == 'border-s1')
            $class[] .= " border-s1";

         $icon_class = "{$icon_width} {$icon_radius} {$icon_border}";
         if ($border_color)
            $icon_class .= " i-border-{$border_color}";
         if ($icon_border || $icon_background || $icon_bg_available)
            $icon_class .= ' fa-stack';

         if ($icon_bg_available)
            $icon_class .= ' ' . $icon_bg_available;
         if ($icon_color_available)
            $icon_class .= ' ' . $icon_color_available;

         $icon_class_inner = "";
         if ($icon_border == 'border-s1') {
            $icon_class_inner .= "{$icon_radius} i-border-{$border_color}";
         }

         if ($box_shadow)
            $icon_class .= " {$box_shadow}";

         $style = array(); // Style box
         if ($min_height)
            $style[] = "min-height:{$min_height};";
         if ($box_background)
            $style[] = "background-color:{$box_background};";

         $style_icon = ''; // Style icon
         if ($icon_background)
            $style_icon .= "background: {$icon_background};";
         if ($icon_color)
            $style_icon .= "color: {$icon_color};";
         if ($style_icon)
            $style_icon = "style=\"{$style_icon}\"";

         $classes_title_text = '';
         $classes_title = array();
         $title_line_height ? $classes_title[] = "lheight-{$title_line_height}" : false;
         $title_font_size ? $classes_title[] = "fsize-{$title_font_size}" : false;
         $classes_title[] = $title_color;
         $classes_title_text = implode(' ', $classes_title);

         $classes_desc_text = '';
         $classes_desc = array();
         $classes_desc[] = $desc_color;
         $classes_desc[] = $hidden_content;
         $classes_desc_text = implode(' ', $classes_desc);

         if ($animate)
            $class[] = 'wow ' . $animate;

         $title_html = $title;
         if ($link) {
            $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

            $uid = $user->get('name')->value;

            if ($user) {
               $parse = parse_url($link);
               if (isset($parse['host'])) {
                  $host = $parse['scheme'] . '://' . $parse['host'];
                  $hosto = $parse['scheme'] . '://' . $parse['host'];

                  $domains = [
                     'http://epersonel.kkr.gov.my' => ['private' => 'abda5129e1bb26a582837ed81720f3fc', 'key' => 'dad3410a0644', 'add' => '/', 'param' => '&r=site/login'],
                     'http://helpdesk.kkr.gov.my' => ['private' => 'd4502ef64a50177a7ea73cc7d84588a9', 'key' => '3d8c3d95b121', 'add' => '/', 'param' => '&r=site/login'],
                     //'http://epass.kkr.gov.my' => ['private' => 'a07539b7ae533c85b73b6ac13e927320', 'key' => '67a1b5cdb9ad'],
                     //'http://epelekat.kkr.gov.my' => ['private' => 'f0765ab319fc2344ab6988af92c2bd5d', 'key' => '61fa50b37276'],

                     //   'http://etempahan.kkr.gov.my' => ['private' => '079d13affc5dd3494afc6c1feca96b69', 'key' => '59ce9f83ed61'], 
                     //  'https://etempahan.kkr.gov.my' => ['private' => '7d5e07a12173e04a7a0d6d635245c041', 'key' => '50c5b5b7c052', 'add' => '/login', 'param' => ''],
                     'https://etempahan.kkr.gov.my' => ['private' => '3af8c4a538e41278f90fcb912bdae53c', 'key' => '8eeed5d1bfac', 'add' => '/login', 'param' => ''],
                     //  'https://etempahanv2.kkr.gov.my' => ['private' => '7d5e07a12173e04a7a0d6d635245c041', 'key' => '50c5b5b7c052', 'add' => '/login', 'param' => ''],
                     'https://etempahanv2.kkr.gov.my' => ['private' => '079d13affc5dd3494afc6c1feca96b69', 'key' => '59ce9f83ed61'],

                     'https://10.9.206.131' => ['private' => 'ce889833c818bf17620cb869fec72a80', 'key' => '71f5e8cd183b', 'add' => '/login', 'param' => ''],
                     //'http://etempahan.kkr.gov.my' => ['private' => '21c68a3bb39cd4c0160264b172041b69', 'key' => 'bab76f06b0ca'],
                     'http://elatihan.kkr.gov.my' => ['private' => '487c41f891a2d6e774d3fd0e0c9bdbbf', 'key' => '1a0ed34fe665', 'param' => '&r=site/loginsso'],
                     'http://ermlt.kkr.gov.my' => ['private' => '756a7d512e7c47dff21a1f46d72c6990', 'key' => '523670223668', 'param' => '&r=site/login', 'add' => '/'],
                     'http://erakam.kkr.gov.my' => ['private' => '11e2ecf4e8b401e73a7febecf2431407', 'key' => '376d6f5f7fce', 'add' => '/'],
                     'http://stk.kkr.gov.my' => ['private' => '085a9ac4d98baf3ea667dc25d9d18ac4', 'key' => '5a302561f0b4', 'add' => '/', 'param' => '&r=site/loginsso'],
                     //'http://etna.kkr.gov.my' => ['private' => '90ee733e016b6609f3c0febd4a283f41', 'key' => 'ffd6b51eae76', 'add' => '/','param'=>'&r=site/login'],
                     'http://etna.kkr.gov.my' => ['private' => 'd894761bfbe9c950089d3c14b15f43b7', 'key' => 'bdacd72f1244', 'add' => '/', 'param' => '&r=site/login'],
                     'http://enaikpangkat.kkr.gov.my' => ['private' => '0609c81430bb2ffe0e82f4470ee83ff9', 'key' => '71f5e8cd183b', 'param' => '&r=site/loginsso', 'add' => '/'],
                     //'http://snk.kkr.gov.my' => ['private' => '86e556ebb24dce08175835745286ba7c', 'key' => 'ee4ef8e83f98', 'add' => '/'],
                     //'https://snk.kkr.gov.my' => ['private' => 'a12bdf6b14a5f5d0ccf0e065c8864295', 'key' => '5757c7a74fda', 'param'=>'/login/log_masuk'],
                     'http://snk.kkr.gov.my' => ['private' => 'a12bdf6b14a5f5d0ccf0e065c8864295', 'key' => '5757c7a74fda', 'param' => '/login/log_masuk'],
                     'http://rtvm.kkr.gov.my' => ['private' => 'ac22dbc769389c001db6f8475a6fefa7', 'key' => '018393eee33d'],
                     //'http://elatihan.kkr.gov.my/internship/index.php?' => ['private' => '310d115d0e2ab63340dde8bcf215e4f9', 'key' => 'dbade460f330', 'add' => '/', 'param'=>'&r=site/login'],
                     'https://internship.kkr.gov.my' => ['private' => '310d115d0e2ab63340dde8bcf215e4f9', 'key' => 'dbade460f330', 'add' => '/', 'param' => '&r=site/login'],
                     'https://epaskat.kkr.gov.my' => ['private' => '88682a3a933579dbd7c496fb1d7c313e', 'key' => '9137e4f8a19f', 'add' => '/', 'param' => 'login'],
                  ];

                  if (isset($domains[$host])) {
                     //var_dump($domains[$host]);
                     //$token = time();
                     $token = session_id();
                     $key = $domains[$host]['private'];
                     $host .= (array_key_exists('add', $domains[$host]) ? $domains[$host]['add'] : '');

                     $host = strtolower(($host));
                     $token = empty($token) ? session_id() : $token;
                     $auth2 = hash_hmac('md5', $token . $host, $key); //Come from PHP8
                     $auth = self::hsr_sso_hash_key($host, $key, $token); //Leagacy method
                     
                     //var_dump($host);
                     //var_dump($auth);
                     //var_dump($token."<br>");
                     // $link =  str_replace('hahayayata', $uid, $link);
                     // $title = $parse['path'];

                     // echo "testing purpose details<br>";
                     // echo "PARSE SCHEME:" . $parse['scheme']. "<br> PARSE HOST: " .$parse['host']. "<br> PARSE PATH: ".$parse['path']. "<br> HOST TO: ".$domains[$hosto]. "<br> HOST TO PARAM: ".$domains[$hosto]['param']. "<br>";

                     // if($link == 'http://epersonel.kkr.gov.my/index.php?r=site%2Findex_kln') {
                     //    $link =  $parse['scheme'].'://'.$parse['host']. $parse['path'].'?uid='.$uid.'&auth='.$auth.'&token='. $token.'&r=site%2Findex_kln';
                     // } else {
                     //    $link =  $parse['scheme'].'://'.$parse['host']. $parse['path'].'?uid='.$uid.'&auth='.$auth.'&token='. $token.'&sso=turu'.(array_key_exists('param',$domains[$hosto]) ? $domains[$hosto]['param'] : '');
                     // }

                     $link = $parse['scheme'] . '://' . $parse['host'] . $parse['path'] . '?uid=' . $uid . '&auth=' . $auth . '&token=' . $token . '&sso=turu' . (array_key_exists('param', $domains[$hosto]) ? $domains[$hosto]['param'] : '');

                     $data = [
                        'domain' => $domains[$hosto],
                        'key' => $domains[$hosto]['private'],
                        'host' => $host,
                        'token' => $token,
                        'session_id' => session_id(),
                        'identical?' => $auth2 == $auth ? 'Yes' : 'No',
                        'hsr_sso_hash' => $auth,
                        'hash_hmac' => $auth2,
                        'link' => $link,
                     ];
                     \Drupal::logger('Hash Result')->info('<pre>' . print_r($data, true) . '</pre>');
                  } else {
                     // $title = 'NOT  FOUND';
                     $data['link(Before)'] = $link;
                     $link = str_replace('hahayayata', $uid, $link);
                     $data['link(After)'] = $link;
                     \Drupal::logger('SSO Link')->info('<pre>' . print_r($data, true) . '</pre>');
                  }
               }

            }
            $title_html = "<a href=\"{$link}\" {$target}> {$title}</a>";
            $title_html = "<a href=\"{$link}\" {$target}>{$title}</a>";
         }

         $_classes = count($class) > 0 ? implode(' ', $class) : '';
         $_style = count($style) > 0 ? 'style="' . implode(';', $style) . '"' : '';
         ob_start();
         ?>

         <div class="widget gsc-icon-box <?php print $_classes ?>" <?php print $_style ?>
            <?php print gavias_content_builder_print_animate_wow('', $animate_delay) ?>>
            <a data-track="true" href="<?php echo $link; ?>" <?php echo $target; ?>>
               <?php if ($icon || $image) { ?>
                  <div class="highlight-icon verticle-align-<?php print $vertical_align_content ?>">
                     <span class="icon-inner <?php echo $icon_class_inner ?>">
                        <span class="icon-container <?php print $icon_class ?>" <?php print $style_icon ?>>
                           <?php if ($icon) { ?><span class="icon <?php print $icon ?>"></span> <?php } ?>
                           <?php if ($image) { ?><span class="icon"><img src="<?php print $image ?>"
                                    alt="<?php print strip_tags($title) ?>" /> </span> <?php } ?>
                        </span>
                     </span>
                  </div>
               <?php } ?>

               <div class="highlight_content verticle-align-<?php print $vertical_align_content ?>">
                  <?php if ($title) { ?>
                     <h3 class="title <?php print $classes_title_text ?>"><?php print $title_html; ?></h3>
                  <?php } ?>
                  <?php if ($content) { ?>
                     <div class="desc <?php print $classes_desc_text ?>"><?php print $content; ?></div>
                  <?php } ?>
               </div>
            </a>
         </div>


         <?php return ob_get_clean() ?>
         <?php
      }

   }
endif;
