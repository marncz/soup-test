<?php

namespace MarcinTest\ACF\Blocks\Content;

error_reporting(-1);
ini_set('display_errors', 'On');


use NanoSoup\Nemesis\ACF\BaseFields;
use NanoSoup\Nemesis\ACF\Blocks\Block;
use NanoSoup\Nemesis\ACF\Blocks\BlockInterface;
use Timber\Timber;

/**
 * Class ContentWithImage
 * @package MarcinTest\ACF\Blocks
 */
class Block5050 extends Block implements BlockInterface
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
        $this->setBlockName('block_5050')
            ->setBlockTitle('Block 5050')
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

        $position = $vars['fields']['text_position'] ?? 'left';

        Timber::render('/classes/ACF/Blocks/Content/views/block-5050__text-' . $position . '.twig', $vars);
    }

    /**
     * Register the field group with ACF
     */
    public function registerFieldGroup(): void
    {
        $fields = new BaseFields();
        $prefix = 'block_5050';

        $text_position_options = [
            'left' => 'left',
            'right' => 'right'
        ];

        acf_add_local_field_group([
            'key' => 'group_block_' . $prefix,
            'title' => 'Block 5050: Text with Image',
            'fields' => [
                $fields->text($prefix . __FUNCTION__, 'Title'),
                $fields->wysiwyg($prefix . __FUNCTION__, 'Content'),
                $fields->select($prefix . __FUNCTION__, 'Text Position', $text_position_options),
                $fields->image($prefix . __FUNCTION__, 'Image'),
                $fields->text($prefix . __FUNCTION__, 'Button Text'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/block-5050',
                    ]
                ]
            ],
        ]);
    }
}