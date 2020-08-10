<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_About
{
    /*
     * Css file suffix (version number with with extension)
     */
     private $css_file_suffix = '-' . CUSTOM_PERMALINKS_PLUGIN_VERSION . '.min.css';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->more_plugins();
    }

    /**
     * Print HTML for Custom Permalinks About Page.
     *
     * @since 1.2.11
     * @access private
     */
    private function more_plugins()
    {
        $filename   = 'about-plugins' . $this->css_file_suffix;
        $plugin_url = plugins_url( '/admin', CUSTOM_PERMALINKS_FILE );
        $img_src    = $plugin_url . '/images';
        wp_enqueue_style( 'style', $plugin_url . '/css/' . $filename );

        $fivestar    = '<span class="star">
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 53.867 53.867" width="15" height="15">
                            <polygon points="26.934,1.318 35.256,18.182 53.867,20.887 40.4,34.013 43.579,52.549 26.934,43.798
                            10.288,52.549 13.467,34.013 0,20.887 18.611,18.182 "/>
                          </svg>
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 53.867 53.867" width="15" height="15">
                          <polygon points="26.934,1.318 35.256,18.182 53.867,20.887 40.4,34.013 43.579,52.549 26.934,43.798
                              10.288,52.549 13.467,34.013 0,20.887 18.611,18.182 "/>
                          </svg>
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 53.867 53.867" width="15" height="15">
                            <polygon points="26.934,1.318 35.256,18.182 53.867,20.887 40.4,34.013 43.579,52.549 26.934,43.798
                              10.288,52.549 13.467,34.013 0,20.887 18.611,18.182 "/>
                          </svg>
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 53.867 53.867" width="15" height="15">
                            <polygon points="26.934,1.318 35.256,18.182 53.867,20.887 40.4,34.013 43.579,52.549 26.934,43.798
                              10.288,52.549 13.467,34.013 0,20.887 18.611,18.182 "/>
                          </svg>
                          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 53.867 53.867" width="15" height="15">
                            <polygon points="26.934,1.318 35.256,18.182 53.867,20.887 40.4,34.013 43.579,52.549 26.934,43.798
                              10.288,52.549 13.467,34.013 0,20.887 18.611,18.182 "/>
                          </svg>
                        </span>';
        ?>

        <div class="wrap">
          <div class="float">
            <h1>
              <?php
              esc_html_e( 'Custom Permalinks v' . CUSTOM_PERMALINKS_PLUGIN_VERSION, 'custom-permalinks' );
              ?>
            </h1>
            <div class="tagline">
              <p>
              <?php
              esc_html_e(
                  'Thank you for choosing Custom Permalinks! We hope that your experience with our plugin for updating permalinks is quick and easy. We are trying to make it more feasible for you and provide capabilities in it.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <p>
              <?php
              printf(
                  __(
                      'To support future development and help to make it even better just leaving us a <a href="%s" title="Custom Permalinks Rating" target="_blank">%s</a> rating with a nice message to me :)',
                      'custom-permalinks'
                  ),
                  'https://wordpress.org/support/plugin/custom-permalinks/reviews/?rate=5#new-post',
                  $fivestar
              );
              ?>
              </p>
            </div>
          </div>

          <div class="float">
            <object type="image/svg+xml" data="<?php echo esc_url( $img_src . '/custom-permalinks.svg' );?>" width="128" height="128"></object>
          </div>

          <div class="product">
            <h2>
            <?php
            esc_html_e( 'More from Sami Ahmed Siddiqui', 'custom-permalinks' );
            ?>
            </h2>
            <span>
            <?php
            esc_html_e(
                'Our List of Plugins provides the services which help you to prevent your site from XSS Attacks, Brute force attack, change absolute paths to relative, increase your site visitors by adding Structured JSON Markup and so on.',
                'custom-permalinks'
            );
            ?>
            </span>
            <div class="box recommended">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/prevent-xss-vulnerability.png' ); ?>" style="transform:scale(1.5)" />
              </div>

              <h3>
                <?php
                esc_html_e( 'Prevent XSS Vulnerability', 'custom-permalinks' );
                ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'Secure your site from the XSS Attacks so, your users won\'t lose any kind of information or not redirected to any other site by visiting your site with the malicious code in the URL or so. In this way, users can open their site URLs without any hesitation.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/prevent-xss-vulnerability/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box recommended">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/http-auth.svg' ); ?>" />
              </div>

              <h3>
              <?php
              esc_html_e( 'HTTP Auth', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'Allows you apply HTTP Auth on your site. You can apply Http Authentication all over the site or only the admin pages. It helps to stop crawling on your site while on development or persist the Brute Attacks by locking the Admin Pages.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/http-auth/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/make-paths-relative.svg' ); ?>" />
              </div>

              <h3>
              <?php
              esc_html_e( 'Make Paths Relative', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'Convert the paths(URLs) to relative instead of absolute. You can make Post, Category, Archive, Image URLs and Script and Style src as per your requirement. You can choose which you want to be relative from the settings Page.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/make-paths-relative/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/schema-for-article.svg' ); ?>" />
              </div>

              <h3>
              <?php
              esc_html_e( 'SCHEMA for Article', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'Simply the easiest solution to add valid schema.org as a JSON script in the head of blog posts or articles. You can choose the schema either to show with the type of Article or NewsArticle from the settings page.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/schema-for-article/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/remove-links-and-scripts.svg' ); ?>" />
              </div>

              <h3>
              <?php
              esc_html_e( 'Remove Links and Scripts', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'It removes some meta data from the wordpress header so, your header keeps clean of useless information like shortlink, rsd_link, wlwmanifest_link, emoji_scripts, wp_embed, wp_json, emoji_styles, generator and so on.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/remove-links-and-scripts/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/media-post-permalink.png' ); ?>" style="transform:scale(1.5)" />
              </div>

              <h3>
              <?php
              esc_html_e( 'Media Post Permalink', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'On uploading  any image, let\'s say services.png, WordPress creates the attachment post with the permalink of /services/ and doesn\'t allow you to use that permalink to point your page. In this case, we come up with this great solution.',
                   'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/media-post-permalink/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>

            <div class="box">
              <div class="img">
                <img src="<?php echo esc_url( $img_src . '/json-structuring-markup.svg' ); ?>" />
              </div>

              <h3>
              <?php
              esc_html_e( 'JSON Structuring Markup', 'custom-permalinks' );
              ?>
              </h3>
              <p>
              <?php
              esc_html_e(
                  'Simply the easiest solution to add valid schema.org as a JSON script in the head of posts and pages. It provides you multiple SCHEMA types like Article, News Article, Organization and Website Schema.',
                  'custom-permalinks'
              );
              ?>
              </p>
              <a href="https://wordpress.org/plugins/json-structuring-markup/" class="checkout-button" target="_blank">
                <?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
              </a>
            </div>
          </div>
        </div>
        <?php
    }
}
