<?php
namespace Optilarity\FlatsomeExtra;

class TaxonomyFeaturedThumbnail
{
    protected static $instance;

    protected $taxonomies = [];

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function register($taxonomy)
    {
        if (is_array($taxonomy)) {
            foreach ($taxonomy as $tax) {
                $this->register($tax);
            }
            return;
        }

        if (in_array($taxonomy, $this->taxonomies)) {
            return;
        }

        $this->taxonomies[] = $taxonomy;

        add_action("{$taxonomy}_add_form_fields", [$this, 'addThumbnailField']);
        add_action("{$taxonomy}_edit_form_fields", [$this, 'editThumbnailField'], 10, 2);
        add_action("created_{$taxonomy}", [$this, 'saveThumbnailField']);
        add_action("edited_{$taxonomy}", [$this, 'saveThumbnailField']);
    }

    public function enqueueAssets($hook)
    {
        if (!in_array($hook, ['edit-tags.php', 'term.php'])) {
            return;
        }
        wp_enqueue_media();
    }

    public function addThumbnailField($taxonomy)
    {
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label>
                <?php _e('Thumbnail', 'akselos'); ?>
            </label>
            <div id="taxonomy-thumbnail-preview" style="float: left; margin-right: 10px;">
                <img src="<?php echo esc_url(WPINC . '/images/blank.gif'); ?>" width="60px" height="60px" />
            </div>
            <div style="line-height: 60px;">
                <input type="hidden" id="taxonomy-thumbnail-id" name="taxonomy_thumbnail_id" value="" />
                <button type="button" class="upload-taxonomy-thumbnail button">
                    <?php _e('Upload/Add image', 'akselos'); ?>
                </button>
                <button type="button" class="remove-taxonomy-thumbnail button">
                    <?php _e('Remove image', 'akselos'); ?>
                </button>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var frame;
                    $('.upload-taxonomy-thumbnail').on('click', function (e) {
                        e.preventDefault();
                        if (frame) {
                            frame.open();
                            return;
                        }
                        frame = wp.media({
                            title: '<?php _e('Select or Upload Thumbnail', 'akselos'); ?>',
                            button: { text: '<?php _e('Use this image', 'akselos'); ?>' },
                            multiple: false
                        });
                        frame.on('select', function () {
                            var attachment = frame.state().get('selection').first().toJSON();
                            $('#taxonomy-thumbnail-id').val(attachment.id);
                            $('#taxonomy-thumbnail-preview img').attr('src', attachment.url);
                        });
                        frame.open();
                    });
                    $('.remove-taxonomy-thumbnail').on('click', function (e) {
                        e.preventDefault();
                        $('#taxonomy-thumbnail-id').val('');
                        $('#taxonomy-thumbnail-preview img').attr('src', '<?php echo esc_url(WPINC . '/images/blank.gif'); ?>');
                    });
                });
            </script>
            <div class="clear"></div>
        </div>
        <?php
    }

    public function editThumbnailField($term, $taxonomy)
    {
        $thumbnail_id = get_term_meta($term->term_id, 'taxonomy_thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_thumb_url($thumbnail_id) : WPINC . '/images/blank.gif';
        ?>
        <tr class="form-field term-thumbnail-wrap">
            <th scope="row"><label>
                    <?php _e('Thumbnail', 'akselos'); ?>
                </label></th>
            <td>
                <div id="taxonomy-thumbnail-preview" style="float: left; margin-right: 10px;">
                    <img src="<?php echo esc_url($image_url); ?>" width="60px" height="60px" />
                </div>
                <div style="line-height: 60px;">
                    <input type="hidden" id="taxonomy-thumbnail-id" name="taxonomy_thumbnail_id"
                        value="<?php echo esc_attr($thumbnail_id); ?>" />
                    <button type="button" class="upload-taxonomy-thumbnail button">
                        <?php _e('Upload/Add image', 'akselos'); ?>
                    </button>
                    <button type="button" class="remove-taxonomy-thumbnail button">
                        <?php _e('Remove image', 'akselos'); ?>
                    </button>
                </div>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        var frame;
                        $('.upload-taxonomy-thumbnail').on('click', function (e) {
                            e.preventDefault();
                            if (frame) {
                                frame.open();
                                return;
                            }
                            frame = wp.media({
                                title: '<?php _e('Select or Upload Thumbnail', 'akselos'); ?>',
                                button: { text: '<?php _e('Use this image', 'akselos'); ?>' },
                                multiple: false
                            });
                            frame.on('select', function () {
                                var attachment = frame.state().get('selection').first().toJSON();
                                $('#taxonomy-thumbnail-id').val(attachment.id);
                                $('#taxonomy-thumbnail-preview img').attr('src', attachment.url);
                            });
                            frame.open();
                        });
                        $('.remove-taxonomy-thumbnail').on('click', function (e) {
                            e.preventDefault();
                            $('#taxonomy-thumbnail-id').val('');
                            $('#taxonomy-thumbnail-preview img').attr('src', '<?php echo esc_url(WPINC . '/images/blank.gif'); ?>');
                        });
                    });
                </script>
                <div class="clear"></div>
            </td>
        </tr>
        <?php
    }

    public function saveThumbnailField($term_id)
    {
        if (isset($_POST['taxonomy_thumbnail_id'])) {
            update_term_meta($term_id, 'taxonomy_thumbnail_id', $_POST['taxonomy_thumbnail_id']);
        }
    }

    public static function getThumbnailId($term_id)
    {
        return get_term_meta($term_id, 'taxonomy_thumbnail_id', true);
    }

    public static function getThumbnailUrl($term_id, $size = 'thumbnail')
    {
        $thumbnail_id = self::getThumbnailId($term_id);
        if ($thumbnail_id) {
            return wp_get_attachment_image_url($thumbnail_id, $size);
        }
        return '';
    }
}
