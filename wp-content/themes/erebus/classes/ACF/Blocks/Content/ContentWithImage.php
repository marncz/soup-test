<?php

namespace MarcinTest\ACF\Blocks\Content;

use NanoSoup\Nemesis\ACF\BaseFields;
use NanoSoup\Nemesis\ACF\Blocks\Block;
use NanoSoup\Nemesis\ACF\Blocks\BlockInterface;
use Timber\Timber;

/**
 * Class ContentWithImage
 * @package MarcinTest\ACF\Blocks
 */
class ContentWithImage extends Block implements BlockInterface
{
    /**
     * ContentWithImage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        add_action('acf/init', [$this, 'registerBlock']);
        add_action('acf/init', [$this, 'registerFieldGroup']);
    }

    /**
     * Register the block with ACF
     */
    public function registerBlock(): void
    {
        $this->setBlockName('content-with-image')
            ->setBlockTitle('Content with Image')
            ->setBlockCallback([self::class, 'renderBlock'])
            ->setBlockIcon('align-right')
            ->setCat('content')
            ->setPostTypes(['page', 'post', 'support-articles'])
            ->saveBlock();
    }

    /**
     * Render the block using HTML/Twig
     *
     * @param $block
     */
    public static function renderBlock($block): void
    {
        $vars['block'] = $block;
        $vars['fields'] = get_fields();

        if (array_key_exists('block_title', $vars['fields'])) {
            $vars['fields']['scroll_title'] = sanitize_title_with_dashes($vars['fields']['block_title']);
        }

        Timber::render('/classes/ACF/Blocks/Content/views/content-with-image.twig', $vars);
    }

    /**
     * Register the field group with ACF
     */
    public function registerFieldGroup(): void
    {
        $fields = new BaseFields();
        $prefix = 'content_with_image';

        acf_add_local_field_group([
            'key' => 'group_block_' . $prefix,
            'title' => 'Block: Content with Image',
            'fields' => [
                $fields->text($prefix . __FUNCTION__, 'Block title', 0, 'This is used when the page has an anchor menu'),
                $fields->select($prefix . __FUNCTION__, 'Background Colour', [
                    'white' => 'White',
                    'blue' => 'Blue'
                ]),
                $fields->choice($prefix . __FUNCTION__, 'Flip Content'),
                $fields->text($prefix . __FUNCTION__, 'Title'),
                $fields->text($prefix . __FUNCTION__, 'Subtitle'),
                $fields->wysiwyg($prefix . __FUNCTION__, 'Content'),
                $fields->image($prefix . __FUNCTION__, 'Image')
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/content-with-image',
                    ]
                ]
            ],
        ]);
    }
}