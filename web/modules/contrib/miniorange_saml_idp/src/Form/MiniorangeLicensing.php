<?php
/**
 * @file
 * Contains Licensing information for miniOrange SAML Login Module.
 */

namespace Drupal\miniorange_saml_idp\Form;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;

class MiniorangeLicensing extends FormBase{
    
    protected RendererInterface $renderer;
    protected $configFactory;
    protected $moduleExtensionList;


    public function __construct(RendererInterface $renderer,ConfigFactoryInterface $configFactory,ModuleExtensionList $moduleExtensionList) {
        $this->renderer = $renderer;
        $this->moduleExtensionList = $moduleExtensionList;
        $this->configFactory = $configFactory;
    }

    public static function create(ContainerInterface|\Symfony\Component\DependencyInjection\ContainerInterface $container) {
        return new static(
        $container->get('renderer'),
        $container->get('config.factory'),
        $container->get('extension.list.module')
        );
    }

    public function getFormId() {
        return 'miniorange_saml_licensing';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
        $module_path = $base_url . '/' . $this->moduleExtensionList->getPath("miniorange_saml_idp");
        $module_data = $this->moduleExtensionList->getExtensionInfo('miniorange_saml_idp');
            $features = [
    [
        'title' => 'Unlimited SSO for all user roles',
        'description' => "You can allow all users on your Drupal site to log in using Single Sign-On (SSO)  whether they are admins, editors, members, or customers. There's no limit on the number of roles that can use SSO.",
    ],
    [
        'title' => 'Support multiple applications/service providers',
        'description' => 'You can connect your Drupal site to many external applications or services (like CRMs, learning portals, or cloud tools) using SSO. This means users can log in once and get access to all connected platforms without entering credentials again.',
    ],
    [
        'title' => 'Both SP and Idp-initiated SSO',
        'description' => '<p><strong>SP-initiated</strong>: User clicks login on the third-party app (Service Provider), and it redirects to Drupal for authentication.</p><p><strong>IdP-initiated</strong>: User logs in from Drupal (Identity Provider) and is then redirected to the connected app.</p>',
    ],
    [
        'title' => 'Supports SAML Single Logout',
        'description' => 'When a user logs out from one application, they are automatically logged out from all connected applications at once. This is called Single Logout (SLO), and it helps in maintaining security and consistency across platforms.',
    ],

    [
        'title' => 'Sends all user attributes and roles in SAML assertion',
        'description' => 'When a user logs in using SSO, Drupal sends their information (like name, email, and user roles) to the connected app. This is helpful for the external system to know who the user is and what permissions they have.',
    ],
    [
        'title' => 'Support for signed response and encrypted assertion',
        'description' => 'To make the login data safe, the SAML responses can be digitally signed (to ensure authenticity) and encrypted (to protect sensitive information). This adds a strong layer of security to the login process.',
    ],
    ];
        $related_products = [
      [
          'title' => 'User Provisioning & Sync (SCIM)',
          'description' => 'The Drupal User Provisioning and Sync module automates the creation, updating, and removal of user accounts across systems. It seamlessly syncs user roles and groups between the identity provider and your Drupal site, ensuring consistent access control and efficient user management.',
          'image' => 'user-provisioning.webp',
          'link' => 'https://plugins.miniorange.com/drupal-user-provisioning-and-sync'
      ],
      [
          'title' => 'Two Factor Authentication',
          'description' => 'Second-Factor Authentication (TFA) adds a second layer of security with an option to configure truly Passwordless Login. You can configure the module to send an OTP to your preferred mode of communication like phone/email, integrate with TOTP Apps like Google Authenticator or configure hardware token 2FA method.',
          'image' => '2fa.webp',
          'link' => 'https://plugins.miniorange.com/drupal-two-factor-authentication-2fa'
      ],
      [
          'title' => 'OTP Verification',
          'description' => 'Drupal OTP Verification module verifies Email Address/Mobile Number of users by sending verification code(OTP) during registration. It eliminates the possibility of a user registering with invalid personal details (phone number or email) on the Drupal site.',
          'image' => 'otp.webp',
          'link' => 'https://plugins.miniorange.com/drupal-otp-verification'
      ],
      [
          'title' => 'REST API Authentication',
          'description' => 'Drupal REST & JSON API Authentication module secures your Drupal site APIs against unauthorized access by enforcing different authentication methods including Basic Authentication, API Key Authentication, JWT Authentication, Third-Party Provider Authentication, etc.',
          'image' => 'rest-api.webp',
          'link' => 'https://plugins.miniorange.com/drupal-rest-api-authentication'
      ],
      [
          'title' => 'Session Management',
          'description' => 'User Session Management module helps you to manage the Drupal user session-related operations. It efficiently handles user sessions and provides you with multiple features like terminating any user session from the admin section, auto-logout user on being idle for the configured amount of time, limiting the number of simultaneous sessions per user, IP-based login restrictions, and many more.',
          'image' => 'session-management.webp',
          'link' => 'https://plugins.miniorange.com/drupal-session-management'
      ],
      [
          'title' => 'Website Security Pro',
          'description' => 'The Website Security Pro module safeguards your Drupal site with enterprise-grade security. It protects against brute force and DDoS attacks, enforces strong passwords, monitors and blacklists suspicious IPs, and secures login and registration forms. Designed to block hackers and malware, it ensures your site stays secure, stable, and reliable.',
          'image' => 'web-security.webp',
          'link' => 'https://plugins.miniorange.com/drupal-web-security-pro',
      ],
      ];

        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    "miniorange_saml_idp/miniorange_saml_idp.admin"
                )
            )
        );
        $form['current_plan_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['mo-saml-current-plan-section']
            ],
        ];
        $form['current_plan_section']['plan_info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['plan-info']],
        ];

        $form['current_plan_section']['plan_info']['plan_type'] = [
            '#type' => 'markup',
            '#markup' => 'Current Plan: Free Version',
        ];

        $form['current_plan_section']['plan_info']['module_info'] = [
            '#type' => 'markup',
            '#markup' => '<h3>SAML IdP</h3>',
            '#attributes' => ['class' => ['module-info']],
        ];

        $form['current_plan_section']['plan_info']['version_info'] = [
            '#type' => 'markup',
            '#markup' => 'Version: ' . $module_data['version'],
        ];

        // Right side upgrade button
        $form['current_plan_section']['upgrade_button'] = [
            '#type' => 'link',
            '#title' => t('Upgrade Now'),
            '#url' => Url::fromUri('https://plugins.miniorange.com/drupal-saml-idp'),
            '#attributes' => [
                'class' => ['button', 'button--primary'],
                'target' => '_blank',
            ],

        ];

        // Premium Features Section
        $form['premium_features_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['mo-saml-section-container'],
            ],
        ];

        $form['premium_features_section']['title'] = [
            '#type' => 'markup',
            '#markup' => '<h3>Premium Features</h3>',
            '#prefix' => '<div class="mo-saml-section-title">',
        ];

        $form['premium_features_section']['upgrade-button'] = [
            '#type' => 'link',
            '#title' => t('View All Features'),
            '#url' => Url::fromUri('https://plugins.miniorange.com/drupal-saml-idp'),
            '#attributes' => [
                'class' => ['button', 'button--primary'],
                'target' => '_blank',
            ],

            '#suffix' => '</div>',
        ];

        // Features Grid Container
        $form['premium_features_section']['features_grid'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['mo-saml-features-grid'],
            ],
        ];

        foreach ($features as $index => $feature) {
            $form['premium_features_section']['features_grid']['feature_' . $index] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['mo-saml-feature-box']
                ],
            ];

            $form['premium_features_section']['features_grid']['feature_' . $index]['content'] = [
                '#type' => 'markup',
                '#markup' => '<h5>' . $feature['title'] . '</h5>' . $feature['description'],
            ];
        }

        // Related Products Section
        $form['related_products_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['mo-saml-section-container'],
            ],
        ];

        $form['related_products_section']['title'] = [
            '#type' => 'markup',
            '#markup' => '<h3>Related Products</h3>',
            '#prefix' => '<div class="mo-saml-section-title">',
        ];

        $form['related_products_section']['upgrade-button'] = [
            '#type' => 'link',
            '#title' => t('View All Products'),
            '#url' => "",
            '#attributes' => [
                'class' => ['button', 'button--primary'],
            ],
            '#target' => '_blank',
            '#suffix' => '</div>',
        ];

        // Features Grid Container
        $form['related_products_section']['features_grid'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['mo-saml-features-grid']
            ],
        ];

        // Feature boxes with images

        foreach ($related_products as $index => $product) {
            $form['related_products_section']['features_grid']['feature_' . $index] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['mo-saml-feature-box']
                ],
            ];

            $form['related_products_section']['features_grid']['feature_' . $index]['title'] = [
                '#type' => 'markup',
                '#markup' => '<h5>' . $product['title'] . '</h5><hr>',
            ];

            // Image
            $form['related_products_section']['features_grid']['feature_' . $index]['image'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<div class="mo-saml-feature-image-div"><img class="mo-saml-feature-image" src=":module_path/images/banners/:image" alt=":title"></div>', [
                    ':module_path' => $module_path,
                    ':image' => $product['image'],
                    ':title' => $product['title'],
                ]),
            ];

            // Title and Description
            $form['related_products_section']['features_grid']['feature_' . $index]['content'] = [
                '#type' => 'markup',
                '#markup' => '<span class="mo-saml-product-description">' . $product['description'] . '</span>',
            ];

            // View Details Button
            $form['related_products_section']['features_grid']['feature_' . $index]['button'] = [
                '#type' => 'link',
                '#title' => t('View Details'),
                '#url' => "",
                '#attributes' => [
                    'class' => ['button', 'button--primary'],
                    'style' => 'margin-top: 10px;',
                ],
                '#target' => '_blank',
            ];
        }
        

        $rows = [
        [ Markup::create(t('<b>1.</b> Click on Upgrade Now button for required licensed plan and you will be redirected to miniOrange login console.</li>')), Markup::create(t('<b>5.</b> Uninstall and then delete the free version of the module from your Drupal site.')) ],
        [ Markup::create(t('<b>2.</b> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.')), Markup::create(t('<b>6.</b> Now install the downloaded licensed version of the module.')) ],
        [ Markup::create(t('<b>3.</b> Enter your card details and proceed for payment. On successful payment completion, the Licensed version module(s) will be available to download.')), Markup::create(t('<b>7.</b> Clear Drupal Cache from <a href="'.$base_url.'/admin/config/development/performance" >here</a>.')) ],
        [ Markup::create(t('<b>4.</b> Download the licensed module(s) from Module Releases and Downloads section.')), Markup::create(t('<b>8.</b> After enabling the licensed version of the module, login using the account you have registered with us.')) ],
      ];

      $form['markup_how to upgrade'] = array(
        '#markup' => '<h3 class="mo_saml_text_center"><br>How to Upgrade to Licensed Version Module</h3>'
      );

      $form['miniorange_how_to_upgrade_table'] = array(
        '#type' => 'table',
        '#responsive' => TRUE,
        '#rows' => $rows,
        '#attributes' => ['style' => 'border:groove', 'class' => ['mo_how_to_upgrade']],
      );

        $form['markup_faq'] = [
            '#markup' => '<h3 class="mo_saml_faq_text_center"><br>Frequently Asked Questions</h3>'
        ];

        $form['miniorange_FAQ_table'] = [
            '#type' => 'table',
        ];

        $form['miniorange_FAQ_table'][0][0] = [
            '#type' => 'details',
            '#title' => $this->t('What is an instance?'),
            '#open' => false,
            '#attributes' => ['style' => ' width: 625px;']
        ];

        $form['miniorange_FAQ_table'][0][0]['detail1'] = [
            '#markup' => $this->t('A Drupal instance refers to a single installation of a Drupal site. It refers to each individual website where the module is active. In the case of a multisite/subsite Drupal setup, each site with a separate database will be counted as a single instance, e.g. If you have the dev-staging-prod type of environment then you will require 3 licenses of the module (with additional discounts applicable on pre-production environments).'),
        ];

        $form['miniorange_FAQ_table'][0][1] = [
            '#type' => 'details',
            '#title' => t('What is the Refund Policy?' ),
            '#open' => false,
            '#attributes' => ['style' => ' width: 625px;']
        ];

        $form['miniorange_FAQ_table'][0][1]['detail2'] = [
            '#markup' => $this->t('At miniOrange, we want to ensure 100% customer satisfaction with our product/module purchase. If the module you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved, we will refund the whole amount given that you have raised a refund request within the first 10 days of the purchase. For any queries regarding the return policy, please email us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>.'),
        ];

        $form['miniorange_FAQ_table'][1][0] = [
            '#type' => 'details',
            '#title' => t('Does miniOrange Offer Technical Support?'),
            '#open' => false,
            '#attributes' => ['style' => 'width: 625px;']
        ];

        $form['miniorange_FAQ_table'][1][0]['detail3'] = [
            '#markup' => $this->t('Yes, we provide 24x7 support for all and any issues you might face while using the module, which includes technical support from our developers. You can get prioritized support based on the Support Plan you have opted.'),
        ];

        $form['miniorange_FAQ_table'][1][1] = [
            '#type' => 'details',
            '#title' => t('Does miniOrange store any User data ?'),
            '#open' => false,
            '#attributes' => ['style' => 'width: 625px;']
        ];

        $form['miniorange_FAQ_table'][1][1]['detail4'] = [
            '#markup' => $this->t('miniOrange does not store or transfer any user data externally. All the data remains within your premises/server.')
        ];

        return $form;
    }

    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    }

}